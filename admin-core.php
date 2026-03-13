<?php
/**
 * Sdílená admin logika.
 * Před includováním musí být nastaveno:
 *   $data_file        – např. 'data-rano.json'
 *   $slot             – 'rano' nebo 'odpoledne' (předpony názvů souborů)
 *   $slot_label       – titulek v adminu
 *   $admin_url        – URL aktuálního adminu (pro add/remove GET akce)
 *   $program_subtitle – popis programové sekce
 *   $switch_url_rano      (volitelné) – URL pro přepnutí na ranní admin
 *   $switch_url_odpoledne (volitelné) – URL pro přepnutí na odpolední admin
 */
$switch_url_rano      = $switch_url_rano      ?? 'admin-rano.php';
$switch_url_odpoledne = $switch_url_odpoledne ?? 'admin-odpoledne.php';

// Absolutní cesta k datovému souboru – relativní cesta selhává na některých serverech
$data_file = __DIR__ . '/' . ltrim($data_file, '/');

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ── Pomocné funkce ────────────────────────────────────────────────────────────

function parseProgram($text) {
    $lines = explode("\n", trim($text));
    $program = [];
    foreach ($lines as $line) {
        $parts = explode("|", $line);
        if (count($parts) >= 2) {
            $program[] = [
                'time'  => trim($parts[0]),
                'title' => trim($parts[1]),
                'desc'  => isset($parts[2]) ? trim($parts[2]) : '',
            ];
        }
    }
    return $program;
}

function formatProgram($array) {
    $text = '';
    if (is_array($array)) {
        foreach ($array as $item) {
            $text .= ($item['time'] ?? '') . ' | ' . ($item['title'] ?? '') . ' | ' . ($item['desc'] ?? '') . "\n";
        }
    }
    return trim($text);
}

function parseStatsItems($text) {
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $p    = explode('|', $line, 2);
        $out[] = ['number' => trim($p[0] ?? ''), 'label' => trim($p[1] ?? '')];
    }
    return $out;
}

function parseIntroItems($text) {
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $p    = explode('|', $line, 2);
        $out[] = ['title' => trim($p[0] ?? ''), 'text' => trim($p[1] ?? '')];
    }
    return $out;
}

function parseFaqItems($text) {
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $p    = explode('|', $line, 2);
        $out[] = ['q' => trim($p[0] ?? ''), 'a' => trim($p[1] ?? '')];
    }
    return $out;
}

function parseBullets($text) {
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
        $line = trim($line);
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

function uploadImage($fileKey, $prefix, $uploadDir, &$error) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmpName = $_FILES[$fileKey]['tmp_name'];
    $mime    = mime_content_type($tmpName);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        $error = "Neplatný formát obrázku. Povolené jsou JPG, PNG, WEBP.";
        return null;
    }
    $ext      = $allowed[$mime];
    $fileName = $prefix . time() . '.' . $ext;
    $target   = $uploadDir . $fileName;
    if (!move_uploaded_file($tmpName, $target)) {
        $error = "Nepodařilo se uložit obrázek '$fileName'.";
        return null;
    }
    return 'img/' . $fileName;
}

function uploadImageMulti($fileKey, $index, $prefix, $uploadDir, &$error) {
    if (!isset($_FILES[$fileKey]['tmp_name'][$index])) return null;
    if ($_FILES[$fileKey]['error'][$index] !== UPLOAD_ERR_OK) return null;
    $tmpName = $_FILES[$fileKey]['tmp_name'][$index];
    $mime    = @mime_content_type($tmpName);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$mime || !isset($allowed[$mime])) return null;
    $ext      = $allowed[$mime];
    $fileName = $prefix . $index . '-' . time() . '.' . $ext;
    $target   = $uploadDir . $fileName;
    if (!move_uploaded_file($tmpName, $target)) return null;
    return 'img/' . $fileName;
}

// ── Přihlášení ────────────────────────────────────────────────────────────────

if (isset($_POST['login'])) {
    if ($_POST['password'] === 'previo') { // ZMĚŇTE HESLO
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Zadané heslo není správné.";
    }
}

// ── Načtení dat (pro add/remove) ──────────────────────────────────────────────

if (!isset($data)) {
    $json_content = file_exists($data_file) ? file_get_contents($data_file) : '{}';
    $data = json_decode($json_content, true);
    if (!$data) $data = [];
}

// ── Helper: uložit data a přesměrovat ────────────────────────────────────────

function saveAndRedirect(array $data, string $data_file, string $url): void {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($data_file, $json, LOCK_EX) === false) {
        // Zápis selhal – zobraz chybu místo přesměrování
        session_write_close();
        die('<div style="font-family:sans-serif;padding:30px;background:#fff0f0;color:#7f1d1d;border:1px solid #fca5a5;border-radius:8px;margin:20px;">'
          . '<strong>❌ Chyba zápisu</strong><br>'
          . 'Nepodařilo se uložit soubor <code>' . htmlspecialchars($data_file) . '</code>.<br>'
          . 'Zkontroluj oprávnění k zápisu (chmod 664) na serveru.<br><br>'
          . '<a href="' . htmlspecialchars($url) . '">← Zpět</a></div>');
    }
    header('Location: ' . $url);
    exit;
}

// ── GET akce: přidat / odebrat zastávku ───────────────────────────────────────

if (isset($_SESSION['logged_in']) && isset($_GET['add_stop'])) {
    $data['stops'][] = ['city' => '', 'date' => '', 'time_from' => '', 'time_to' => '', 'title' => '', 'badges' => [], 'description' => ''];
    saveAndRedirect($data, $data_file, $admin_url . '#section-stops');
}
if (isset($_SESSION['logged_in']) && isset($_GET['remove_stop']) && is_numeric($_GET['remove_stop'])) {
    $i = (int)$_GET['remove_stop'];
    if (isset($data['stops'][$i])) array_splice($data['stops'], $i, 1);
    saveAndRedirect($data, $data_file, $admin_url . '#section-stops');
}

// ── GET akce: přidat / odebrat řečníka ───────────────────────────────────────

if (isset($_SESSION['logged_in']) && isset($_GET['add_speaker'])) {
    $data['speakers'][] = ['photo' => '', 'role' => '', 'name' => '', 'bio' => ''];
    saveAndRedirect($data, $data_file, $admin_url . '#section-speakers');
}
if (isset($_SESSION['logged_in']) && isset($_GET['remove_speaker']) && is_numeric($_GET['remove_speaker'])) {
    $i = (int)$_GET['remove_speaker'];
    if (isset($data['speakers'][$i])) array_splice($data['speakers'], $i, 1);
    saveAndRedirect($data, $data_file, $admin_url . '#section-speakers');
}

// ── GET akce: přidat / odebrat proběhlou akci ────────────────────────────────

if (isset($_SESSION['logged_in']) && isset($_GET['add_past'])) {
    $data['past_events'][] = ['image' => '', 'date' => '', 'place' => ''];
    saveAndRedirect($data, $data_file, $admin_url . '#section-past');
}
if (isset($_SESSION['logged_in']) && isset($_GET['remove_past']) && is_numeric($_GET['remove_past'])) {
    $i = (int)$_GET['remove_past'];
    if (isset($data['past_events'][$i])) array_splice($data['past_events'], $i, 1);
    saveAndRedirect($data, $data_file, $admin_url . '#section-past');
}

// ── POST: uložení dat ─────────────────────────────────────────────────────────

if (isset($_POST['save']) && isset($_SESSION['logged_in'])) {
    $existing  = $data ?? [];
    $uploadDir = __DIR__ . '/img/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);

    // Hero obrázky (přidat do galerie)
    $heroImages = $existing['hero_images'] ?? [];
    if (!is_array($heroImages)) $heroImages = $heroImages ? [$heroImages] : [];
    $newHero = uploadImage('hero_image', 'hero-' . $slot . '-', $uploadDir, $error);
    if ($newHero) $heroImages[] = $newHero;

    // Automatizace – obrázek
    $automationImagePath = $existing['automation_images'][0] ?? 'chytre-zamky.png';
    $newAuto = uploadImage('automation_image', 'automation-' . $slot . '-', $uploadDir, $error);
    if ($newAuto) $automationImagePath = $newAuto;

    // Řečníci
    $speakersSaved = [];
    if (isset($_POST['speakers_index']) && is_array($_POST['speakers_index'])) {
        foreach ($_POST['speakers_index'] as $idx => $_) {
            $photo = trim($existing['speakers'][$idx]['photo'] ?? '');
            $newPhoto = uploadImageMulti('speakers_photo', $idx, 'speaker-' . $slot . '-', $uploadDir, $error);
            if ($newPhoto) $photo = $newPhoto;
            $speakersSaved[] = [
                'photo' => $photo,
                'role'  => trim($_POST['speakers_role'][$idx] ?? ''),
                'name'  => trim($_POST['speakers_name'][$idx] ?? ''),
                'bio'   => trim($_POST['speakers_bio'][$idx] ?? ''),
            ];
        }
    }
    if (empty($speakersSaved) && !empty($existing['speakers'])) {
        $speakersSaved = $existing['speakers'];
    }

    // Zastávky
    $stopsSaved = [];
    if (isset($_POST['stops_date']) && is_array($_POST['stops_date'])) {
        foreach ($_POST['stops_date'] as $i => $d) {
            $badgesStr  = trim($_POST['stops_badges'][$i] ?? '');
            $badges     = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $badgesStr))));
            $stopsSaved[] = [
                'city'        => trim($_POST['stops_city'][$i] ?? ''),
                'date'        => trim($d),
                'time_from'   => trim($_POST['stops_time_from'][$i] ?? ''),
                'time_to'     => trim($_POST['stops_time_to'][$i] ?? ''),
                'title'       => trim($_POST['stops_title'][$i] ?? ''),
                'badges'      => $badges,
                'description' => trim($_POST['stops_description'][$i] ?? ''),
            ];
        }
    }
    if (empty($stopsSaved) && !empty($existing['stops'])) $stopsSaved = $existing['stops'];

    // Proběhlé akce
    $pastSaved = [];
    $peDates   = $_POST['past_events_date'] ?? [];
    if (!is_array($peDates)) $peDates = [];
    for ($i = 0; $i < count($peDates); $i++) {
        $img = $existing['past_events'][$i]['image'] ?? '';
        $newImg = uploadImageMulti('past_events_photo', $i, 'past-' . $slot . '-', $uploadDir, $error);
        if ($newImg) $img = $newImg;
        $pastSaved[] = [
            'image' => $img,
            'date'  => trim($peDates[$i] ?? ''),
            'place' => trim($_POST['past_events_place'][$i] ?? ''),
        ];
    }

    // Reg kroky
    $regStepsSaved = [];
    for ($r = 1; $r <= 3; $r++) {
        $regStepsSaved[] = [
            'icon'  => trim($_POST["reg_step{$r}_icon"]  ?? ''),
            'title' => trim($_POST["reg_step{$r}_title"] ?? ''),
            'text'  => trim($_POST["reg_step{$r}_text"]  ?? ''),
        ];
    }

    // Content items (3 boxy)
    $contentItemsSaved = [];
    for ($b = 0; $b < 3; $b++) {
        $contentItemsSaved[] = [
            'icon'    => trim($_POST["content_icon_{$b}"]    ?? ''),
            'title'   => trim($_POST["content_title_{$b}"]   ?? ''),
            'text'    => trim($_POST["content_text_{$b}"]    ?? ''),
            'bullets' => parseBullets($_POST["content_bullets_{$b}"] ?? ''),
        ];
    }

    // Sestavení nových dat
    $newData = [
        'city'               => trim($_POST['city'] ?? ''),
        'date'               => trim($_POST['date'] ?? ''),
        'time'               => trim($_POST['time'] ?? ''),
        'venue'              => trim($_POST['venue'] ?? ''),
        'capacity'           => (int)($_POST['capacity'] ?? 0),
        'registered'         => (int)($_POST['registered'] ?? 0),
        'promo_title'        => trim($_POST['promo_title'] ?? ''),
        'promo_text'         => trim($_POST['promo_text'] ?? ''),
        'hero_title'         => trim($_POST['hero_title'] ?? ($existing['hero_title'] ?? '')),
        'hero_text'          => trim($_POST['hero_text'] ?? ($existing['hero_text'] ?? '')),
        'hero_images'        => $heroImages,
        'program'            => parseProgram($_POST['program'] ?? ''),
        'stats_intro'        => trim($_POST['stats_intro'] ?? ''),
        'stats_items'        => parseStatsItems($_POST['stats_items'] ?? ''),
        'content_tag'        => trim($_POST['content_tag'] ?? ''),
        'content_title'      => trim($_POST['content_title'] ?? ''),
        'intro_items'        => parseIntroItems($_POST['intro_items'] ?? ''),
        'content_items'      => $contentItemsSaved,
        'automation_tag'     => trim($_POST['automation_tag'] ?? ''),
        'automation_subtitle'=> trim($_POST['automation_subtitle'] ?? ''),
        'automation_title'   => trim($_POST['automation_title'] ?? ''),
        'automation_text'    => trim($_POST['automation_text'] ?? ''),
        'automation_bullets' => parseBullets($_POST['automation_bullets'] ?? ''),
        'automation_images'  => [$automationImagePath],
        'stops'              => $stopsSaved,
        'speakers_tag'       => trim($_POST['speakers_tag'] ?? ''),
        'speakers_title'     => trim($_POST['speakers_title'] ?? ''),
        'speakers'           => $speakersSaved,
        'reg_tag'            => trim($_POST['reg_tag'] ?? ''),
        'reg_title'          => trim($_POST['reg_title'] ?? ''),
        'reg_steps'          => $regStepsSaved,
        'reg_form_title'     => trim($_POST['reg_form_title'] ?? ''),
        'reg_form_desc'      => trim($_POST['reg_form_desc'] ?? ''),
        'faq_tag'            => trim($_POST['faq_tag'] ?? ''),
        'faq_title'          => trim($_POST['faq_title'] ?? ''),
        'faq_items'          => parseFaqItems($_POST['faq_items'] ?? ''),
        'history_section_title' => trim($_POST['history_section_title'] ?? 'Proběhlé akce'),
        'past_events'        => $pastSaved,
    ];

    // Zachovat klíče, které admin neposílá
    foreach (['locations', 'history_locations', 'events'] as $keep) {
        if (isset($existing[$keep])) $newData[$keep] = $existing[$keep];
    }

    $jsonString = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonString === false) {
        $error = "Chyba při převodu dat do JSON: " . json_last_error_msg();
    } else {
        $writeResult = file_put_contents($data_file, $jsonString);
        if ($writeResult !== false) {
            $success = "✅ Data byla úspěšně uložena (" . $writeResult . " bytů).<br>"
                     . "Soubor: " . realpath($data_file) . "<br>"
                     . "Čas: " . date('d.m.Y H:i:s');
            clearstatcache();
            $data = json_decode(file_get_contents($data_file), true);
        } else {
            $error = "❌ Nepodařilo se uložit soubor '$data_file'. Zkontrolujte oprávnění k zápisu.";
        }
    }
}

// ── Načtení dat pro zobrazení ─────────────────────────────────────────────────

if (!isset($data) || !$data) {
    $json_content = file_exists($data_file) ? file_get_contents($data_file) : '{}';
    $data = json_decode($json_content, true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – <?= htmlspecialchars($slot_label) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #ffffff;
            --color-section: #f5f7fa;
            --color-text: #1f2937;
            --color-border: #e5e7eb;
            --color-primary: #B50000;
            --color-primary-dark: #900000;
            --radius-sm: 12px;
            --radius-md: 18px;
            --radius-pill: 999px;
            --space-page: 40px;
            --space-card: 40px;
            --space-field: 14px;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Source Sans 3', sans-serif; background: var(--color-section); padding: var(--space-page); color: var(--color-text); }
        .container { max-width: 860px; margin: 0 auto; background: var(--color-bg); padding: var(--space-card); border-radius: var(--radius-md); border: 1px solid var(--color-border); box-shadow: 0 10px 24px rgba(15,23,42,0.06); }
        h2, h3 { color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 10px; margin-top: 30px; }
        h2 { margin-top: 0; border-color: var(--color-primary); }
        label { font-weight: 600; font-size: 0.9rem; display: block; margin-top: 15px; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="password"], textarea {
            width: 100%; padding: var(--space-field); border: 1px solid var(--color-border);
            border-radius: var(--radius-sm); font-size: 1rem; font-family: inherit;
        }
        input[type="text"]:focus, input[type="number"]:focus, input[type="password"]:focus, textarea:focus {
            outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(181,0,0,0.12);
        }
        textarea { font-family: ui-monospace, monospace; height: 150px; line-height: 1.5; }
        button { background: var(--color-primary); color: white; border: 1px solid transparent; padding: 15px 30px;
            cursor: pointer; border-radius: var(--radius-pill); font-weight: 700; font-size: 1.05rem;
            width: 100%; margin-top: 30px; transition: all 0.25s ease; box-shadow: 0 10px 20px rgba(181,0,0,0.22); }
        button:hover { background: var(--color-primary-dark); transform: translateY(-1px); box-shadow: 0 14px 26px rgba(181,0,0,0.28); }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: var(--radius-sm); border: 1px solid transparent; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .hint { font-size: 0.9rem; color: #4b5563; margin-top: 5px; background: #fafafa; padding: 10px 12px;
            border: 1px solid var(--color-border); border-left: 3px solid #cbd5e1; border-radius: var(--radius-sm); }
        a { color: #4b5563; }
        .section-card { margin-top: 24px; padding: 22px 22px 24px; border-radius: var(--radius-md); background: #f9fafb; border: 1px solid var(--color-border); }
        .section-card h3 { margin-top: 0; border-bottom: none; padding-bottom: 0; }
        .section-card + .section-card { margin-top: 20px; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-top: 8px; }
        .gallery-grid img { width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--color-border); object-fit: cover; }
        .admin-switcher { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .admin-switcher a { padding: 12px 20px; border-radius: 8px; border: 2px solid #d1d5db; background: #fff;
            color: #374151; font-weight: 700; font-size: 1rem; text-decoration: none; transition: all 0.2s; }
        .admin-switcher a:hover { background: #f3f4f6; border-color: #9ca3af; }
        .admin-switcher a.active { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
        .section-nav { margin: 0 0 20px; padding: 10px 14px; background: #f9fafb; border-radius: var(--radius-sm);
            border: 1px solid var(--color-border); font-size: 0.9rem; display: flex; flex-wrap: wrap; gap: 6px 16px; }
        .section-nav a { text-decoration: none; font-weight: 600; color: var(--color-primary); }
        .section-nav a:hover { text-decoration: underline; }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px; }
        .btn-add { display: inline-block; background: var(--color-primary); color: white; padding: 8px 16px;
            border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .btn-remove { color: var(--color-primary); font-size: 0.9rem; text-decoration: none; }
        .inner-card { margin-top: 16px; padding: 16px; background: #fff; border: 1px solid var(--color-border); border-radius: 12px; }
        .inner-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        @media (max-width: 768px) { body { padding: 20px; } .container { padding: 24px; } .row-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">

    <h2>Administrace – <?= htmlspecialchars($slot_label) ?></h2>

    <?php if (!isset($_SESSION['logged_in'])): ?>
        <?php if (isset($error)) echo "<div class='alert error'>" . htmlspecialchars($error) . "</div>"; ?>
        <form method="POST">
            <label>Heslo:</label>
            <input type="password" name="password" required autofocus>
            <button type="submit" name="login">Přihlásit se</button>
        </form>

    <?php else: ?>

        <?php if (isset($success)) echo "<div class='alert success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert error'>" . htmlspecialchars($error) . "</div>"; ?>

        <div class="admin-switcher">
            <a href="<?= htmlspecialchars($switch_url_rano) ?>" class="<?= $slot === 'rano' ? 'active' : '' ?>">🌅 Akce ráno</a>
            <a href="<?= htmlspecialchars($switch_url_odpoledne) ?>" class="<?= $slot === 'odpoledne' ? 'active' : '' ?>">🌇 Akce odpoledne</a>
        </div>

        <div class="section-nav">
            <strong>Přejít na sekci:</strong>
            <a href="#section-basic">Základní údaje</a>
            <a href="#section-hero">Hero</a>
            <a href="#section-program">Program</a>
            <a href="#section-stats">Stats</a>
            <a href="#section-content">Obsah</a>
            <a href="#section-automation">Automatizace</a>
            <a href="#section-stops">Zastávky</a>
            <a href="#section-speakers">Řečníci</a>
            <a href="#section-reg">Registrace</a>
            <a href="#section-faq">FAQ</a>
            <a href="#section-past">Proběhlé akce</a>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <!-- ── Základní údaje ───────────────────────────────────────── -->
            <div class="section-card" id="section-basic">
                <h3>📍 Základní údaje</h3>
                <div class="row-2">
                    <div><label>Město</label><input type="text" name="city" value="<?= htmlspecialchars($data['city'] ?? '') ?>"></div>
                    <div><label>Datum</label><input type="text" name="date" value="<?= htmlspecialchars($data['date'] ?? '') ?>"></div>
                    <div><label>Čas</label><input type="text" name="time" value="<?= htmlspecialchars($data['time'] ?? '') ?>"></div>
                    <div><label>Místo (Hotel)</label><input type="text" name="venue" value="<?= htmlspecialchars($data['venue'] ?? '') ?>"></div>
                    <div><label>Celková kapacita</label><input type="number" name="capacity" value="<?= (int)($data['capacity'] ?? 50) ?>"></div>
                    <div><label>Obsazeno</label><input type="number" name="registered" value="<?= (int)($data['registered'] ?? 0) ?>"></div>
                </div>
                <label>Promo nadpis (fallback pro hero)</label>
                <input type="text" name="promo_title" value="<?= htmlspecialchars($data['promo_title'] ?? '') ?>">
                <label>Promo podnadpis (fallback)</label>
                <input type="text" name="promo_text" value="<?= htmlspecialchars($data['promo_text'] ?? '') ?>">
            </div>

            <!-- ── Hero ────────────────────────────────────────────────── -->
            <div class="section-card" id="section-hero">
                <h3>🖼️ Hero sekce</h3>
                <p class="hint">Nadpis a text hero sekce. Pokud jsou prázdné, použije se promo text výše.</p>
                <label>Nadpis (Hero)</label>
                <input type="text" name="hero_title" value="<?= htmlspecialchars($data['hero_title'] ?? '') ?>">
                <label>Podnadpis (Hero)</label>
                <input type="text" name="hero_text" value="<?= htmlspecialchars($data['hero_text'] ?? '') ?>">
                <label>Přidat obrázek do slideru</label>
                <input type="file" name="hero_image" accept="image/*">
                <?php
                    $gallery = $data['hero_images'] ?? [];
                    if (!is_array($gallery)) $gallery = $gallery ? [$gallery] : [];
                ?>
                <?php if (!empty($gallery)): ?>
                    <p class="hint" style="margin-top:10px;">Aktuální slider (<?= count($gallery) ?> obrázků):</p>
                    <div class="gallery-grid">
                        <?php foreach ($gallery as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Program ─────────────────────────────────────────────── -->
            <div class="section-card" id="section-program">
                <h3>📝 Program</h3>
                <p class="hint">Formát: <b>Čas | Nadpis | Popis</b> (každá položka na nový řádek)</p>
                <label><?= htmlspecialchars($program_subtitle) ?></label>
                <textarea name="program"><?= htmlspecialchars(formatProgram($data['program'] ?? [])) ?></textarea>
            </div>

            <!-- ── Stats ───────────────────────────────────────────────── -->
            <div class="section-card" id="section-stats">
                <h3>📊 Statistiky</h3>
                <label>Úvodní text</label>
                <input type="text" name="stats_intro" value="<?= htmlspecialchars($data['stats_intro'] ?? '') ?>">
                <label>Čísla a popisky (řádek: <b>číslo | popisek</b>)</label>
                <textarea name="stats_items" rows="5"><?php
                    foreach ($data['stats_items'] ?? [] as $s) {
                        echo htmlspecialchars(($s['number'] ?? '') . ' | ' . ($s['label'] ?? '') . "\n");
                    }
                ?></textarea>
            </div>

            <!-- ── Obsah akce ───────────────────────────────────────────── -->
            <div class="section-card" id="section-content">
                <h3>📋 Obsah akce</h3>
                <label>Tag sekce</label>
                <input type="text" name="content_tag" value="<?= htmlspecialchars($data['content_tag'] ?? 'Obsah akce') ?>">
                <label>Nadpis sekce</label>
                <input type="text" name="content_title" value="<?= htmlspecialchars($data['content_title'] ?? '') ?>">
                <label>Úvodní boxy (řádek: <b>nadpis | text</b>)</label>
                <textarea name="intro_items" rows="4"><?php
                    foreach ($data['intro_items'] ?? [] as $it) {
                        echo htmlspecialchars(($it['title'] ?? '') . ' | ' . ($it['text'] ?? '') . "\n");
                    }
                ?></textarea>
                <?php
                    $ci = $data['content_items'] ?? [];
                    for ($b = 0; $b < 3; $b++):
                        $box = $ci[$b] ?? ['icon' => '', 'title' => '', 'text' => '', 'bullets' => []];
                        $bulletsStr = implode("\n", (array)($box['bullets'] ?? []));
                ?>
                <h4 style="margin-top:20px;">Box <?= $b+1 ?> <small style="font-weight:400;color:#6b7280;">(icon: trending-up / file-check / sparkles / settings / …)</small></h4>
                <div class="row-2">
                    <div><label>Icon</label><input type="text" name="content_icon_<?= $b ?>" value="<?= htmlspecialchars($box['icon'] ?? '') ?>" placeholder="trending-up"></div>
                    <div><label>Nadpis</label><input type="text" name="content_title_<?= $b ?>" value="<?= htmlspecialchars($box['title'] ?? '') ?>"></div>
                </div>
                <label>Text</label>
                <textarea name="content_text_<?= $b ?>" rows="3"><?= htmlspecialchars($box['text'] ?? '') ?></textarea>
                <label>Odrážky (každá na nový řádek)</label>
                <textarea name="content_bullets_<?= $b ?>" rows="3"><?= htmlspecialchars($bulletsStr) ?></textarea>
                <?php endfor; ?>
            </div>

            <!-- ── Automatizace ─────────────────────────────────────────── -->
            <div class="section-card" id="section-automation">
                <h3>⚙️ Automatizace v praxi</h3>
                <div class="row-2">
                    <div><label>Tag</label><input type="text" name="automation_tag" value="<?= htmlspecialchars($data['automation_tag'] ?? '') ?>"></div>
                    <div><label>Podnadpis</label><input type="text" name="automation_subtitle" value="<?= htmlspecialchars($data['automation_subtitle'] ?? '') ?>"></div>
                </div>
                <label>Hlavní nadpis</label>
                <input type="text" name="automation_title" value="<?= htmlspecialchars($data['automation_title'] ?? '') ?>">
                <label>Odstavec</label>
                <textarea name="automation_text" rows="3"><?= htmlspecialchars($data['automation_text'] ?? '') ?></textarea>
                <label>Odrážky (každá na nový řádek)</label>
                <textarea name="automation_bullets" rows="4"><?= htmlspecialchars(implode("\n", $data['automation_bullets'] ?? [])) ?></textarea>
                <label>Obrázek (upload)</label>
                <input type="file" name="automation_image" accept="image/*">
                <?php if (!empty($data['automation_images'][0])): $aimg = $data['automation_images'][0]; if (strpos($aimg,'img/')!==0 && strpos($aimg,'/')===false) $aimg='img/'.$aimg; ?>
                    <p class="hint">Aktuálně: <img src="<?= htmlspecialchars($aimg) ?>" alt="" style="max-width:200px;height:auto;display:block;margin-top:8px;"></p>
                <?php endif; ?>
            </div>

            <!-- ── Zastávky ─────────────────────────────────────────────── -->
            <div class="section-card" id="section-stops">
                <h3>📍 Příští zastávky</h3>
                <p class="hint">Úpravy zastávek uložte tlačítkem „Uložit" níže.</p>
                <p><a href="<?= htmlspecialchars($admin_url) ?>?add_stop=1" class="btn-add">+ Přidat zastávku</a></p>
                <?php foreach ($data['stops'] ?? [] as $idx => $stop):
                    $badgesStr = is_array($stop['badges'] ?? []) ? implode(', ', $stop['badges']) : ''; ?>
                <div class="inner-card">
                    <div class="inner-card-header">
                        <strong>Zastávka <?= $idx+1 ?></strong>
                        <a href="<?= htmlspecialchars($admin_url) ?>?remove_stop=<?= $idx ?>" class="btn-remove" onclick="return confirm('Odebrat zastávku?');">Smazat</a>
                    </div>
                    <div class="row-2">
                        <div><label>Město</label><input type="text" name="stops_city[]" value="<?= htmlspecialchars($stop['city'] ?? '') ?>" placeholder="Praha"></div>
                        <div><label>Datum</label><input type="text" name="stops_date[]" value="<?= htmlspecialchars($stop['date'] ?? '') ?>" placeholder="25. března 2026"></div>
                        <div><label>Čas od</label><input type="text" name="stops_time_from[]" value="<?= htmlspecialchars($stop['time_from'] ?? '') ?>" placeholder="09:00"></div>
                        <div><label>Čas do</label><input type="text" name="stops_time_to[]" value="<?= htmlspecialchars($stop['time_to'] ?? '') ?>" placeholder="12:00"></div>
                        <div class="row-2" style="grid-column:1/-1;"><div><label>Název místa</label><input type="text" name="stops_title[]" value="<?= htmlspecialchars($stop['title'] ?? '') ?>" placeholder="Praha – Hotel XY"></div></div>
                    </div>
                    <label>Badge (oddělené čárkou)</label>
                    <input type="text" name="stops_badges[]" value="<?= htmlspecialchars($badgesStr) ?>">
                    <label>Popis</label>
                    <textarea name="stops_description[]" rows="2"><?= htmlspecialchars($stop['description'] ?? '') ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Řečníci ──────────────────────────────────────────────── -->
            <div class="section-card" id="section-speakers">
                <h3>🎤 Řečníci</h3>
                <div class="row-2">
                    <div><label>Tag sekce</label><input type="text" name="speakers_tag" value="<?= htmlspecialchars($data['speakers_tag'] ?? 'Řečníci') ?>"></div>
                    <div><label>Nadpis sekce</label><input type="text" name="speakers_title" value="<?= htmlspecialchars($data['speakers_title'] ?? 'Experti s praxí v oboru') ?>"></div>
                </div>
                <?php if (empty($data['speakers'])): ?>
                <p class="hint" style="margin-top:14px;">⚠️ Žádní řečníci nejsou přidáni – web zobrazuje výchozí obsah. Přidejte řečníky tlačítkem níže.</p>
                <?php endif; ?>
                <p style="margin-top:16px;"><a href="<?= htmlspecialchars($admin_url) ?>?add_speaker=1" class="btn-add">+ Přidat řečníka</a></p>
                <?php foreach ($data['speakers'] ?? [] as $idx => $sp): ?>
                <div class="inner-card">
                    <div class="inner-card-header">
                        <strong>Řečník <?= $idx+1 ?><?= !empty($sp['name']) ? ' – ' . htmlspecialchars($sp['name']) : '' ?></strong>
                        <a href="<?= htmlspecialchars($admin_url) ?>?remove_speaker=<?= $idx ?>" class="btn-remove" onclick="return confirm('Odebrat řečníka?');">Smazat</a>
                    </div>
                    <input type="hidden" name="speakers_index[]" value="<?= $idx ?>">
                    <div class="row-2">
                        <div>
                            <label>Jméno řečníka</label>
                            <input type="text" name="speakers_name[]" value="<?= htmlspecialchars($sp['name'] ?? '') ?>" placeholder="Jan Novák">
                        </div>
                        <div>
                            <label>Nadpis karty (zaměření)</label>
                            <input type="text" name="speakers_role[]" value="<?= htmlspecialchars($sp['role'] ?? '') ?>" placeholder="Strategie & Trendy">
                        </div>
                    </div>
                    <label>Na co se zaměřuje (popis)</label>
                    <textarea name="speakers_bio[]" rows="3" placeholder="Krátký popis řečníka a jeho specializace..."><?= htmlspecialchars($sp['bio'] ?? '') ?></textarea>
                    <label>Foto řečníka</label>
                    <input type="file" name="speakers_photo[]" accept="image/*">
                    <?php if (!empty($sp['photo'])): ?>
                        <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                            <img src="<?= htmlspecialchars($sp['photo']) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:50%;border:2px solid #e5e7eb;">
                            <span style="font-size:0.85rem;color:#6b7280;"><?= htmlspecialchars($sp['photo']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ── Registrace ───────────────────────────────────────────── -->
            <div class="section-card" id="section-reg">
                <h3>📝 Registrace</h3>
                <div class="row-2">
                    <div><label>Tag sekce</label><input type="text" name="reg_tag" value="<?= htmlspecialchars($data['reg_tag'] ?? 'Registrace') ?>"></div>
                    <div><label>Nadpis sekce</label><input type="text" name="reg_title" value="<?= htmlspecialchars($data['reg_title'] ?? '') ?>"></div>
                </div>
                <p class="hint" style="margin-top:16px;">3 kroky (ikona = emoji, např. ✍️ ✉️ 📅)</p>
                <?php
                    $rs = $data['reg_steps'] ?? [['icon'=>'','title'=>'','text'=>''],['icon'=>'','title'=>'','text'=>''],['icon'=>'','title'=>'','text'=>'']];
                    for ($r = 0; $r < 3; $r++):
                        $step = $rs[$r] ?? ['icon'=>'','title'=>'','text'=>''];
                ?>
                <div style="margin-top:12px; display:flex; gap:10px;">
                    <input type="text" name="reg_step<?= $r+1 ?>_icon" value="<?= htmlspecialchars($step['icon'] ?? '') ?>" placeholder="Emoji" style="width:70px;">
                    <input type="text" name="reg_step<?= $r+1 ?>_title" value="<?= htmlspecialchars($step['title'] ?? '') ?>" placeholder="Nadpis kroku" style="flex:1;">
                    <input type="text" name="reg_step<?= $r+1 ?>_text" value="<?= htmlspecialchars($step['text'] ?? '') ?>" placeholder="Popis" style="flex:2;">
                </div>
                <?php endfor; ?>
                <div class="row-2" style="margin-top:16px;">
                    <div><label>Nadpis formuláře</label><input type="text" name="reg_form_title" value="<?= htmlspecialchars($data['reg_form_title'] ?? '') ?>"></div>
                    <div><label>Popis pod nadpisem</label><input type="text" name="reg_form_desc" value="<?= htmlspecialchars($data['reg_form_desc'] ?? '') ?>"></div>
                </div>
            </div>

            <!-- ── FAQ ─────────────────────────────────────────────────── -->
            <div class="section-card" id="section-faq">
                <h3>❓ FAQ</h3>
                <div class="row-2">
                    <div><label>Tag</label><input type="text" name="faq_tag" value="<?= htmlspecialchars($data['faq_tag'] ?? '') ?>"></div>
                    <div><label>Nadpis</label><input type="text" name="faq_title" value="<?= htmlspecialchars($data['faq_title'] ?? 'Časté dotazy') ?>"></div>
                </div>
                <label>Položky (řádek: <b>otázka | odpověď</b>)</label>
                <textarea name="faq_items" rows="8"><?php
                    foreach ($data['faq_items'] ?? [] as $f) {
                        echo htmlspecialchars(($f['q'] ?? '') . ' | ' . ($f['a'] ?? '') . "\n");
                    }
                ?></textarea>
                <label style="margin-top:24px;">Nadpis sekce „Proběhlé akce"</label>
                <input type="text" name="history_section_title" value="<?= htmlspecialchars($data['history_section_title'] ?? 'Proběhlé akce') ?>">
            </div>

            <!-- ── Proběhlé akce ────────────────────────────────────────── -->
            <div class="section-card" id="section-past">
                <h3>📅 Proběhlé akce</h3>
                <p class="hint">Karty se zobrazí v sekci historie na stránce akce.</p>
                <p><a href="<?= htmlspecialchars($admin_url) ?>?add_past=1" class="btn-add">+ Přidat proběhlou akci</a></p>
                <?php foreach ($data['past_events'] ?? [] as $pi => $pe):
                    $peImg = $pe['image'] ?? '';
                    if ($peImg && strpos($peImg, 'img/') !== 0 && strpos($peImg, '/') === false) $peImg = 'img/' . $peImg;
                ?>
                <div class="inner-card">
                    <div class="inner-card-header">
                        <strong>Akce <?= $pi+1 ?></strong>
                        <a href="<?= htmlspecialchars($admin_url) ?>?remove_past=<?= $pi ?>" class="btn-remove" onclick="return confirm('Smazat proběhlou akci?');">Smazat</a>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;">
                        <?php if ($peImg): ?>
                            <img src="<?= htmlspecialchars($peImg) ?>" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:6px;">
                        <?php endif; ?>
                        <div style="flex:1;min-width:200px;">
                            <div class="row-2">
                                <div><label>Datum</label><input type="text" name="past_events_date[]" value="<?= htmlspecialchars($pe['date'] ?? '') ?>" placeholder="12. 3. 2025"></div>
                                <div><label>Místo</label><input type="text" name="past_events_place[]" value="<?= htmlspecialchars($pe['place'] ?? '') ?>" placeholder="Praha"></div>
                            </div>
                            <label>Nahradit fotku (volitelné)</label>
                            <input type="file" name="past_events_photo[]" accept="image/*">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="save">Uložit všechny změny</button>
        </form>

        <p style="text-align:center;margin-top:20px;">
            <a href="index.php" target="_blank">← Zpět na web</a>
            &nbsp;|&nbsp;
            <a href="<?= $slot === 'rano' ? 'akce-rano.php' : 'akce-odpoledne.php' ?>" target="_blank">Náhled stránky ↗</a>
        </p>

    <?php endif; ?>
</div>
</body>
</html>
