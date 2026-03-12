<?php
/**
 * Zpracování přihlášení k newsletteru – double opt-in
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

function loadSubscribers(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode((string) file_get_contents($file), true) ?? [];
}

function saveSubscribers(string $file, array $subscribers): void {
    if (file_put_contents($file, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
        logError("newsletter: nelze zapsat do $file");
        fail('Chyba při ukládání. Zkuste to znovu.', 500);
    }
}

function buildBaseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['REQUEST_URI'] ?? '/'), '/');
    return $scheme . '://' . $host . $dir;
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

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $now      = time();
    $requests = file_exists($file) ? (json_decode((string) file_get_contents($file), true) ?? []) : [];
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

if ($rawEmail === '') fail('E-mail je povinný.');
if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) fail('Zadejte platnou e-mailovou adresu.');
if (mb_strlen($rawEmail) > 254) fail('E-mail je příliš dlouhý.');

$email = strtolower($rawEmail);

// =============================================================================
// 3. KONTROLA EXISTUJÍCÍHO ZÁZNAMU
// =============================================================================

$storageDir  = __DIR__ . '/tmp';
$storageFile = $storageDir . '/newsletter_subscribers.json';

if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);

$subscribers = loadSubscribers($storageFile);

foreach ($subscribers as $sub) {
    if (($sub['email'] ?? '') !== $email) continue;

    if (($sub['status'] ?? '') === 'confirmed') {
        // Nevyzrazuj existenci – jen potvrď
        ok('Potvrzovací e-mail jsme odeslali. Zkontrolujte svou schránku.');
    }

    // Stav pending – pošli odkaz znovu (stejný token)
    sendConfirmLink($email, $sub['token'], buildBaseUrl(), $cfg);
    ok('Potvrzovací e-mail jsme znovu odeslali. Zkontrolujte svou schránku.');
}

// =============================================================================
// 4. ULOŽENÍ NOVÉHO ODBĚRATELE (pending)
// =============================================================================

$token = bin2hex(random_bytes(32)); // 64 znaků, kryptograficky bezpečný

$subscribers[] = [
    'email'      => $email,
    'status'     => 'pending',
    'token'      => $token,
    'token_expires' => time() + 48 * 3600, // platný 48 hodin
    'subscribed' => date('Y-m-d H:i:s'),
    'confirmed'  => null,
    'ip'         => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
    'source'     => 'meetup-kaficko',
];

saveSubscribers($storageFile, $subscribers);

// =============================================================================
// 5. ODESLÁNÍ POTVRZOVACÍHO E-MAILU
// =============================================================================

function sendConfirmLink(string $email, string $token, string $baseUrl, array $cfg): void {
    $smtp       = $cfg['smtp'];
    $confirmUrl = $baseUrl . '/confirm_newsletter.php?token=' . urlencode($token);
    $eEmail     = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $eUrl       = htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head><meta charset="UTF-8"><title>Potvrďte odběr novinek</title></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">

      <!-- Header -->
      <tr><td style="background:#b50000;padding:32px 40px;text-align:center;">
        <p style="color:#ffffff;font-size:13px;letter-spacing:2px;text-transform:uppercase;margin:0 0 8px;">Previo MeetUp</p>
        <h1 style="color:#ffffff;font-size:24px;margin:0;font-weight:700;">Potvrďte odběr novinek</h1>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:40px;">
        <p style="font-size:16px;color:#374151;line-height:1.6;">Ahoj,</p>
        <p style="font-size:16px;color:#374151;line-height:1.6;">
          obdrželi jsme žádost o přihlášení adresy <strong>{$eEmail}</strong>
          k odběru novinek z hotelového světa od Previo.
        </p>
        <p style="font-size:16px;color:#374151;line-height:1.6;">
          Kliknutím na tlačítko níže odběr potvrdíte. Odkaz je platný <strong>48 hodin</strong>.
        </p>
        <p style="text-align:center;margin:32px 0;">
          <a href="{$eUrl}"
             style="display:inline-block;background:#b50000;color:#ffffff;text-decoration:none;
                    font-size:16px;font-weight:700;padding:16px 36px;border-radius:50px;">
            Potvrdit odběr novinek
          </a>
        </p>
        <p style="font-size:13px;color:#9ca3af;line-height:1.5;">
          Pokud jste se k odběru nepřihlásili, tento e-mail ignorujte.<br>
          Odkaz pro kopírování: {$eUrl}
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
        $mail->Subject = 'Potvrďte přihlášení k novinkám – Previo';
        $mail->Body    = $html;
        $mail->AltBody = "Potvrďte odběr novinek Previo kliknutím na odkaz:\n\n{$confirmUrl}\n\nOdkaz je platný 48 hodin.";

        $mail->send();
    } catch (MailException $e) {
        logError('newsletter confirm link failed: ' . $e->getMessage());
        fail('Nepodařilo se odeslat potvrzovací e-mail. Zkuste to prosím znovu.', 500);
    }
}

sendConfirmLink($email, $token, buildBaseUrl(), $cfg);

ok('Téměř hotovo! Poslali jsme vám e-mail s potvrzovacím odkazem. Zkontrolujte svou schránku (i spam).');
