# WordPress deployment návod (kaficko)

Tento návod je pro WordPress administrátora/developera, který chce rychle nasadit registrační aplikaci z tohoto repozitáře.

## Cíl nasazení

- WordPress zůstává hlavní web
- Registrační aplikace běží jako samostatná PHP mini-aplikace
- WordPress na ni pouze odkazuje (případně ji embeduje přes iframe)

---

## Doporučená architektura

### Varianta A (doporučeno): subdoména

- `www.mojedomena.cz` = WordPress
- `events.mojedomena.cz` = tato aplikace

### Proč je to nejlepší varianta

- minimum konfliktů s WP cache pluginy
- jednodušší debugging
- snadný rollback
- čisté oddělení odpovědností

### Varianta B: podsložka

- `www.mojedomena.cz/meetup/`

Použij pouze pokud hosting/subdoména není k dispozici.

---

## Krok 1: Upload projektu

Nahraj obsah repozitáře na hosting tak, aby na cílové URL byly dostupné:

- `index.php`
- `admin.php`
- `process_registration.php`
- `download_ics.php`
- `data.json`
- složka `img/`

Po uploadu otevři cílovou URL a zkontroluj, že se stránka načte bez PHP chyby.

---

## Krok 2: Nastavení práv

Soubor `data.json` musí být zapisovatelný uživatelem webserveru.

Typický postup na Linux hostingu:

```bash
chmod 664 data.json
```

Pokud zapisování z `admin.php` nefunguje, ověř vlastníka souboru a uživatele PHP procesu.

---

## Krok 3: Konfigurace administrace

V souboru `admin.php` změň výchozí heslo (`previo`) na vlastní silné heslo.

Hledej tento blok:

```php
if ($_POST['password'] === 'previo') { // ZMĚŇTE HESLO
```

Doporučení:

- použij unikátní heslo jen pro tento projekt
- přidej HTTP Basic Auth nad `admin.php`, pokud to hosting umožňuje

---

## Krok 4: Konfigurace Google Sheets

V souboru `process_registration.php` nastav Apps Script endpoint do proměnné:

```php
$googleScriptUrl = 'https://script.google.com/macros/s/.../exec';
```

### Ověření

1. Vyplň testovací registraci.
2. Zkontroluj, že se záznam propsal do tabulky.
3. Pokud ne, ověř publikaci Apps Scriptu (Web App + práva přístupu).

---

## Krok 5: Konfigurace e-mailů

V souboru `process_registration.php` uprav:

- `$senderEmail`
- `$adminEmail`
- případně `Reply-To`

### Doručitelnost

Pro produkci doporučeno nastavit SPF/DKIM/DMARC pro doménu odesílatele.

---

## Krok 6: Propojení s WordPress

### Varianta 1: odkaz z tlačítka/menu (doporučeno)

- Vytvoř ve WordPressu tlačítko „Registrovat“
- Odkazuj na cílovou URL aplikace (subdoména nebo podsložka)

### Varianta 2: iframe embed

Použij jen pokud musí být registrace přímo uvnitř WP stránky.

Příklad HTML bloku ve WordPressu:

```html
<iframe
  src="https://events.mojedomena.cz/"
  width="100%"
  height="1400"
  style="border:0;"
  loading="lazy"
></iframe>
```

Poznámka: u iframe hlídej výšku a UX na mobilu.

---

## Krok 7: Test checklist před spuštěním

- načte se landing page
- funguje přihlášení do `admin.php`
- jde uložit změna v administraci
- registrace vrací úspěšnou hlášku
- data se zapíší do Google Sheets
- účastník dostane potvrzovací e-mail
- admin dostane notifikační e-mail
- stáhne se `.ics` z `download_ics.php`

---

## Troubleshooting

### `admin.php` neukládá změny

- zkontroluj práva k `data.json`
- otevři `test_data.php` a ověř čitelnost/dekódování JSON

### Formulář hlásí chybu serveru

- zkontroluj `process_registration.php`
- ověř dostupnost cURL extension
- ověř Apps Script URL (`.../exec`)

### E-maily nechodí

- ověř funkčnost `mail()` na hostingu
- zkontroluj spam složku
- nastav SPF/DKIM/DMARC

---

## Bezpečnostní minimum

Před produkcí minimálně:

- změň admin heslo
- zapni HTTPS
- omez přístup na `admin.php` (IP allowlist / Basic Auth)
- přidej monitoring logů

---

## Poznámka k GitHub Pages

`docs/index.html` je jen statický preview soubor.
PHP backend (`process_registration.php`, `admin.php`) na GitHub Pages neběží.

---

## Související dokument

Pro finální kontrolu před ostrým spuštěním použij:

- `docs/GO-LIVE_CHECKLIST.md`
- `docs/HANDOVER_TEMPLATE.md`
