# Vytvoření WordPress pluginu z Previo PMS

## Jak to bude fungovat

Plugin zaregistruje shortcode `[previo_pms]`, který na stránce vykreslí React aplikaci. Vite build se přibalí přímo do pluginu.

## Struktura pluginu

```
previo-pms-plugin/
├── previo-pms.php          ← hlavní soubor pluginu
├── assets/                 ← sem přijde výstup z npm run build
│   ├── index.js
│   └── index.css
└── build.sh                ← volitelný helper skript
```

---

## Krok 1 — Uprav vite.config.ts

Před buildem nastav výstupní složku a vypni hash v názvech souborů (aby název byl předvídatelný):

```ts
// vite.config.ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/wp-content/plugins/previo-pms-plugin/assets/',
  build: {
    outDir: 'dist',
    rollupOptions: {
      output: {
        entryFileNames: 'index.js',
        chunkFileNames: 'chunk-[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) return 'index.css'
          return '[name][extname]'
        },
      },
    },
  },
})
```

---

## Krok 2 — Spusť build

```bash
npm install
npm run build
```

Zkopíruj obsah `dist/assets/` do složky pluginu `previo-pms-plugin/assets/`.

---

## Krok 3 — Vytvoř hlavní PHP soubor pluginu

```php
<?php
/**
 * Plugin Name: Previo PMS
 * Description: Kalendář rezervací Previo PMS jako React aplikace.
 * Version: 1.0.0
 * Author: Pavel Trnka
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PrevioPMS {

    public function __construct() {
        add_shortcode( 'previo_pms', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
    }

    public function maybe_enqueue_assets() {
        // Načte assets jen na stránkách kde je shortcode
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'previo_pms' ) ) {
            $this->enqueue_assets();
        }
    }

    private function enqueue_assets() {
        $plugin_url = plugin_dir_url( __FILE__ );
        $plugin_path = plugin_dir_path( __FILE__ );

        wp_enqueue_script(
            'previo-pms-js',
            $plugin_url . 'assets/index.js',
            [],
            filemtime( $plugin_path . 'assets/index.js' ),
            true  // načíst v </body>
        );

        wp_enqueue_style(
            'previo-pms-css',
            $plugin_url . 'assets/index.css',
            [],
            filemtime( $plugin_path . 'assets/index.css' )
        );
    }

    public function render_shortcode( $atts ) {
        return '<div id="root" style="width:100%; min-height:600px;"></div>';
    }
}

new PrevioPMS();
```

---

## Krok 4 — Nainstaluj plugin do WordPressu

Zazipuj složku `previo-pms-plugin/` a nahraj přes **Pluginy → Přidat nový → Nahrát plugin**, nebo celou složku zkopíruj přímo na server do `/wp-content/plugins/`.

Pak plugin aktivuj v administraci.

---

## Krok 5 — Použití

Na libovolné stránce nebo příspěvku vlož shortcode:

```
[previo_pms]
```

---

## Potenciální problémy

### Konflikty CSS s tématem
Tailwind generuje globální styly, které mohou rozhodit WordPress téma. Řešení je buď použít iframe variantu shortcodu, nebo do Tailwind configu přidat prefix, aby se styly neaplikovaly globálně.

### React `#root` div
Pokud má téma vlastní `#root` element, může dojít ke kolizi. Lze změnit v `src/main.tsx` na jiné ID, např. `#previo-pms-root`, a podle toho upravit `render_shortcode`.

### Subdirectory WordPress instalace
Pokud WordPress není na rootu domény, je potřeba upravit `base` ve `vite.config.ts` podle skutečné cesty.

---

## Automatizace (volitelné)

Workflow soubor je připraven v `docs/build-plugin.yml`. Zkopíruj ho do repozitáře `previo-pms` jako `.github/workflows/build-plugin.yml`.

Co workflow dělá:
- spustí se automaticky po každém push na `main` i při otagování verze
- nainstaluje závislosti a spustí `npm run build`
- zkopíruje výstup do struktury pluginu
- zabalí plugin do `previo-pms-plugin.zip`
- zip nahraje jako GitHub Actions artifact (dostupný ke stažení 30 dní)
- při tagu (`v*`) automaticky vytvoří GitHub Release a přiloží zip

### Vytvoření release

```bash
git tag v1.0.0
git push origin v1.0.0
```

Release s přiloženým `previo-pms-plugin.zip` bude automaticky dostupný na záložce **Releases** v repozitáři.
