<?php
// Test script pro kontrolu načítání dat
echo "<h2>Test načítání data.json</h2>";

$file = 'data.json';

// Zkontrolovat, zda soubor existuje
if (file_exists($file)) {
    echo "✅ Soubor data.json existuje<br>";
    
    // Zkontrolovat oprávnění
    echo "Oprávnění: " . substr(sprintf('%o', fileperms($file)), -4) . "<br>";
    
    // Načíst obsah
    $json_content = file_get_contents($file);
    echo "✅ Obsah načten (" . strlen($json_content) . " bytů)<br>";
    
    // Dekódovat JSON
    $data = json_decode($json_content, true);
    if ($data) {
        echo "✅ JSON dekódován úspěšně<br><br>";
        echo "<h3>Data:</h3>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    } else {
        echo "❌ Chyba při dekódování JSON: " . json_last_error_msg() . "<br>";
    }
} else {
    echo "❌ Soubor data.json neexistuje!<br>";
}

echo "<hr>";
echo "<a href='index.php'>→ Jít na index.php</a> | ";
echo "<a href='admin.php'>→ Jít na admin.php</a>";
?>
