<?php
$slot = (isset($_GET['slot']) && $_GET['slot'] === 'odpoledne') ? 'odpoledne' : 'rano';

$data_file        = ($slot === 'odpoledne') ? 'data-odpoledne.json' : 'data-rano.json';
$slot_label       = ($slot === 'odpoledne') ? '🌇 Odpoledne (odpolední MeetUp)' : '🌅 Ráno (dopolední MeetUp)';
$admin_url        = 'admin.php?slot=' . $slot;
$program_subtitle = ($slot === 'odpoledne') ? 'Odpoledne: PRO/LITE (pro klienty)' : 'Dopoledne: Connect (pro neklienty)';

$switch_url_rano      = 'admin.php';
$switch_url_odpoledne = 'admin.php?slot=odpoledne';

require __DIR__ . '/admin-core.php';
