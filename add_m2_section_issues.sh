#!/usr/bin/env bash
# Run from inside the gbb-branding repo folder.
set -euo pipefail

create_issue () {
  local title="$1" body="$2"
  gh issue create --title "$title" --body "$body" --milestone "M2: Admin UI & Config" > /dev/null
  echo "   + $title"
}

echo "==> Adding M2 section issues"

create_issue "Settings: General Branding tab" \
"Fields: Portal Name, Company Name, Support Email, Support Phone, Website, Footer Text, Application Name, Web Link, Slogan, Legal Notice, Privacy Policy, Logo, Header Logo, Favicon, Background Image.
- [ ] All text fields save/load via IConfig
- [ ] 4 image fields upload via IAppData
- [ ] Uses Nextcloud native theming APIs where applicable"

create_issue "Settings: Theming tab" \
"General theming controls beyond individual colors (placeholder scope, refine once General Branding pattern is proven)."

create_issue "Settings: Logo Management tab" \
"Dedicated logo management view (may fold into General Branding depending on final UX)."

create_issue "Settings: Login Branding tab" \
"Fields: Login Title, Login Subtitle, Login Background, Login Logo.
- [ ] Text fields save/load via IConfig
- [ ] 2 image fields upload via IAppData
- [ ] Applied to Nextcloud login screen"

create_issue "Settings: Colors tab" \
"Fields: Primary Color, Background Color, Header Color, Accent Color.
- [ ] Color picker inputs, hex validated
- [ ] Saved via IConfig
- [ ] Live preview swatch"

create_issue "Settings: Footer tab" \
"Fields: Copyright, Company, Links, Version.
- [ ] Links field supports multiple label+URL pairs
- [ ] Saved via IConfig"

create_issue "Settings: Email Branding tab" \
"Fields: Email Logo, Email Header, Email Footer, Signature, Social Links.
- [ ] Image fields upload via IAppData
- [ ] Wires into EmailBrandingService from Milestone 3"

create_issue "Settings: Dashboard Branding tab" \
"Branding controls for the Nextcloud dashboard widget/homepage (scope TBD)."

create_issue "Settings: Custom CSS tab" \
"Textarea for raw custom CSS, injected app-wide.
- [ ] Saved via IConfig
- [ ] Sanitized/escaped on output to prevent injection"

create_issue "Settings: Custom JavaScript tab" \
"Textarea for raw custom JS, injected app-wide.
- [ ] Saved via IConfig
- [ ] Clear admin-only warning about XSS risk of this feature"

create_issue "Settings: Advanced tab" \
"Catch-all for remaining config (scope TBD as other tabs solidify)."

echo "==> Done."
