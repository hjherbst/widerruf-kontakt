# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] – 2026-06-17

- Honour block toolbar width (`alignwide` / `alignfull`) on the frontend
- Quick-start page uses a clean heading + form pattern instead of statutory boilerplate
- Remove field help texts (markup and strings, not CSS hiding)
- Streamline admin guide; show SMTP warning banner when delivery is not configured
- Clarify Brevo SMTP login field (account email vs. from address)

## [1.0.0] – 2026-06-16

- Initial release
- Withdrawal / revocation form block (goods, digital products, services)
- Contact form block
- SMTP setup wizard: Brevo, existing mailbox, manual
- Confirmation-of-receipt email to the sender (withdrawal form)
- Admin guide with quick-start helper "Create withdrawal page"
- Honeypot + IP rate-limit spam protection
- EN default, DE on German sites
- Auto-updates via GitHub Releases (plugin-update-checker)
