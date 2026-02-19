<?php
// Debug režim - zobrazí všechny chyby
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$file = 'data.json';

// Funkce pro převod textu z textarea na pole
function parseProgram($text) {
    $lines = explode("\n", trim($text));
    $program = [];
    foreach ($lines as $line) {
        $parts = explode("|", $line);
        if (count($parts) >= 2) {
            $program[] = [
                'time' => trim($parts[0]),
                'title' => trim($parts[1]),
                'desc' => isset($parts[2]) ? trim($parts[2]) : ''
            ];
        }
    }
    return $program;
}

// Funkce pro převod pole na text do textarea
function formatProgram($array) {
    $text = "";
    if (is_array($array)) {
        foreach ($array as $item) {
            $text .= $item['time'] . " | " . $item['title'] . " | " . $item['desc'] . "\n";
        }
    }
    return trim($text);
}

// Zpracování přihlášení
if (isset($_POST['login'])) {
    if ($_POST['password'] === 'previo') { // ZMĚŇTE HESLO
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Zadané heslo není správné.";
    }
}

// Zpracování uložení
if (isset($_POST['save']) && isset($_SESSION['logged_in'])) {
    // Zpracování POST dat
    $newData = [
        'city' => isset($_POST['city']) ? trim($_POST['city']) : '',
        'date' => isset($_POST['date']) ? trim($_POST['date']) : '',
        'time' => isset($_POST['time']) ? trim($_POST['time']) : '',
        'venue' => isset($_POST['venue']) ? trim($_POST['venue']) : '',
        'capacity' => isset($_POST['capacity']) ? (int)$_POST['capacity'] : 0,
        'registered' => isset($_POST['registered']) ? (int)$_POST['registered'] : 0,
        'promo_title' => isset($_POST['promo_title']) ? trim($_POST['promo_title']) : '',
        'promo_text' => isset($_POST['promo_text']) ? trim($_POST['promo_text']) : '',
        // Uložení programů
        'program_connect' => isset($_POST['program_connect']) ? parseProgram($_POST['program_connect']) : [],
        'program_prolite' => isset($_POST['program_prolite']) ? parseProgram($_POST['program_prolite']) : [],
    ];
    
    // Pokus o zápis
    $jsonString = json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonString === false) {
        $error = "Chyba při převodu dat do JSON formátu: " . json_last_error_msg();
    } else {
        $writeResult = file_put_contents($file, $jsonString);
        if ($writeResult !== false) {
            $success = "✅ Data byla úspěšně uložena (" . $writeResult . " bytů zapsáno).<br>";
            $success .= "Soubor: " . realpath($file) . "<br>";
            $success .= "Čas uložení: " . date('d.m.Y H:i:s');
            // Znovu načíst data po uložení
            clearstatcache();
            $json_content = file_get_contents($file);
            $data = json_decode($json_content, true);
        } else {
            $error = "❌ Při ukládání souboru data.json došlo k chybě. Zkontrolujte oprávnění k zápisu.";
        }
    }
}

// Načtení dat
if (!isset($data)) {
    $json_content = file_exists($file) ? file_get_contents($file) : '{}';
    $data = json_decode($json_content, true);
    if (!$data) {
        $data = [];
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previo Admin</title>
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
            --radius-lg: 24px;
            --radius-pill: 999px;

            --space-page: 40px;
            --space-card: 40px;
            --space-field: 14px;
        }

        * { box-sizing: border-box; }
        body { font-family: 'Source Sans 3', sans-serif; background: var(--color-section); padding: var(--space-page); color: var(--color-text); }
        .container { max-width: 860px; margin: 0 auto; background: var(--color-bg); padding: var(--space-card); border-radius: var(--radius-md); border: 1px solid var(--color-border); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
        h2, h3 { color: var(--color-text); border-bottom: 1px solid var(--color-border); padding-bottom: 10px; margin-top: 30px; }
        h2 { margin-top: 0; border-color: var(--color-primary); }
        label { font-weight: 600; font-size: 0.9rem; display: block; margin-top: 15px; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="password"], textarea { 
            width: 100%; padding: var(--space-field); border: 1px solid var(--color-border); border-radius: var(--radius-sm); box-sizing: border-box; font-size: 1rem; font-family: inherit;
        }
        input[type="text"]:focus, input[type="number"]:focus, input[type="password"]:focus, textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(181, 0, 0, 0.12);
        }
        textarea { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; height: 150px; line-height: 1.5; }
        button { background: var(--color-primary); color: white; border: 1px solid transparent; padding: 15px 30px; cursor: pointer; border-radius: var(--radius-pill); font-weight: 700; font-size: 1.05rem; width: 100%; margin-top: 30px; transition: all 0.25s ease; box-shadow: 0 10px 20px rgba(181, 0, 0, 0.22); }
        button:hover { background: var(--color-primary-dark); transform: translateY(-1px); box-shadow: 0 14px 26px rgba(181, 0, 0, 0.28); }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: var(--radius-sm); border: 1px solid transparent; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .hint { font-size: 0.9rem; color: #4b5563; margin-top: 5px; background: #fafafa; padding: 10px 12px; border: 1px solid var(--color-border); border-left: 3px solid #cbd5e1; border-radius: var(--radius-sm); }
        a { color: #4b5563; }

        @media (max-width: 768px) {
            body { padding: 20px; }
            .container { padding: 24px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Administrace MeetUp</h2>

    <?php if (!isset($_SESSION['logged_in'])): ?>
        <?php if (isset($error)) echo "<div class='alert error'>$error</div>"; ?>
        <form method="POST">
            <label>Heslo:</label>
            <input type="password" name="password" required>
            <button type="submit" name="login">Přihlásit se</button>
        </form>
    <?php else: ?>
        
        <?php if (isset($success)) echo "<div class='alert success'>$success</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert error'>$error</div>"; ?>
        
        <form method="POST">
            <h3>📍 Základní údaje</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div><label>Město:</label><input type="text" name="city" value="<?= htmlspecialchars($data['city'] ?? '') ?>"></div>
                <div><label>Datum:</label><input type="text" name="date" value="<?= htmlspecialchars($data['date'] ?? '') ?>"></div>
                <div><label>Čas:</label><input type="text" name="time" value="<?= htmlspecialchars($data['time'] ?? '') ?>"></div>
                <div><label>Místo (Hotel):</label><input type="text" name="venue" value="<?= htmlspecialchars($data['venue'] ?? '') ?>"></div>
            </div>

            <h3>📊 Kapacita (Pill Bar)</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div><label>Celková kapacita:</label><input type="number" name="capacity" value="<?= $data['capacity'] ?? 50 ?>"></div>
                <div><label>Obsazeno:</label><input type="number" name="registered" value="<?= $data['registered'] ?? 0 ?>"></div>
            </div>

            <h3>📢 Promo texty</h3>
            <label>Nadpis (Hero):</label>
            <input type="text" name="promo_title" value="<?= htmlspecialchars($data['promo_title'] ?? '') ?>">
            <label>Podnadpis:</label>
            <input type="text" name="promo_text" value="<?= htmlspecialchars($data['promo_text'] ?? '') ?>">

            <h3>📝 Program: Dopoledne (Connect)</h3>
            <div class="hint">Formát: <b>Čas | Nadpis | Popis</b> (každá položka na nový řádek)</div>
            <textarea name="program_connect"><?= htmlspecialchars(formatProgram($data['program_connect'] ?? [])) ?></textarea>

            <h3>📝 Program: Odpoledne (PRO/LITE)</h3>
            <div class="hint">Formát: <b>Čas | Nadpis | Popis</b> (každá položka na nový řádek)</div>
            <textarea name="program_prolite"><?= htmlspecialchars(formatProgram($data['program_prolite'] ?? [])) ?></textarea>

            <button type="submit" name="save">Uložit všechny změny</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="index.php" target="_blank" style="color: #4b5563; text-decoration: none;">← Zpět na web</a></p>
    <?php endif; ?>
</div>

</body>
</html>