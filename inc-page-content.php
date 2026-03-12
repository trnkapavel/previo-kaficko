<?php
/**
 * Příprava editovatelných proměnných ze $data pro index, akce-rano, akce-odpoledne.
 * Před voláním musí být nastaveno: $data
 *
 * Postupně připravujeme plnou variantnost (ráno / odpoledne).
 * Pokud je nastaveno $variant = 'morning' nebo 'afternoon',
 * nejdřív se budou používat *_morning / *_afternoon klíče.
 */

if (!isset($variant)) {
    $variant = 'default';
}
$isMorning = ($variant === 'morning');
$isAfternoon = ($variant === 'afternoon');

// Stats (Tvoříme největší komunitu...)
$stats_intro = $data['stats_intro'] ?? 'Tvoříme největší komunitu hoteliérů v Česku. Přidejte se.';
// TODO (další krok): zavést stats_intro_morning / stats_intro_afternoon a tady je preferovat
$stats_items = $data['stats_items'] ?? [
    ['number' => '120+', 'label' => 'Zastávek za námi'],
    ['number' => '1 500+', 'label' => 'Vypitých káv s klienty ☕'],
    ['number' => '98 %', 'label' => 'Účastníků akci doporučuje'],
];
if (isset($data['stats_items']) && is_array($data['stats_items'])) {
    $stats_items = $data['stats_items'];
}

// Obsah akce (sekce #proc)
$content_tag = $data['content_tag'] ?? 'Obsah akce';
$content_title = $data['content_title'] ?? 'Praktická témata pro růst ubytování';
$intro_items = $data['intro_items'] ?? [
    ['icon' => 'users', 'title' => 'Pro koho je akce určena?', 'text' => 'Pro majitele, provozní a recepční hotelů i penzionů. Ať už Previo používáte, nebo teprve hledáte inspiraci.'],
    ['icon' => 'zap', 'title' => 'Hlavní témata', 'text' => 'E-Turista bez stresu, Revenue Management v praxi, psychologie hosta na mobilu a reálné využití AI v hotelnictví.'],
];
if (isset($data['intro_items']) && is_array($data['intro_items'])) {
    $intro_items = $data['intro_items'];
}
$content_items = $data['content_items'] ?? [
    ['icon' => 'trending-up', 'title' => 'Růst tržeb & práce s cenou', 'text' => 'Ukážeme vám, jak reagovat na lokální festivaly a události. Naučíme vás pracovat s minimální délkou pobytu a dynamickou cenotvorbou.', 'bullets' => ['Lokální statistiky a průměrné ceny v regionu.', 'Strategie pro zvýšení přímých rezervací.']],
    ['icon' => 'file-check', 'title' => 'Legislativa bez vrásek (e‑Turista)', 'text' => 'Legislativní změny mohou být náročné. Ukážeme vám e‑Turistu jako příležitost k digitalizaci a zjednodušení administrativy.', 'bullets' => ['Aktuální briefing: co musíte splnit.', 'Automatizace hlášení: jak to systém vyřeší za vás.']],
    ['icon' => 'sparkles', 'title' => 'Budoucnost s AI a automatizací', 'text' => 'Automatizujte rutinní agendu. Cesta hosta začíná na mobilu – ukážeme si, jak efektivně využít AI nástroje v každodenním provozu.', 'bullets' => ['Psaní textů a reakcí na recenze pomocí AI.', 'Cesta moderního hosta: od vyhledávání po check‑out.']],
];
if (isset($data['content_items']) && is_array($data['content_items'])) {
    $content_items = $data['content_items'];
}

// Automatizace v praxi
$automation_tag = $data['automation_tag'] ?? 'Automatizace v praxi';
$automation_subtitle = $data['automation_subtitle'] ?? 'Vyzkoušejte naživo';
$automation_title = $data['automation_title'] ?? 'Automatizace v praxi';
$automation_text = $data['automation_text'] ?? 'Součástí programu jsou praktické ukázky fungování chytrých klik a samoobslužných kiosků.';
$automation_bullets = $data['automation_bullets'] ?? ['Integrace zámkových systémů (např. Smartkey klika)', 'Check‑in proces bez recepčního'];
if (isset($data['automation_bullets']) && is_array($data['automation_bullets'])) {
    $automation_bullets = $data['automation_bullets'];
}
$automation_images = $data['automation_images'] ?? ['chytre-zamky.png'];
$automation_image = is_array($automation_images) ? ($automation_images[0] ?? 'chytre-zamky.png') : $automation_images;
if (strpos($automation_image, 'img/') !== 0 && strpos($automation_image, '/') === false) {
    $automation_image = 'img/' . $automation_image;
}

// Zastávky (použije se $stops z hlavního skriptu, zde jen pro konzistenci - index/akce už $stops mají)
if (!isset($stops)) {
    $stops = $data['stops'] ?? [];
}

// Řečníci
$speakers_tag = $data['speakers_tag'] ?? 'Řečníci';
$speakers_title = $data['speakers_title'] ?? 'Experti s praxí v oboru';
$speakers_default = [
    ['photo' => 'img/jiri-sindelar.jpeg', 'role' => 'Strategie & Trendy', 'name' => 'Jiří Šindelář', 'bio' => 'Head of Growth. Provází ranní částí. Jiří má přes 10 let praxe v digitalizaci ubytování.'],
    ['photo' => 'img/petr-mares.jpg', 'role' => 'Inovace & Automatizace', 'name' => 'Petr Mareš', 'bio' => 'Odpoledne prezentuje novinky. Vizionář v oblasti zámkových systémů a integrace AI.'],
    ['photo' => 'img/jana-vlkova.jpg', 'role' => 'Konzultace & Workshopy', 'name' => 'Jana V.', 'bio' => 'K dispozici na odpolední individuální konzultace. Řeší konkrétní problémy v nastavení Previa.'],
];
$speakers = (!empty($data['speakers']) && is_array($data['speakers'])) ? $data['speakers'] : $speakers_default;

// Registrace
$reg_tag = $data['reg_tag'] ?? 'Registrace';
$reg_title = $data['reg_title'] ?? 'Jak to funguje? Zajistěte si místo';
$reg_steps = $data['reg_steps'] ?? [
    ['icon' => '✍️', 'title' => 'Vyplnění přihlášky', 'text' => 'Vyplnění trvá přibližně jednu minutu.'],
    ['icon' => '✉️', 'title' => 'Potvrzovací e‑mail', 'text' => 'Potvrzení obdržíte e‑mailem.'],
    ['icon' => '📅', 'title' => 'Uložení do kalendáře', 'text' => 'Pro snadné připomenutí termínu.'],
];
if (isset($data['reg_steps']) && is_array($data['reg_steps'])) {
    $reg_steps = $data['reg_steps'];
}
$reg_form_title = $data['reg_form_title'] ?? 'Registrační formulář';
$reg_form_desc = $data['reg_form_desc'] ?? 'Kapacita je omezena. Vyplňte prosím pečlivě všechny údaje.';

// FAQ
$faq_tag = $data['faq_tag'] ?? 'Vše, co potřebujete vědět';
$faq_title = $data['faq_title'] ?? 'Časté dotazy';
$faq_items = $data['faq_items'] ?? [
    ['q' => 'Bude z akce záznam?', 'a' => 'Akce probíhá prezenčně. Videozáznam neplánujeme, po akci však zašleme materiály.'],
    ['q' => 'Mohu vzít kolegu?', 'a' => 'Ano. Každou osobu prosíme zaregistrovat samostatně z důvodu kapacity.'],
    ['q' => 'Kolik stojí vstupenka?', 'a' => 'Pro všechny registrované účastníky je vstup zdarma.'],
];
if (isset($data['faq_items']) && is_array($data['faq_items'])) {
    $faq_items = $data['faq_items'];
}
