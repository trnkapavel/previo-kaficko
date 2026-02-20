# Nasazení Previo PMS na WordPress

## Přehled situace

Projekt je React SPA stavěný Vitem. WordPress nativně React aplikace nespouští, proto jsou dvě možnosti: vložení přes iframe (jednodušší) nebo přes shortcode + přímé vložení souborů (hlubší integrace).

---

## Možnost A — Iframe (doporučeno pro prototypy)

Tato metoda je nejjednodušší a nevyžaduje žádné úpravy WordPress šablony.

### 1. Připrav build aplikace

Na svém počítači nebo CI serveru:

```bash
git clone https://github.com/trnkapavel/previo-pms.git
cd previo-pms
npm install
npm run build
```

Výstup je ve složce `dist/`.

### 2. Nahraj soubory na hosting

Celý obsah složky `dist/` nahraj na FTP/SFTP do adresáře na svém hostingu, například:

```
/public_html/previo-app/
```

Aplikace pak bude dostupná na adrese: `https://tvujweb.cz/previo-app/`

### 3. Uprav vite.config.ts před buildem

Protože aplikace nebude na rootu domény, je potřeba nastavit base cestu před buildem:

```ts
// vite.config.ts
export default defineConfig({
  base: '/previo-app/',
  // ...zbytek konfigurace
})
```

Poté znovu spusť `npm run build` a nahraj.

### 4. Vlož iframe do WordPress stránky

V editoru WordPress (Gutenberg) přidej blok **Vlastní HTML** a vlož:

```html
<iframe
  src="https://tvujweb.cz/previo-app/"
  width="100%"
  height="900px"
  frameborder="0"
  style="border: none; width: 100%;">
</iframe>
```

---

## Možnost B — Shortcode (přímé vložení do WP)

Tato metoda načítá JS a CSS přímo do stránky WordPress bez iframe.

### 1. Proveď build

Stejně jako výše, ale `base: '/'` nebo relativní cesta.

### 2. Nahraj `dist/assets/` do WordPress

Nahraj obsah `dist/assets/` do:

```
/wp-content/uploads/previo-app/assets/
```

### 3. Přidej kód do functions.php svého tématu

```php
function previo_pms_shortcode() {
    // Načti CSS a JS ze složky s aplikací
    wp_enqueue_style(
        'previo-pms-style',
        get_template_directory_uri() . '/previo-app/assets/index.css'
    );
    wp_enqueue_script(
        'previo-pms-script',
        get_template_directory_uri() . '/previo-app/assets/index.js',
        array(),
        null,
        true
    );

    return '<div id="root"></div>';
}
add_shortcode('previo_pms', 'previo_pms_shortcode');
```

> Soubory `index.css` a `index.js` mají v `dist/assets/` hash v názvu (např. `index-Abc123.js`) — název uprav podle skutečného výstupu buildu.

### 4. Vlož shortcode na stránku

```
[previo_pms]
```

---

## Možné problémy a řešení

### React Router 404

Pokud aplikace používá React Router s history mode, je potřeba nastavit `.htaccess` přesměrování. Přidej do `.htaccess` ve složce aplikace:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /previo-app/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /previo-app/index.html [L]
</IfModule>
```

### Konflikty CSS

Tailwind CSS může kolidovat se styly WordPress tématu. Iframe (Možnost A) tento problém eliminuje, protože je izolovaný.

### Výkon

Vite build je optimalizovaný (code splitting, minifikace), takže výkon nebude problém.

---

## Doporučení

Pro prototyp (sdílení UI konceptu v týmu) je **Možnost A s iframe** jednoznačně nejrychlejší a nejbezpečnější volba — nepotřebuješ zasahovat do WordPress kódu a nehrozí konflikty.
