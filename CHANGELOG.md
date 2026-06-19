# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] – 2026-06-19

### Geändert
- Eingangsbestätigung: Betreff, Text und Absendername mit echter Standardvorlage vorausgefüllt (frei editierbar)
- Anrede-Umschaltung (Du/Sie) tauscht Standardtexte live, behält eigene Anpassungen
- Hinweis „Wirksam durch Speichern" neben der Anrede-Auswahl
- Schnellstart: keine doppelten Widerrufsseiten mehr – bei vorhandener Seite Links zum Bearbeiten/Ansehen
- Admin-Anleitung: Emojis entfernt, doppelter Abschnitt „Eingangsbestätigung anpassen" entfernt

## [1.2.0] – 2026-06-19

### Neu
- Rechtssichere Eingangsbestätigung: Datum und Uhrzeit des Eingangs werden serverseitig (Site-Zeitzone) erfasst und in der Kunden-E-Mail ausgegeben
- Platzhalter-System für die Kunden-Mail: `{received_at}`, `{first_name}`, `{name}`, `{email}`, `{order_reference}`, `{order_number}`, `{items}`, `{order_date}`, `{received_date}`, `{address}`, `{reason}`, `{declaration}`, `{sender_name}`, `{site_name}`
- `{declaration}` liefert eine strukturierte Zusammenfassung aller ausgefüllten Felder mit aktuellen Feldbezeichnungen
- Standard-Vorlagen für Kunden-Mail: DE formell (Sie), DE informell (Du, angelehnt an Anwältin-Vorlage), EN
- Anrede (Du/Sie), Betreff, Mailtext und Absendername zentral konfigurierbar unter **E-Mail-Versand → Eingangsbestätigung**
- Feldbezeichnungen individuell überschreibbar pro Block (Inspector → Feldbezeichnungen), z. B. „Rechnungsnummer" statt „Bestell-/Buchungsnummer"
- Händler-Mail enthält ebenfalls den serverseitigen Eingangszeitstempel

### Geändert
- Eingangsbestätigung an Kunden nutzt eigene Vorlage statt der Händler-Mail (falsche Perspektive behoben)
- Admin-Anleitung mit Platzhalter-Referenztabelle und Link auf E-Mail-Versand erweitert

## [1.1.1] – 2026-06-17

- Fix Brevo field copy: SMTP login is the address from “Your SMTP settings” (e.g. name@smtp-brevo.com), not the Brevo sign-in email

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
