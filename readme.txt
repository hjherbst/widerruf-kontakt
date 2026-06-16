=== Widerruf & Kontakt ===
Contributors: hjherbst
Tags: widerruf, widerrufsformular, kontaktformular, withdrawal, contact form, DSGVO
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Datenschutzkonformes Widerrufsformular und Kontaktformular als native Gutenberg-Blöcke.

== Description ==

**Widerruf & Kontakt** stellt zwei native Gutenberg-Blöcke bereit:

1. **Widerrufsformular** – Erfüllt die gesetzliche Pflicht, Verbrauchern einen einfach zugänglichen Widerruf zu ermöglichen. Funktioniert für physische Waren, digitale Produkte und Dienstleistungsverträge. Mit automatischer Eingangsbestätigung per E-Mail an den Absender.

2. **Kontaktformular** – Schlankes Kontaktformular mit konfigurierbaren Feldern, Datenschutz-Checkbox und E-Mail-Versand.

**Features:**
- Kein Build-Schritt, keine externe Abhängigkeit
- SMTP-Setup mit Brevo (kostenlos), eigenem Postfach oder manueller Konfiguration
- Eingangsbestätigung an Absender (Widerruf)
- Vertragstyp-Wortlaut anpassbar (Waren / digitale Produkte / Dienstleistung)
- Honeypot + IP-Rate-Limit-Spamschutz
- EN-Default, DE auf deutschen Sites
- Auto-Updates über GitHub-Releases
- DSGVO-konform: keine Datenbankprotokollierung der Einreichungen

**Hinweis:** Dieses Plugin ersetzt keine Rechtsberatung. Widerrufsbelehrung und -recht sind mit einem Anwalt oder einer Rechtsschutzlösung abzustimmen.

== Installation ==

1. ZIP von der [Plugin-Seite](https://gutenblock.com/widerruf-kontakt) oder der [GitHub Releases-Seite](https://github.com/hjherbst/widerruf-kontakt/releases) herunterladen
2. WordPress Admin → Plugins → Installieren → Plugin hochladen → ZIP auswählen → Aktivieren
3. Admin-Menü „Widerruf & Kontakt" → Anleitung aufrufen

Bestehende Installationen erhalten Updates automatisch über den WordPress-Update-Mechanismus.

== Frequently Asked Questions ==

= Werden Widerrufe in der Datenbank gespeichert? =
Nein. Aus Gründen der DSGVO-Datensparsamkeit werden Einreichungen ausschließlich per E-Mail übermittelt. Die Eingangsbestätigung an den Absender dient als Nachweis.

= Welchen E-Mail-Dienst soll ich verwenden? =
Brevo (ehemals Sendinblue) ist die empfohlene Option – kostenlos für bis zu 300 E-Mails/Tag, einfach einzurichten. Alternativ funktioniert jedes SMTP-Postfach.

= Kann ich das Plugin zusammen mit GutenBlock (Pro) nutzen? =
Ja. Die Plugins verwenden unterschiedliche Prefixe und Datenbank-Optionen und laufen konfliktfrei nebeneinander.

= Ist das Plugin rechtssicher? =
Das Plugin stellt das technische Werkzeug bereit. Widerrufsbelehrung, AGB und die konkrete rechtliche Umsetzung sind mit einem Anwalt oder einem Rechtsdienst (z. B. IT-Recht Kanzlei, e-recht24) abzustimmen.

== Changelog ==

= 1.0.0 =
* Erstveröffentlichung
* Widerrufsformular-Block (Waren, digitale Produkte, Dienstleistungen)
* Kontaktformular-Block
* SMTP-Setup (Brevo, Postfach, Manuell) mit Test-Mail
* Admin-Anleitung mit Schnellstart-Helfer „Widerrufsseite erstellen"
