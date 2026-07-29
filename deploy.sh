#!/usr/bin/env bash

set -euo pipefail

APP_NAME="govmailbranding"
ARCHIVE="${APP_NAME}.tar.gz"

SERVER="197.159.78.180"
USER="root"

NEXTCLOUD="/var/www/nextcloud"
APP_DIR="$NEXTCLOUD/apps/$APP_NAME"

echo "======================================"
echo " GovMail Branding Deployment"
echo "======================================"

# Ensure required local tools are available
for cmd in git ssh scp; do
    command -v "$cmd" >/dev/null 2>&1 || {
        echo "$cmd is not installed or not in PATH."
        exit 1
    }
done

echo
echo "Git Status"
echo "--------------------------------------"
git status --short

echo
read -p "Commit changes? (y/N): " answer

if [[ "$answer" =~ ^[Yy]$ ]]; then
    read -p "Commit message: " msg
    git add .
    git commit -m "$msg" || echo "Nothing new to commit, continuing..."
fi

echo
echo "Creating deployment package..."

git archive --format=tar.gz \
    --output="$ARCHIVE" \
    HEAD

echo
echo "Uploading..."

scp "$ARCHIVE" "$USER@$SERVER:/tmp/"

echo
echo "Deploying..."

ssh "$USER@$SERVER" bash -s <<EOF
set -euo pipefail

APP_NAME="$APP_NAME"
NEXTCLOUD="$NEXTCLOUD"
APP_DIR="$APP_DIR"
ARCHIVE="$ARCHIVE"

mkdir -p /tmp/backups
mkdir -p "\$APP_DIR"

# Only back up if there's something there already (skips cleanly on first deploy)
if [ "\$(ls -A "\$APP_DIR" 2>/dev/null)" ]; then
    echo "Backing up current version..."
    rm -f "/tmp/backups/\${APP_NAME}.previous.tar.gz"
    tar -czf "/tmp/backups/\${APP_NAME}.previous.tar.gz" \
        -C "\$NEXTCLOUD/apps" \
        "\$APP_NAME"
else
    echo "No existing deployment found, skipping backup."
fi

echo "Extracting new version..."
rm -rf "\${APP_DIR:?}"/*
tar -xzf "/tmp/\$ARCHIVE" -C "\$APP_DIR"

echo "Fixing ownership..."
chown -R www-data:www-data "\$APP_DIR"

echo "Enabling app..."
sudo -u www-data php "\$NEXTCLOUD/occ" app:disable "\$APP_NAME" || true
sudo -u www-data php "\$NEXTCLOUD/occ" app:enable "\$APP_NAME"
sudo -u www-data php "\$NEXTCLOUD/occ" maintenance:repair

rm -f "/tmp/\$ARCHIVE"

echo "Remote deployment steps complete."
EOF

rm -f "$ARCHIVE"

echo
echo "======================================"
echo " Deployment Completed Successfully"
echo "======================================"
