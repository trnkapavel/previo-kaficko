# Previo MeetUp – registrační landing page (PHP)

Jednoduchá webová aplikace pro meetup/roadshow registrace.
Obsahuje veřejnou stránku s formulářem, admin rozhraní pro editaci obsahu akce a backend pro zápis registrací + odesílání e-mailů.

Je připravená pro rychlé nasazení jako samostatný mini-projekt vedle WordPressu (typicky subdoména nebo podsložka).

## Co projekt dělá

### 1) Veřejná stránka (`index.php`)
- načítá data akce z `data.json` (město, datum, čas, venue, kapacita, program)
- zobrazuje kompletní landing page s programem, FAQ a CTA
- má registrační formulář odesílaný přes `fetch()` na backend
- po úspěchu zobrazí potvrzovací modal a nabídku kalendáře (Google + ICS)

### 2) Administrace (`admin.php`)
- jednoduché přihlášení heslem (session)
- editace textů, kapacity, programu i detailů akce
- ukládání změn zpět do `data.json`

### 3) Zpracování registrací (`process_registration.php`)
- validace základních polí (`name`, `email`)
- odeslání registrace do Google Sheets (přes Google Apps Script URL)
- odeslání potvrzovacího e-mailu účastníkovi
- odeslání notifikačního e-mailu adminovi
- JSON odpověď pro frontend

### 4) Kalendářová pozvánka (`download_ics.php`)
- vygeneruje `.ics` soubor pro Outlook / Apple Calendar

### 5) Diagnostika (`test_data.php`)
- ověří dostupnost a validitu `data.json`

## Struktura projektu

```
kaficko/
├── index.php
├── admin.php
├── process_registration.php
├── download_ics.php
├── test_data.php
├── data.json
├── docs/
│   └── index.html
└── img/
```

## Požadavky

- PHP 8.0+ (doporučeno)
- povolené PHP funkce: `mail()`, `curl` extension
- webserver s možností zápisu do `data.json`

## Rychlé spuštění lokálně

```bash
php -S localhost:8000
```

Pak otevři:
- `http://localhost:8000` (web)
- `http://localhost:8000/admin.php` (administrace)
- `http://localhost:8000/test_data.php` (diagnostika)

## Základní konfigurace před produkcí

### 1) Uprav přístup do administrace
V souboru `admin.php` změň výchozí heslo `previo` na vlastní silné heslo.

### 2) Uprav napojení na Google Sheets
V souboru `process_registration.php` nastav:
- `$googleScriptUrl` na vlastní Apps Script endpoint (`.../exec`)

### 3) Uprav e-mailové adresy
V souboru `process_registration.php` nastav:
- `$senderEmail`
- `$adminEmail`
- případně `Reply-To`

### 4) Ověř práva souboru
Soubor `data.json` musí být zapisovatelný uživatelem webserveru.

## WordPress nasazení (doporučený postup)

Nejjednodušší a nejstabilnější je provozovat tuto aplikaci **vedle WordPressu**, ne uvnitř šablony/pluginu.

### Varianta A (doporučeno): samostatná subdoména
Příklad:
- WP běží na `www.moje-domena.cz`
- registrace běží na `events.moje-domena.cz`

Postup:
1. Nahraj celý projekt na subdoménu.
2. Ověř, že funguje `index.php`, `admin.php`, `process_registration.php`.
3. Ve WordPressu vlož tlačítko/menu odkaz na registrační stránku.

Výhody:
- nulový konflikt s WP pluginy, cache a šablonou
- jednoduchý update (jen přepíšeš soubory)

### Varianta B: podsložka na stejné doméně
Příklad: `www.moje-domena.cz/meetup/`

Postup:
1. Nahraj projekt do podsložky `meetup`.
2. Ve WordPressu nepřepisuj tuto cestu přes permalink pravidla.
3. Přidej odkaz na `/meetup/` do stránky nebo menu.

> Pokud máš agresivní cache/plugin firewall, přidej výjimku pro `process_registration.php`.

## Jak založit repozitář na GitHubu

Pokud projekt teprve zakládáš jako nový repozitář:

```bash
git init
git add .
git commit -m "Initial commit: meetup registration app"
git branch -M main
git remote add origin https://github.com/TVUJ-UCET/NAZEV-REPO.git
git push -u origin main
```

Pak v GitHub repozitáři:
- ověř, že `README.md` se zobrazuje jako hlavní dokumentace
- případně zapni GitHub Pages jen pro `docs/index.html` (statický preview)

## Detailní návod pro WP admina

Podrobný deployment checklist je v dokumentu:

- `docs/WORDPRESS_DEPLOY.md`
- `docs/GO-LIVE_CHECKLIST.md`
- `docs/HANDOVER_TEMPLATE.md`
- `docs/GITHUB_RELEASE_TEXT.md`

## GitHub Pages preview

Soubor `docs/index.html` je statická ukázka designu.
Na GitHub Pages **nefunguje** PHP backend (registrace, admin, e-maily).

## Známá omezení

- přihlášení do administrace je jednoduché (session + heslo v kódu)
- `download_ics.php` má aktuálně pevně zadané datum/čas/místo
- chybí ochrana proti CSRF/rate limit (pro veřejnou produkci doporučeno doplnit)

## Doporučení pro produkci

- zapnout HTTPS
- změnit admin heslo před spuštěním
- sledovat doručitelnost e-mailů (SPF, DKIM, DMARC)
- otestovat celý flow: formulář → Sheets → e-mail klientovi → e-mail adminovi

---

Pokud chceš, můžu v dalším kroku rovnou připravit i jednoduchou „hardened“ verzi (bezpečnější přihlášení, CSRF token a konfiguraci přes `.env`).
