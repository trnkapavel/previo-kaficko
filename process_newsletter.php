<?php
/**
 * Zpracování přihlášení k newsletteru – Previo MeetUp
 *
 * Závislosti: composer require phpmailer/phpmailer
 * Struktura odpovědi: JSON { success: bool, message?: string }
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$cfg = require __DIR__ . '/config.php';

// =============================================================================
// POMOCNÉ FUNKCE
// =============================================================================

function fail(string $message, int $httpCode = 422): never {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function ok(string $message = ''): never {
    echo json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function logError(string $msg): void {
    $line = date('Y-m-d H:i:s') . ' [' . ($_SERVER['REMOTE_ADDR'] ?? '?') . '] ' . $msg . "\n";
    file_put_contents(__DIR__ . '/error_log.txt', $line, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// 1. RATE LIMITING
// =============================================================================

function checkRateLimit(array $cfg): void {
    $rateCfg  = $cfg['rate_limit'];
    $dir      = $rateCfg['storage_dir'];
    $max      = $rateCfg['max_requests'];
    $window   = $rateCfg['window_seconds'];
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $file     = $dir . '/' . hash('sha256', 'newsletter_' . $ip) . '.json';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $now      = time();
    $requests = [];

    if (file_exists($file)) {
        $requests = json_decode((string) file_get_contents($file), true) ?? [];
    }

    // Odstraň staré záznamy mimo okno
    $requests = array_values(array_filter($requests, fn(int $t) => $t > $now - $window));

    if (count($requests) >= $max) {
        fail('Příliš mnoho pokusů. Zkuste to znovu za chvíli.', 429);
    }

    $requests[] = $now;
    file_put_contents($file, json_encode($requests), LOCK_EX);
}

// =============================================================================
// 2. VSTUP A VALIDACE
// =============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Neplatná metoda.', 405);
}

checkRateLimit($cfg);

$rawEmail = trim($_POST['email'] ?? '');

if ($rawEmail === '') {
    fail('E-mail je povinný.');
}

if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
    fail('Zadejte platnou e-mailovou adresu.');
}

if (mb_strlen($rawEmail) > 254) {
    fail('E-mail je příliš dlouhý.');
}

$email = strtolower($rawEmail);

// =============================================================================
// 3. ULOŽENÍ ODBĚRATELE (dedup)
// =============================================================================

$storageDir  = __DIR__ . '/tmp';
$storageFile = $storageDir . '/newsletter_subscribers.json';

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

$subscribers = [];
if (file_exists($storageFile)) {
    $subscribers = json_decode((string) file_get_contents($storageFile), true) ?? [];
}

// Zkontroluj duplicity
$alreadyExists = false;
foreach ($subscribers as $sub) {
    if (($sub['email'] ?? '') === $email) {
        $alreadyExists = true;
        break;
    }
}

if ($alreadyExists) {
    // Nevyzrazuj existenci – reaguj jako úspěch
    ok('Váš e-mail je již přihlášen k odběru. Díky!');
}

$subscribers[] = [
    'email'      => $email,
    'subscribed' => date('Y-m-d H:i:s'),
    'ip'         => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
    'source'     => 'meetup-kaficko',
];

if (file_put_contents($storageFile, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
    logError("newsletter: nelze zapsat do $storageFile");
    fail('Chyba při ukládání. Zkuste to znovu.', 500);
}

// =============================================================================
// 4. POTVRZOVACÍ E-MAIL ODBĚRATELI
// =============================================================================

function sendConfirmation(string $email, array $cfg): void {
    $smtp = $cfg['smtp'];

    $html = <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head><meta charset="UTF-8"><title>Přihlášení k odběru</title></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">

      <!-- Header -->
      <tr><td style="background:#b50000;padding:32px 40px;text-align:center;">
        <p style="color:#ffffff;font-size:13px;letter-spacing:2px;text-transform:uppercase;margin:0 0 8px;">Previo MeetUp</p>
        <h1 style="color:#ffffff;font-size:24px;margin:0;font-weight:700;">Odběr novinek potvrzen ✓</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <p style="font-size:16px;color:#374151;line-height:1.6;">Ahoj,</p>
        <p style="font-size:16px;color:#374151;line-height:1.6;">
          přihlásili jste se k odběru novinek z hotelového světa od <strong>Previo</strong>.
          Budeme vás informovat o nadcházejících akcích, trendech a praktických tipech pro rozvoj ubytování.
        </p>
        <p style="font-size:16px;color:#374151;line-height:1.6;">
          Pokud se chcete odhlásit, stačí odpovědět na tento e-mail s textem <em>„odhlásit"</em>.
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;padding:24px 40px;border-top:1px solid #e5e7eb;text-align:center;">
        <p style="font-size:12px;color:#9ca3af;margin:0;">
          PREVIO s.r.o. · Milady Horákové 13, 602 00 Brno<br>
          <a href="https://www.previo.cz" style="color:#b50000;text-decoration:none;">www.previo.cz</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>
HTML;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp['from'], $smtp['from_name']);
        $mail->addAddress($email);
        $mail->addReplyTo($smtp['reply_to'] ?? $smtp['from']);

        $mail->isHTML(true);
        $mail->Subject = 'Přihlášení k odběru novinek – Previo';
        $mail->Body    = $html;
        $mail->AltBody = 'Přihlásili jste se k odběru novinek Previo MeetUp. Odhlášení: odpovězte "odhlásit".';

        $mail->send();
    } catch (MailException $e) {
        logError('newsletter confirmation mail failed: ' . $e->getMessage());
        // Tiše ignorujeme – subscriber byl uložen
    }
}

// =============================================================================
// 5. ADMINSKÁ NOTIFIKACE
// =============================================================================

function sendAdminNotification(string $email, array $cfg): void {
    $smtp       = $cfg['smtp'];
    $adminEmail = $cfg['admin_email'] ?? '';
    if ($adminEmail === '') return;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp['from'], $smtp['from_name']);
        $mail->addAddress($adminEmail);

        $mail->isHTML(false);
        $mail->Subject = '[Newsletter] Nový odběratel: ' . $email;
        $mail->Body    = "Nový odběratel newsletteru:\n\nE-mail: {$email}\nČas: " . date('d.m.Y H:i:s') . "\n";

        $mail->send();
    } catch (MailException $e) {
        logError('newsletter admin notification failed: ' . $e->getMessage());
    }
}

sendConfirmation($email, $cfg);
sendAdminNotification($email, $cfg);

ok('Přihlášení proběhlo úspěšně. Potvrzení jsme odeslali na váš e-mail.');
