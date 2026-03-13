<?php
/**
 * Potvrzení odběru newsletteru – double opt-in
 *
 * GET ?token=<64-znakový hex token>
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

$phpmailerAvailable = file_exists(__DIR__ . '/vendor/autoload.php');
if ($phpmailerAvailable) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$cfg = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

// =============================================================================
// POMOCNÉ FUNKCE
// =============================================================================

function logError(string $msg): void {
    $line = date('Y-m-d H:i:s') . ' ' . $msg . "\n";
    file_put_contents(__DIR__ . '/error_log.txt', $line, FILE_APPEND | LOCK_EX);
}

function sendAdminNotification(string $email, array $cfg): void {
    global $phpmailerAvailable;
    $adminEmail = $cfg['admin_email'] ?? '';
    if ($adminEmail === '') return;

    $subject = '[Newsletter] Nový potvrzený odběratel: ' . $email;
    $body    = "Nový odběratel potvrdil odběr newsletteru:\n\nE-mail: {$email}\nČas: " . date('d.m.Y H:i:s') . "\n";

    if ($phpmailerAvailable && !empty($cfg['smtp'])) {
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
            $mail->addAddress($adminEmail);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            logError('newsletter admin notification failed: ' . $e->getMessage());
        }
    }

    $from = $cfg['smtp']['from'] ?? 'noreply@previo.cz';
    @mail($adminEmail, $subject, $body, 'From: ' . $from);
}

function renderPage(string $title, string $heading, string $body, bool $success = true): void {
    $color    = $success ? '#b50000' : '#6b7280';
    $icon     = $success ? '✓' : '✕';
    $iconBg   = $success ? '#fff0f0' : '#f3f4f6';
    $iconClr  = $success ? '#b50000' : '#6b7280';
    echo <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title} – Previo MeetUp</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background: #f5f5f5; min-height: 100vh;
           display: flex; align-items: center; justify-content: center; padding: 24px; }
    .card { background: #fff; border-radius: 16px; max-width: 520px; width: 100%;
            padding: 48px 40px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .icon { width: 72px; height: 72px; border-radius: 50%; background: {$iconBg};
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: {$iconClr}; margin: 0 auto 24px; }
    h1 { font-size: 1.6rem; color: #111; margin-bottom: 12px; }
    p  { font-size: 1rem; color: #6b7280; line-height: 1.6; margin-bottom: 8px; }
    a.btn { display: inline-block; margin-top: 28px; background: {$color}; color: #fff;
            text-decoration: none; padding: 14px 32px; border-radius: 50px;
            font-size: 1rem; font-weight: 700; }
    a.btn:hover { opacity: .88; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon">{$icon}</div>
    <h1>{$heading}</h1>
    {$body}
    <a class="btn" href="https://www.previo.cz">Zpět na Previo.cz</a>
  </div>
</body>
</html>
HTML;
    exit;
}

// =============================================================================
// ZPRACOVÁNÍ TOKENU
// =============================================================================

$token = trim($_GET['token'] ?? '');

if ($token === '' || !ctype_xdigit($token) || strlen($token) !== 64) {
    renderPage('Neplatný odkaz', 'Neplatný odkaz', '<p>Potvrzovací odkaz je neplatný nebo byl pozměněn.</p>', false);
}

$storageFile = __DIR__ . '/tmp/newsletter_subscribers.json';

if (!file_exists($storageFile)) {
    renderPage('Chyba', 'Odkaz nenalezen', '<p>Potvrzovací odkaz nebyl nalezen. Zkuste se přihlásit znovu.</p>', false);
}

$subscribers = json_decode((string) file_get_contents($storageFile), true) ?? [];

$found = false;
foreach ($subscribers as &$sub) {
    if (($sub['token'] ?? '') !== $token) continue;

    $found = true;

    // Již potvrzeno
    if (($sub['status'] ?? '') === 'confirmed') {
        renderPage('Již potvrzeno', 'Odběr již byl potvrzen', '<p>Tento e-mail je již přihlášen k odběru novinek Previo.</p>');
    }

    // Vypršela platnost tokenu
    if (time() > (int)($sub['token_expires'] ?? 0)) {
        renderPage(
            'Odkaz vypršel',
            'Odkaz vypršel',
            '<p>Potvrzovací odkaz byl platný 48 hodin a již vypršel.</p>
             <p>Přihlaste se prosím znovu na stránce akce a my vám zašleme nový odkaz.</p>',
            false
        );
    }

    // Potvrzení
    $sub['status']    = 'confirmed';
    $sub['confirmed'] = date('Y-m-d H:i:s');
    unset($sub['token'], $sub['token_expires']);

    $email = $sub['email'];
    break;
}
unset($sub);

if (!$found) {
    renderPage('Odkaz nenalezen', 'Odkaz nenalezen', '<p>Potvrzovací odkaz nebyl nalezen. Zkuste se přihlásit znovu.</p>', false);
}

// Uložení aktualizovaného stavu
file_put_contents($storageFile, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

// Admin notifikace
sendAdminNotification($email, $cfg);

renderPage(
    'Odběr potvrzen',
    'Odběr novinek potvrzen!',
    '<p>Skvělé! Vaše adresa <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong>
     je nyní přihlášena k odběru novinek z hotelového světa.</p>
     <p>Budeme vás informovat o nadcházejících akcích a trendech.</p>'
);
