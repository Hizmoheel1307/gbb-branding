#!/usr/bin/env bash
#
# Deploys GovMail Branding to every Nextcloud app server behind the load
# balancer, in three phases so no server is left serving a half-deployed app:
#
#   1. Upload + extract the new files on ALL servers (app not touched yet)
#   2. On the PRIMARY server only: disable/enable the app and run
#      maintenance:repair (these touch the shared database, run them once)
#   3. On EVERY server: reconcile the email template override, because
#      config.php is per server, then print its status
#
# Servers without a public IP are reached through the primary server as an
# SSH jump host (ssh -J). Your PC's SSH key must be in root's
# authorized_keys on every server - see the README section in the chat.

set -euo pipefail

APP_NAME="govmailbranding"
ARCHIVE="${APP_NAME}.tar.gz"

NEXTCLOUD="/var/www/nextcloud"
APP_DIR="$NEXTCLOUD/apps/$APP_NAME"

# One entry per app server: "name|ssh target|jump host (empty = direct)".
# The FIRST entry is the primary: it runs app:enable and maintenance:repair.
SERVERS=(
    "dev-gvm-fe-app-0001|root@197.159.78.180|"
    "dev-gvm-fe-app-0002|root@10.0.146.168|root@197.159.78.180"
)

# Optional: deploy to one server only, e.g. ./deploy.sh dev-gvm-fe-app-0002
ONLY="${1:-}"

echo "======================================"
echo " GovMail Branding Deployment"
echo "======================================"

for cmd in git ssh scp; do
    command -v "$cmd" >/dev/null 2>&1 || {
        echo "$cmd is not installed or not in PATH."
        exit 1
    }
done

# --- helpers ---------------------------------------------------------------

field() { # field <entry> <index>
    local IFS='|'
    # shellcheck disable=SC2206
    local parts=($1)
    echo "${parts[$2]:-}"
}

jump_opts() { # jump_opts <jump host>
    if [ -n "$1" ]; then
        echo "-o ProxyJump=$1"
    fi
}

remote() { # remote <entry> <script on stdin>
    local target jump
    target=$(field "$1" 1)
    jump=$(field "$1" 2)
    # shellcheck disable=SC2046
    ssh -o BatchMode=yes -o ConnectTimeout=15 $(jump_opts "$jump") "$target" bash -s
}

# --- pick servers ----------------------------------------------------------

TARGETS=()
for entry in "${SERVERS[@]}"; do
    if [ -z "$ONLY" ] || [ "$(field "$entry" 0)" = "$ONLY" ]; then
        TARGETS+=("$entry")
    fi
done
if [ ${#TARGETS[@]} -eq 0 ]; then
    echo "No server named '$ONLY'. Known servers:"
    for entry in "${SERVERS[@]}"; do echo "  $(field "$entry" 0)"; done
    exit 1
fi
PRIMARY="${SERVERS[0]}"

echo
echo "Checking SSH access..."
for entry in "${TARGETS[@]}"; do
    name=$(field "$entry" 0)
    if echo 'hostname' | remote "$entry" >/dev/null 2>&1; then
        echo "  OK    $name"
    else
        echo "  FAIL  $name - cannot SSH in with your key. Nothing was deployed."
        exit 1
    fi
done

# --- commit + package --------------------------------------------------------

echo
echo "Git Status"
echo "--------------------------------------"
git status --short

echo
read -r -p "Commit changes? (y/N): " answer
if [[ "$answer" =~ ^[Yy]$ ]]; then
    read -r -p "Commit message: " msg
    git add .
    git commit -m "$msg" || echo "Nothing new to commit, continuing..."
fi

echo
echo "Creating deployment package from $(git rev-parse --short HEAD)..."
git archive --format=tar.gz --output="$ARCHIVE" HEAD
trap 'rm -f "$ARCHIVE"' EXIT

# --- phase 1: files on every server ------------------------------------------

for entry in "${TARGETS[@]}"; do
    name=$(field "$entry" 0)
    target=$(field "$entry" 1)
    jump=$(field "$entry" 2)

    echo
    echo "[$name] Uploading..."
    # shellcheck disable=SC2046
    scp -q -o BatchMode=yes $(jump_opts "$jump") "$ARCHIVE" "$target:/tmp/$ARCHIVE"

    echo "[$name] Installing files..."
    remote "$entry" <<EOF
set -euo pipefail
mkdir -p /tmp/backups "$APP_DIR"
if [ "\$(ls -A "$APP_DIR" 2>/dev/null)" ]; then
    rm -f "/tmp/backups/$APP_NAME.previous.tar.gz"
    tar -czf "/tmp/backups/$APP_NAME.previous.tar.gz" -C "$NEXTCLOUD/apps" "$APP_NAME"
    echo "  backup: /tmp/backups/$APP_NAME.previous.tar.gz"
fi
rm -rf "${APP_DIR:?}"/*
tar -xzf "/tmp/$ARCHIVE" -C "$APP_DIR"
chown -R www-data:www-data "$APP_DIR"
rm -f "/tmp/$ARCHIVE"
echo "  installed version: \$(grep -o '<version>[^<]*' "$APP_DIR/appinfo/info.xml" | cut -d'>' -f2)"
EOF
done

# --- phase 2: enable + repair once, on the primary --------------------------

primary_name=$(field "$PRIMARY" 0)
if [ -z "$ONLY" ] || [ "$ONLY" = "$primary_name" ]; then
    echo
    echo "[$primary_name] Enabling app and running repair (shared database, runs once)..."
    remote "$PRIMARY" <<EOF
set -euo pipefail
sudo -u www-data php "$NEXTCLOUD/occ" app:disable "$APP_NAME" || true
sudo -u www-data php "$NEXTCLOUD/occ" app:enable "$APP_NAME"
sudo -u www-data php "$NEXTCLOUD/occ" maintenance:repair > /tmp/$APP_NAME-repair.log 2>&1 \
    || { echo "  maintenance:repair failed, see /tmp/$APP_NAME-repair.log"; exit 1; }
grep -A1 'GovMail Branding' /tmp/$APP_NAME-repair.log || true
EOF
fi

# --- phase 3: per-server email template override ------------------------------

echo
echo "Email branding status"
echo "--------------------------------------"
for entry in "${TARGETS[@]}"; do
    name=$(field "$entry" 0)
    remote "$entry" <<EOF || echo "[$name] WARNING: override is not active on this server - check the output above."
sudo -u www-data php "$NEXTCLOUD/occ" govmailbranding:email:override reconcile
EOF
    echo
done

echo "======================================"
echo " Deployment finished on: $(for e in "${TARGETS[@]}"; do printf '%s ' "$(field "$e" 0)"; done)"
echo "======================================"
