<?php
/**
 * Zpracování přihlášení k newsletteru – double opt-in
 * Funguje s PHPMailer (SMTP) i bez něj (fallback na mail())
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=UTF-8');

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Interní chyba serveru: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')',
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// PHPMailer – nepovinné, fallback na mail()
$phpmailerAvailable = file_exists(__DIR__ . '/vendor/autoload.php');
if ($phpmailerAvailable) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

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
    @file_put_contents(__DIR__ . '/error_log.txt', $line, FILE_APPEND | LOCK_EX);
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
    $rateCfg = $cfg['rate_limit'] ?? ['max_requests' => 3, 'window_seconds' => 300, 'storage_dir' => __DIR__ . '/tmp/rate_limits'];
    $dir     = $rateCfg['storage_dir'];
    $max     = $rateCfg['max_requests'];
    $window  = $rateCfg['window_seconds'];
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $file    = $dir . '/' . hash('sha256', 'newsletter_' . $ip) . '.json';

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $now      = time();
    $requests = file_exists($file) ? (json_decode((string) file_get_contents($file), true) ?? []) : [];
    $requests = array_values(array_filter($requests, function($t) use ($now, $window) { return $t > $now - $window; }));

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
        ok('Potvrzovací e-mail jsme odeslali. Zkontrolujte svou schránku.');
    }

    // Pending – pošli odkaz znovu (stejný token)
    sendConfirmLink($email, $sub['token'], buildBaseUrl(), $cfg);
    ok('Potvrzovací e-mail jsme znovu odeslali. Zkontrolujte svou schránku.');
}

// =============================================================================
// 4. ULOŽENÍ NOVÉHO ODBĚRATELE (pending)
// =============================================================================

$token = bin2hex(random_bytes(32));

$subscribers[] = [
    'email'         => $email,
    'status'        => 'pending',
    'token'         => $token,
    'token_expires' => time() + 48 * 3600,
    'subscribed'    => date('Y-m-d H:i:s'),
    'confirmed'     => null,
    'ip'            => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
    'source'        => 'meetup-kaficko',
];

saveSubscribers($storageFile, $subscribers);

// =============================================================================
// 5. ODESLÁNÍ POTVRZOVACÍHO E-MAILU
// =============================================================================

function buildEmailHtml(string $eEmail, string $eUrl): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Potvrďte odběr novinek – Previo</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

  <!-- Logo -->
  <tr><td style="padding:28px 40px 20px;border-bottom:1px solid #f0f0f0;">
    <img src="https://www.trnka.website/kaficko/img/logo-previo.png"
         alt="Previo" width="120" height="69"
         style="display:block;border:0;height:auto;max-width:120px;">
  </td></tr>

  <!-- Hero -->
  <tr><td style="background:linear-gradient(135deg,#b50000 0%,#8b0000 100%);padding:36px 40px;text-align:center;">
    <p style="color:rgba(255,255,255,0.75);font-size:12px;letter-spacing:3px;text-transform:uppercase;margin:0 0 10px 0;font-weight:600;">Previo MeetUp · Newsletter</p>
    <h1 style="color:#ffffff;font-size:26px;font-weight:800;margin:0;line-height:1.3;">Potvrďte odběr novinek</h1>
  </td></tr>

  <!-- Tělo -->
  <tr><td style="padding:40px 40px 32px;">
    <p style="font-size:16px;color:#374151;line-height:1.7;margin:0 0 16px;">Ahoj,</p>
    <p style="font-size:16px;color:#374151;line-height:1.7;margin:0 0 16px;">
      obdrželi jsme žádost o přihlášení adresy <strong style="color:#111827;">{$eEmail}</strong>
      k odběru novinek z hotelového světa.
    </p>
    <p style="font-size:16px;color:#374151;line-height:1.7;margin:0 0 32px;">
      Kliknutím na tlačítko níže odběr potvrdíte.<br>
      <span style="font-size:14px;color:#9ca3af;">Odkaz je platný 48 hodin.</span>
    </p>

    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center" style="padding-bottom:32px;">
        <a href="{$eUrl}"
           style="display:inline-block;background:#b50000;color:#ffffff;text-decoration:none;
                  font-size:16px;font-weight:700;padding:16px 40px;border-radius:50px;">
          Potvrdit odběr novinek →
        </a>
      </td></tr>
    </table>

    <hr style="border:none;border-top:1px solid #f0f0f0;margin:0 0 24px;">
    <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin:0;">
      Pokud jste se k odběru nepřihlásili, tento e-mail ignorujte — nic se nestane.<br>
      Tlačítko nefunguje? Zkopírujte odkaz do prohlížeče:<br>
      <span style="color:#b50000;word-break:break-all;">{$eUrl}</span>
    </p>
  </td></tr>

  <!-- Patička -->
  <tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #f0f0f0;text-align:center;">
    <p style="font-size:12px;color:#9ca3af;margin:0;line-height:1.8;">
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
}

function sendConfirmLink(string $email, string $token, string $baseUrl, array $cfg): void {
    $confirmUrl = $baseUrl . '/confirm_newsletter.php?token=' . urlencode($token);
    $eEmail     = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $eUrl       = htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8');
    $html       = buildEmailHtml($eEmail, $eUrl);
    $subject    = 'Potvrďte přihlášení k novinkám – Previo';
    $altText    = "Potvrďte odběr novinek Previo kliknutím na odkaz:\n\n{$confirmUrl}\n\nOdkaz je platný 48 hodin.";

    global $phpmailerAvailable;

    if ($phpmailerAvailable && !empty($cfg['smtp'])) {
        // PHPMailer přes SMTP
        try {
            $smtp = $cfg['smtp'];
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['username'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtp['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($smtp['from'], $smtp['from_name']);
            $mail->addAddress($email);
            $mail->addReplyTo($smtp['reply_to'] ?? $smtp['from']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $altText;
            $mail->send();
            return;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            logError('newsletter PHPMailer failed: ' . $e->getMessage() . ' – falling back to mail()');
        }
    }

    // Fallback: PHP mail()
    $from     = $cfg['smtp']['from'] ?? 'noreply@previo.cz';
    $fromName = $cfg['smtp']['from_name'] ?? 'Previo MeetUp';
    $headers  = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $from . '>',
        'Reply-To: ' . ($cfg['smtp']['reply_to'] ?? $from),
    ]);

    if (!@mail($email, $subject, $html, $headers)) {
        logError('newsletter mail() failed for ' . $email);
        fail('Nepodařilo se odeslat potvrzovací e-mail. Zkuste to prosím znovu.', 500);
    }
}

sendConfirmLink($email, $token, buildBaseUrl(), $cfg);

ok('Téměř hotovo! Poslali jsme vám e-mail s potvrzovacím odkazem. Zkontrolujte svou schránku (i spam).');
