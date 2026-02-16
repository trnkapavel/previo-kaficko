# Previo MeetUp - Registrační systém pro roadshow

Webová aplikace pro správu a registraci účastníků na sérii offline setkání **Previo MeetUp** - roadshow po městech ČR zaměřená na hoteliéry a provozovatele ubytování.

## Co aplikace umí

### Landing page (`index.php`)
- **Hero sekce** s rotujícím sliderem pozadí a CTA tlačítkem
- **Statistiky** komunity (120+ zastávek, 1 500+ káv, 98 % spokojenost)
- **Informační bloky** - co se účastníci dozvědí (revenue management, e-Turista, AI)
- **Program** se dvěma záložkami:
  - Dopoledne: **Connect** (pro nehoteliéry / neklienty Previa)
  - Odpoledne: **PRO/LITE** (pro stávající klienty)
- **Profily řečníků** (Jiří Šindelář, Petr Mareš, Jana V.)
- **Registrační formulář** s AJAXovým odesláním
- **FAQ sekce** s nejčastějšími dotazy
- **Newsletter** přihlášení
- **Pill bar** - fixní lišta s progress barem obsazenosti a CTA
- **Modální okno** po úspěšné registraci s možností uložit do kalendáře (Google / Outlook)
- Plně **responzivní design** (dark mode hero, světlý obsah)
- **Scroll reveal animace**

### Administrace (`admin.php`)
- Přihlášení heslem
- Editace údajů o akci (město, datum, čas, místo konání)
- Správa kapacity (celková / obsazeno)
- Editace promo textů (hero nadpis, podnadpis)
- Editace programu obou bloků (formát: `Čas | Nadpis | Popis`)
- Data se ukládají do `data.json`

### Registrace (`process_registration.php`)
- Zpracování formuláře (jméno, hotel, e-mail, telefon, typ účasti, dieta, dotaz)
- Odeslání dat do **Google Sheets** přes Google Apps Script
- **HTML potvrzovací e-mail** účastníkovi s detaily akce
- **Notifikační e-mail** adminovi (plain text)
- Logování chyb do `error_log.txt`

### Kalendářová pozvánka (`download_ics.php`)
- Generování `.ics` souboru pro import do Outlook / Apple Calendar

## Technologie

- **PHP** (backend, šablonování, e-maily)
- **Vanilla JavaScript** (slider, animace, AJAX formulář, záložky)
- **CSS** (custom properties, grid, flexbox, animace)
- **Google Fonts** (Inter, Poppins)
- **Google Apps Script** (integrace s Google Sheets)

## Struktura projektu

```
kaficko/
├── index.php                 # Hlavní landing page
├── admin.php                 # Administrační panel
├── process_registration.php  # Zpracování registrace
├── download_ics.php          # Generování ICS pozvánky
├── data.json                 # Konfigurace akce (město, datum, program)
├── img/                      # Obrázky (řečníci, lokace, logo)
└── docs/                     # Statická verze pro GitHub Pages
    └── index.html            # Preview bez PHP
```

## Spuštění (lokální vývoj)

Aplikace vyžaduje PHP server (MAMP, XAMPP, nebo `php -S localhost:8000`).

```bash
cd kaficko
php -S localhost:8000
```

Poté otevřete `http://localhost:8000` v prohlížeči.

## GitHub Pages

Statická verze pro vizuální náhled je dostupná ve složce `docs/`. Slouží pouze k prezentaci designu - registrační formulář a admin panel nejsou funkční.

## Konfigurace

### data.json
Obsahuje veškerá editovatelná data akce - město, datum, místo, kapacitu, promo texty a program obou bloků. Edituje se přes admin panel nebo přímo.

### process_registration.php
- Nastavte URL Google Apps Scriptu pro zápis do Google Sheets
- Nastavte e-mailové adresy odesílatele a admina
