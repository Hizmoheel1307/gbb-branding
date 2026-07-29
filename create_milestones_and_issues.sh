#!/usr/bin/env bash
# Run from inside the gbb-branding repo folder (after the repo/skeleton already exist and are pushed).
set -euo pipefail

echo "==> Creating milestones"
gh api repos/:owner/:repo/milestones -f title="M1: App Foundation" -f description="Skeleton, autoloading, registration, install, CI" > /dev/null
gh api repos/:owner/:repo/milestones -f title="M2: Admin UI & Config" -f description="Settings UI, config storage, logo upload, validation" > /dev/null
gh api repos/:owner/:repo/milestones -f title="M3: Branding Engine" -f description="Email branding, header/footer injection, styling" > /dev/null

create_issue () {
  local title="$1" body="$2" milestone="$3"
  gh issue create --title "$title" --body "$body" --milestone "$milestone" > /dev/null
  echo "   + $title"
}

echo "==> Creating Milestone 1 issues"
create_issue "Create app skeleton" \
"Scaffold the app directory: appinfo/info.xml, lib/, templates/, css/, js/.
- [ ] info.xml with id, name, version, deps
- [ ] Base folder structure in place" "M1: App Foundation"

create_issue "Configure PSR-4 autoloading" \
"Set up composer.json PSR-4 mapping OCA\\GBBBranding\\ -> lib/.
- [ ] composer.json added
- [ ] composer dump-autoload runs clean
- [ ] vendor/ gitignored" "M1: App Foundation"

create_issue "Register app" \
"Wire up Application.php and app bootstrap.
- [ ] lib/AppInfo/Application.php extends App + implements Bootstrap
- [ ] Services registered in register()" "M1: App Foundation"

create_issue "Install into Nextcloud" \
"Symlink or copy app into a local Nextcloud instance's apps/ folder for dev testing.
- [ ] App appears in apps/ dir
- [ ] No errors in nextcloud.log on load" "M1: App Foundation"

create_issue "Enable via OCC" \
"Enable the app using occ app:enable gbb_branding.
- [ ] Enables without error
- [ ] Confirmed active via occ app:list" "M1: App Foundation"

create_issue "Add GitHub Actions for linting" \
"CI workflow for PHP lint + code style on push/PR.
- [ ] .github/workflows/lint.yml
- [ ] Runs php -l and phpcs (or php-cs-fixer)
- [ ] Fails PR on lint errors" "M1: App Foundation"

echo "==> Creating Milestone 2 issues"
create_issue "Build Admin UI" \
"Settings page under Administration > GBB Branding.
- [ ] Vue/plain JS settings section registered
- [ ] Fields: primary color, logo, email footer text" "M2: Admin UI & Config"

create_issue "Store configuration" \
"Persist settings via OCP\\IConfig (appconfig).
- [ ] Settings saved on submit
- [ ] Settings loaded on page render" "M2: Admin UI & Config"

create_issue "Upload logo" \
"Logo upload + storage (as app data, not public webroot).
- [ ] Upload endpoint/controller
- [ ] File type/size validated server-side
- [ ] Stored via OCP\\Files\\IAppData" "M2: Admin UI & Config"

create_issue "Validate settings" \
"Server-side validation for all admin settings fields.
- [ ] Hex color validated
- [ ] Logo mime type restricted (png/jpg/svg)
- [ ] Friendly error messages returned to UI" "M2: Admin UI & Config"

echo "==> Creating Milestone 3 issues"
create_issue "Email branding service" \
"Core service that applies branding to outgoing emails.
- [ ] EmailBrandingService in lib/Service/
- [ ] Hooks into Nextcloud mailer event/template" "M3: Branding Engine"

create_issue "Header injection" \
"Inject custom header (logo + color) into email templates.
- [ ] Header block renders configured logo
- [ ] Falls back to default when unset" "M3: Branding Engine"

create_issue "Footer injection" \
"Inject custom footer text/links into email templates.
- [ ] Footer text configurable
- [ ] Rendered in plain-text and HTML email variants" "M3: Branding Engine"

create_issue "Background colors" \
"Apply configured background color to email template.
- [ ] Color applied to header/body regions
- [ ] Contrast fallback if no color set" "M3: Branding Engine"

create_issue "Button styling" \
"Style CTA buttons in emails per branding config.
- [ ] Button color matches configured primary color
- [ ] Renders correctly in major email clients (Gmail/Outlook)" "M3: Branding Engine"

echo "==> Creating project board (v2) and linking issues"
OWNER=$(gh repo view --json owner --jq .owner.login)
PROJECT_URL=$(gh project create --owner "$OWNER" --title "GBB Branding Roadmap" --format json --jq .url)
echo "   Project: $PROJECT_URL"

gh issue list --state open --json url --jq '.[].url' | while read -r url; do
  gh project item-add "$(basename "$PROJECT_URL")" --owner "$OWNER" --url "$url" > /dev/null
done

echo "==> Done. Milestones, issues, and project board are set up."
