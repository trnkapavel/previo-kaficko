# GitHub release text (copy/paste)

Hotové texty pro nastavení repozitáře a první release.

## 1) Repository description (About)

PHP registrační landing page pro Previo MeetUp: formulář, admin, Google Sheets integrace, e-mail notifikace a WordPress-ready nasazení.

## 2) Website URL (volitelné)

- Lokální test: http://localhost:8888/kaficko/
- Produkce: doplň finální doménu (např. https://events.vasedomena.cz/)

## 3) Topics (doporučené štítky)

`php`, `landing-page`, `registration-form`, `wordpress`, `google-sheets`, `event-management`, `meetup`, `ics`

## 4) První release (v1.0.0)

### Tag

`v1.0.0`

### Release title

`v1.0.0 — Initial public release (MeetUp registration app)`

### Release notes

První veřejné vydání registrační aplikace pro Previo MeetUp.

Co release obsahuje:
- veřejnou landing page (`index.php`) s registračním formulářem
- administrační rozhraní (`admin.php`) pro správu textů, kapacity a programu
- backend zpracování registrací (`process_registration.php`)
- odesílání dat do Google Sheets přes Apps Script
- potvrzovací e-mail účastníkovi + notifikace adminovi
- generování kalendářové pozvánky (`download_ics.php`)
- dokumentaci pro nasazení vedle WordPressu

Přidané dokumenty:
- `README.md` (hlavní dokumentace)
- `docs/WORDPRESS_DEPLOY.md` (WordPress deploy návod)
- `docs/GO-LIVE_CHECKLIST.md` (kontrola před spuštěním)
- `docs/HANDOVER_TEMPLATE.md` (šablona předávacího e-mailu)

Poznámka:
- `docs/index.html` je statický preview pro GitHub Pages (bez PHP backendu).

## 5) Doporučené další releases

- `v1.1.0` — bezpečnostní hardening (CSRF, rate limit, lepší auth)
- `v1.2.0` — dynamické ICS z `data.json` místo hardcoded dat
- `v1.3.0` — konfigurace přes `.env`
