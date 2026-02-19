# GO-LIVE checklist (produkční spuštění)

Krátký checklist před ostrým spuštěním registrační stránky.

## Aktuální URL (teď nastaveno)

- Veřejná stránka: http://localhost:8888/kaficko/
- Administrace: http://localhost:8888/kaficko/admin.php

Poznámka: jde o lokální adresu pro test. Před ostrým spuštěním nahraď produkční doménou.

## 1) Přístupy a bezpečnost

- [ ] V `admin.php` je změněné výchozí heslo (`previo`) na silné unikátní heslo.
- [ ] `admin.php` je chráněné navíc (Basic Auth, IP allowlist nebo obojí).
- [ ] Web běží přes HTTPS (platný certifikát).
- [ ] Veřejně dostupné jsou jen nutné soubory.

## 2) Konfigurace aplikace

- [ ] `process_registration.php` má správně nastavené `$googleScriptUrl` (`.../exec`).
- [ ] `process_registration.php` má správně nastavené `$senderEmail`.
- [ ] `process_registration.php` má správně nastavené `$adminEmail`.
- [ ] `data.json` má aktuální město, datum, čas, venue, kapacitu a program.
- [ ] `data.json` je zapisovatelný webserverem.

## 3) Funkční testy (před spuštěním)

- [ ] Načte se veřejná stránka bez PHP warning/error.
- [ ] Přihlášení do `admin.php` funguje.
- [ ] Uložení změny v administraci se zapíše do `data.json`.
- [ ] Test registrace vrátí úspěšnou hlášku ve formuláři.
- [ ] Registrace se zapíše do Google Sheets.
- [ ] Účastník dostane potvrzovací e-mail.
- [ ] Admin dostane notifikační e-mail.
- [ ] Odkaz na `download_ics.php` stáhne platný `.ics` soubor.

## 4) WordPress integrace

- [ ] Ve WordPressu je nasazený odkaz na registrační URL (menu/tlačítko).
- [ ] Odkaz je otestovaný na desktopu i mobilu.
- [ ] Pokud je aktivní cache/WAF, `process_registration.php` má výjimku.
- [ ] Pokud je použitý iframe, je správně nastavená výška a UX na mobilu.

## 5) Monitoring po spuštění (prvních 48 hodin)

- [ ] Ověřen první reálný zápis do Google Sheets.
- [ ] Ověřeno doručení prvních reálných e-mailů.
- [ ] Kontrola logů hostingu / PHP error logu.
- [ ] Kontrola, že kapacita a čísla v obsahu sedí.

## 6) Rollback plán

- [ ] Je připravená poslední funkční verze souborů k rychlému návratu.
- [ ] Je určený kontakt, kdo řeší incident (tech + business).
- [ ] Je jasný komunikační postup při výpadku formuláře.

---

## Rychlý smoke test (2 minuty)

1. Otevři veřejnou stránku a pošli test registraci.
2. Zkontroluj Google Sheets + oba e-maily.
3. Přihlas se do administrace a uprav jeden text.
4. Ověř stažení `.ics`.
5. Zkontroluj odkaz z WordPressu.

Pokud všech 5 kroků projde, můžeš bezpečně spustit kampaň.
