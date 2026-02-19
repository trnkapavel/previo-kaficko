# Google Chat integrace

Tento projekt podporuje notifikace do Google Chatu ve 2 režimech:

1. GitHub notifikace (push/release) přes GitHub Actions
2. Aplikační notifikace nové registrace z `process_registration.php`

## 1) GitHub notifikace (push/release)

Workflow je v souboru:

- `.github/workflows/google-chat-notify.yml`

### Nastavení

1. V Google Chatu vytvořte `Incoming Webhook` v cílovém Space.
2. V GitHub repozitáři nastavte secret:
   - `Settings` → `Secrets and variables` → `Actions` → `New repository secret`
   - Název: `GOOGLE_CHAT_WEBHOOK_URL`
   - Hodnota: webhook URL z Google Chatu

Po nastavení se budou posílat notifikace při:

- push na `main`
- publikaci release

## 2) Notifikace nové registrace z aplikace

Soubor `process_registration.php` odesílá zprávu do Google Chatu, pokud je dostupná env proměnná:

- `GOOGLE_CHAT_WEBHOOK_URL`

### Co se posílá

- město akce
- jméno
- hotel
- typ účasti

### Nastavení na hostingu

Nastavte env proměnnou `GOOGLE_CHAT_WEBHOOK_URL` podle hostingu:

- Apache/Nginx + FPM: přes konfiguraci virtuálního hostu / poolu
- PaaS (např. Render, Railway, Heroku): v sekci environment variables

Pokud proměnná není nastavená, aplikace běží standardně bez chat notifikací.

## Rychlý test webhooku

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"text":"✅ Test notifikace z projektu previo-kaficko"}' \
  "VAŠE_GOOGLE_CHAT_WEBHOOK_URL"
```

## Bezpečnostní doporučení

- webhook URL neukládejte přímo do kódu
- používejte pouze `Secrets` (GitHub) nebo env proměnné (server)
- při úniku webhook URL vytvořte nový webhook a starý zneplatněte
