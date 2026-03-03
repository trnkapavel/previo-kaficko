<?php
// Stránka pro registraci – používá stejnou šablonu jako landing
$landing_config = [
    'variant' => 'registration',
    'page_title_suffix' => ' | Registrace',
];

// Předvyplnění slotu (ranní/odpolední) pro výběr typu účasti
$prefill_slot = isset($_GET['slot']) ? $_GET['slot'] : null;

// Indikace, že se má po načtení stránky přesunout na sekci registrace
$scroll_to_registration = true;

include __DIR__ . '/index.php';

