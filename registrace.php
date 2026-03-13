<?php
// Stránka pro registraci – přesměruje na správnou landing stránku
// a posune návštěvníka na sekci #registrace.

// Předvyplnění slotu (ranní/odpolední) pro výběr typu účasti
$prefill_slot = isset($_GET['slot']) ? $_GET['slot'] : null;

// Indikace pro landing šablonu, že se má po načtení srolovat na sekci registrace
$scroll_to_registration = true;

// Podle slotu zvolíme dopolední / odpolední landing
if ($prefill_slot === 'afternoon') {
    include __DIR__ . '/akce-odpoledne.php';
} else {
    // default = ranní varianta
    include __DIR__ . '/akce-rano.php';
}

?>
