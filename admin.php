<?php
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
        $error = "Špatné heslo!";
    }
}

// Zpracování uložení
if (isset($_POST['save']) && isset($_SESSION['logged_in'])) {
    $newData = [
        'city' => $_POST['city'],
        'date' => $_POST['date'],
        'time' => $_POST['time'],
        'venue' => $_POST['venue'],
        'capacity' => (int)$_POST['capacity'],
        'registered' => (int)$_POST['registered'],
        'promo_title' => $_POST['promo_title'],
        'promo_text' => $_POST['promo_text'],
        // Uložení programů
        'program_connect' => parseProgram($_POST['program_connect']),
        'program_prolite' => parseProgram($_POST['program_prolite']),
    ];
    file_put_contents($file, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = "Data a program byly uloženy!";
}

// Načtení dat
$json_content = file_exists($file) ? file_get_contents($file) : '{}';
$data = json_decode($json_content, true);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previo Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        h2, h3 { color: #222; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-top: 30px; }
        h2 { margin-top: 0; border-color: #B50000; }
        label { font-weight: 600; font-size: 0.9rem; display: block; margin-top: 15px; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="password"], textarea { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        textarea { font-family: monospace; height: 150px; line-height: 1.5; }
        button { background: #B50000; color: white; border: none; padding: 15px 30px; cursor: pointer; border-radius: 6px; font-weight: bold; font-size: 1.1rem; width: 100%; margin-top: 30px; transition: 0.3s; }
        button:hover { background: #900000; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .hint { font-size: 0.85rem; color: #666; margin-top: 5px; background: #fafafa; padding: 10px; border-left: 3px solid #ccc; }
    </style>
</head>
<body>

<div class="container">
    <h2>Administrace MeetUpu</h2>

    <?php if (!isset($_SESSION['logged_in'])): ?>
        <?php if (isset($error)) echo "<div class='alert error'>$error</div>"; ?>
        <form method="POST">
            <label>Heslo:</label>
            <input type="password" name="password" required>
            <button type="submit" name="login">Vstoupit</button>
        </form>
    <?php else: ?>
        
        <?php if (isset($success)) echo "<div class='alert success'>$success</div>"; ?>
        
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
        <p style="text-align: center; margin-top: 20px;"><a href="index.php" target="_blank" style="color: #666; text-decoration: none;">← Zpět na web</a></p>
    <?php endif; ?>
</div>

</body>
</html>