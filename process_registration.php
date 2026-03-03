<?php
// Zapnutí výpisu chyb pro případné ladění (na produkci můžete zakomentovat)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

function sendGoogleChatMessage($webhookUrl, $text) {
  if (empty($webhookUrl) || empty($text)) {
    return false;
  }

  $payload = json_encode(['text' => $text], JSON_UNESCAPED_UNICODE);
  if ($payload === false) {
    return false;
  }

  $ch = curl_init($webhookUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return $httpCode >= 200 && $httpCode < 300;
}

// 1. NAČTENÍ DAT O AKCI (Abychom věděli, kam se člověk hlásí)
$eventData = [];
if (file_exists('data.json')) {
    $eventData = json_decode(file_get_contents('data.json'), true);
}

// Fallback hodnoty
$city = $eventData['city'] ?? 'Neznámé místo';
$date = $eventData['date'] ?? 'Bude upřesněno';
$time = $eventData['time'] ?? '09:30';
$venue = $eventData['venue'] ?? 'Bude upřesněno';

// 2. ZÍSKÁNÍ DAT Z FORMULÁŘE
$name = $_POST['name'] ?? '';
$hotel = $_POST['hotel'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$type = $_POST['type'] ?? '';
$location = $_POST['location'] ?? ($eventData['city'] ?? '-');
$question = $_POST['question'] ?? '-';

// Volitelný webhook pro Google Chat notifikace
$googleChatWebhookUrl = getenv('GOOGLE_CHAT_WEBHOOK_URL') ?: '';

if (empty($email) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Vyplňte povinné údaje.']);
    exit;
}

// 3. ODESLÁNÍ DO GOOGLE SHEETS (Opraveno)
// !!! ZDE VLOŽTE VAŠI URL Z GOOGLE SKRIPTU (Musí končit na /exec) !!!
$googleScriptUrl = 'https://script.google.com/macros/s/AKfycbzpEqQQlPahVWj2a37As5IMvBpScNhhgzrsf-ROrLs9bE2mZGh4jnsSCQgyJezLMPCE/exec'; 

// Data pro Google Sheet
$postData = [
    'name' => $name,
    'hotel' => $hotel,
    'email' => $email,
    'phone' => $phone,
    'type' => $type,
    'location' => $location,
    'question' => $question,
    'event_city' => $city // Přidáme i město, ať víte, kam se hlásí
];

// Odeslání pomocí cURL
if (!empty($googleScriptUrl) && strpos($googleScriptUrl, 'VAS_KOD_ZDE') === false) {
    $ch = curl_init($googleScriptUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    $response = curl_exec($ch);
    curl_close($ch);
}

// 4. HTML E-MAIL ÚČASTNÍKOVI
$to = $email;
$subject = "Potvrzení registrace: Previo MeetUp " . $city;

// Nastavení odesílatele (změňte na doménový e-mail vašeho webhostingu!)
$senderEmail = "pavel.trnka@previo.cz"; // Ideálně info@vasedomena.cz, pokud web neběží na previo.cz

$programName = ($type == 'connect') ? 'Dopolední blok (Connect)' : 
               (($type == 'prolite') ? 'Odpolední blok (PRO/LITE)' : 'Celodenní program');

$message = "
<html>
<head><title>Potvrzení registrace</title></head>
<body style='font-family: sans-serif; color: #333; line-height: 1.6;'>
  <div style='max-width: 600px; margin: 0 auto;'>
      <h2 style='color: #222;'>Dobrý den, $name,</h2>
    <p>Děkujeme Vám za registraci na <strong>Previo MeetUp</strong>. Vaše místo je závazně rezervováno.</p>
      
      <div style='background-color: #f9f9f9; border-left: 5px solid #B50000; padding: 20px; margin: 20px 0;'>
        <h3 style='margin-top: 0; color: #B50000;'>📅 $city</h3>
        <p style='margin: 5px 0;'><strong>Datum:</strong> $date</p>
        <p style='margin: 5px 0;'><strong>Čas:</strong> $time</p>
        <p style='margin: 5px 0;'><strong>Místo:</strong> $venue</p>
      </div>

      <p>Vybraný program: <strong>$programName</strong></p>
      <p>Lokalita akce: <strong>$location</strong></p>
      
    <p>Pokud se nebudete moci zúčastnit, prosíme o informaci odpovědí na tento e-mail.</p>

    <p style='margin-top: 30px;'>Těšíme se na setkání s Vámi.<br><strong>Tým Previo</strong></p>
      <div style='font-size: 0.8em; color: #888; border-top: 1px solid #eee; padding-top: 10px; margin-top: 30px;'>
        Previo.cz | +420 251 613 924 | info@previo.cz
      </div>
  </div>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: Previo Registrace <" . $senderEmail . ">" . "\r\n";
$headers .= "Reply-To: pavel.trnka@previo.cz" . "\r\n";

// Odeslání klientovi
$mailSent = mail($to, $subject, $message, $headers);

// 5. ODESLÁNÍ KOPIE VÁM (PAVEL TRNKA)
$adminEmail = "pavel.trnka@previo.cz";
$adminSubject = "Nová registrace: $name ($city)";

// Pro vás stačí jednoduchý textový e-mail
$adminMessage = "Nová registrace na akci v: $city\n\n";
$adminMessage .= "Jméno: $name\n";
$adminMessage .= "Hotel: $hotel\n";
$adminMessage .= "E-mail: $email\n";
$adminMessage .= "Telefon: $phone\n";
$adminMessage .= "Typ účasti: $programName\n";
$adminMessage .= "Lokalita: $location\n";
$adminMessage .= "Dotaz: $question\n";

$adminHeaders = "From: Previo Web <" . $senderEmail . ">" . "\r\n";
$adminHeaders .= "Reply-To: " . $email . "\r\n";
$adminHeaders .= "Content-type:text/plain;charset=UTF-8" . "\r\n";

// Odeslání adminovi
mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);

// 6. VOLITELNÁ NOTIFIKACE DO GOOGLE CHATU
if (!empty($googleChatWebhookUrl)) {
  $chatText = "✅ Nová registrace na Previo MeetUp\n";
  $chatText .= "Město: {$city}\n";
  $chatText .= "Jméno: {$name}\n";
  $chatText .= "Hotel: {$hotel}\n";
  $chatText .= "Typ účasti: {$programName}";

  $chatSent = sendGoogleChatMessage($googleChatWebhookUrl, $chatText);
  if (!$chatSent) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - Chyba odeslání Google Chat notifikace\n", FILE_APPEND);
  }
}

// Návrat odpovědi pro JavaScript
if ($mailSent) {
    echo json_encode(['success' => true]);
} else {
    // I když se e-mail nepodaří, zapíšeme chybu, ale uživateli řekneme OK (pokud prošel Google Sheet)
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - Chyba odeslání na: $to\n", FILE_APPEND);
    echo json_encode(['success' => true, 'warning' => 'Email neodeslan, ale data zpracovana.']);
}
?>