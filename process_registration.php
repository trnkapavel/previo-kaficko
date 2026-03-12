<?php
/**
 * Zpracování registračního formuláře – Previo MeetUp
 *
 * Závislosti (nainstaluj přes Composer):
 *   composer require phpmailer/phpmailer
 *
 * Struktura odpovědi: JSON { success: bool, message?: string }
 */

declare(strict_types=1);

// Na produkci nastav na false / zakomentuj
error_reporting(E_ALL);
ini_set('display_errors', '0');  // chyby nezobrazovat uživateli, logovat na server

header('Content-Type: application/json; charset=UTF-8');

// Načtení závislostí
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

// Načtení konfigurace
$cfg = require __DIR__ . '/config.php';

// =============================================================================
// POMOCNÉ FUNKCE
// =============================================================================

/** Ukončí požadavek s JSON chybou */
function fail(string $message, int $httpCode = 422): never {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/** Zaznamená chybu do logu */
function logError(string $msg): void {
    $line = date('Y-m-d H:i:s') . ' [' . ($_SERVER['REMOTE_ADDR'] ?? '?') . '] ' . $msg . "\n";
    file_put_contents(__DIR__ . '/error_log.txt', $line, FILE_APPEND | LOCK_EX);
}

// =============================================================================
// 1. RATE LIMITING – max N požadavků z jedné IP za časové okno
// =============================================================================

function checkRateLimit(array $cfg): void {
    $ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key      = hash('sha256', $ip);          // neukládáme raw IP do souborů
    $dir      = $cfg['storage_dir'];
    $file     = $dir . '/' . $key . '.json';
    $max      = $cfg['max_requests'];
    $window   = $cfg['window_seconds'];
    $now      = time();

    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    // Načti existující záznamy
    $timestamps = [];
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];
    }

    // Odstraň starší záznamy mimo okno
    $timestamps = array_values(array_filter($timestamps, fn($t) => ($now - $t) < $window));

    if (count($timestamps) >= $max) {
        $retryAfter = $window - ($now - min($timestamps));
        fail("Příliš mnoho pokusů. Zkuste to prosím za {$retryAfter} sekund.", 429);
    }

    // Zaznamenej aktuální pokus
    $timestamps[] = $now;
    file_put_contents($file, json_encode($timestamps), LOCK_EX);
}

checkRateLimit($cfg['rate_limit']);

// =============================================================================
// 2. VALIDACE VSTUPŮ
// =============================================================================

/** Vrátí ošetřený string nebo null pokud prázdný */
function inputString(string $key, int $maxLen = 200): ?string {
    $val = trim($_POST[$key] ?? '');
    if ($val === '') return null;
    // Odstraň HTML tagy a ořízni na max délku
    $val = strip_tags($val);
    return mb_substr($val, 0, $maxLen);
}

$name     = inputString('name',     100);
$hotel    = inputString('hotel',    150);
$email    = inputString('email',    200);
$phone    = inputString('phone',     30);
$type     = inputString('type',      20);
$location = inputString('location', 100);
$question = inputString('question', 1000) ?? '-';

// Povinná pole
if (!$name)  fail('Vyplňte jméno a příjmení.');
if (!$hotel) fail('Vyplňte název ubytování.');
if (!$email) fail('Vyplňte e-mailovou adresu.');

// Formát e-mailu
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('E-mailová adresa není platná.');
}

// Telefon – povoleny jen číslice, +, -, mezera, závorky
if ($phone !== null && !preg_match('/^[\d\s+\-().]+$/', $phone)) {
    fail('Telefonní číslo obsahuje nepovolené znaky.');
}

// Whitelist pro typ akce
if (!in_array($type, ['connect', 'prolite'], true)) {
    fail('Neplatný typ akce.');
}

// =============================================================================
// 3. VALIDACE GOOGLE reCAPTCHA v3
// =============================================================================

function verifyRecaptcha(string $token, array $cfg): void {
    if (empty($cfg['secret_key']) || str_contains($cfg['secret_key'], 'ZMEN_ME')) {
        return; // reCAPTCHA není nakonfigurována – přeskoč (jen pro vývoj)
    }

    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
        'secret'   => $cfg['secret_key'],
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]));

    if (!$response) {
        fail('Nepodařilo se ověřit reCAPTCHA. Zkuste to znovu.', 503);
    }

    $data = json_decode($response, true);

    if (empty($data['success']) || ($data['score'] ?? 0) < $cfg['min_score']) {
        logError('reCAPTCHA zamítnuta: score=' . ($data['score'] ?? 'N/A'));
        fail('Ověření bezpečnosti selhalo. Zkuste to znovu.', 403);
    }
}

$recaptchaToken = trim($_POST['recaptcha_token'] ?? '');
verifyRecaptcha($recaptchaToken, $cfg['recaptcha']);

// =============================================================================
// 4. NAČTENÍ DAT O AKCI
// =============================================================================

$dataFile  = ($type === 'prolite') ? 'data-odpoledne.json' : 'data-rano.json';
$eventData = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?: [])
    : [];

$city        = $eventData['city']    ?? 'Neznámé místo';
$date        = $eventData['date']    ?? 'Bude upřesněno';
$time        = $eventData['time']    ?? '09:30';
$venue       = $eventData['venue']   ?? 'Bude upřesněno';
$program     = is_array($eventData['program'] ?? null) ? $eventData['program'] : [];
$programName = $type === 'connect' ? 'Dopolední blok (Connect)' : 'Odpolední blok (PRO/LITE)';

// =============================================================================
// 5. ODESLÁNÍ DO GOOGLE SHEETS
// =============================================================================

$googleScriptUrl = 'https://script.google.com/macros/s/AKfycbzpEqQQlPahVWj2a37As5IMvBpScNhhgzrsf-ROrLs9bE2mZGh4jnsSCQgyJezLMPCE/exec';

if (!empty($googleScriptUrl) && !str_contains($googleScriptUrl, 'VAS_KOD_ZDE')) {
    $ch = curl_init($googleScriptUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_POSTFIELDS     => http_build_query([
            'name'       => $name,
            'hotel'      => $hotel,
            'email'      => $email,
            'phone'      => $phone ?? '',
            'type'       => $type,
            'location'   => $location ?? $city,
            'question'   => $question,
            'event_city' => $city,
        ]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// =============================================================================
// 6. SESTAVENÍ E-MAILU
// =============================================================================

// Pomocné proměnné pro kalendářové linky
function czechDateToYmd(string $s): string {
    static $m = ['ledna'=>'01','února'=>'02','března'=>'03','dubna'=>'04','května'=>'05','června'=>'06',
                 'července'=>'07','srpna'=>'08','září'=>'09','října'=>'10','listopadu'=>'11','prosince'=>'12'];
    if (preg_match('/(\d+)\.\s*(\S+)\s+(\d{4})/', $s, $p)) {
        return $p[3] . ($m[strtolower($p[2])] ?? '01') . sprintf('%02d', (int)$p[1]);
    }
    return date('Ymd');
}

$dateYmd      = czechDateToYmd($date);
$timeStart    = str_replace(':', '', $time) . '00';
$timeEnd      = ($type === 'prolite') ? '170000' : '120000';
$googleCalUrl = 'https://calendar.google.com/calendar/render?' . http_build_query([
    'action'   => 'TEMPLATE',
    'text'     => 'Previo MeetUp – ' . $city,
    'dates'    => $dateYmd . 'T' . $timeStart . '/' . $dateYmd . 'T' . $timeEnd,
    'details'  => $programName . "\n\nPrevio MeetUp – setkání s týmem Previo.",
    'location' => $venue . ', ' . $city,
]);
$icsUrl  = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'roadshow.previo.cz') . '/kaficko/download_ics.php?type=' . urlencode($type);
$mapsUrl = 'https://maps.google.com/?q=' . urlencode($venue . ', ' . $city);

/** Sestaví HTML řádky programu – vstupní data jsou z JSON, ne od uživatele */
function buildProgramRows(array $program): string {
    $rows = '';
    foreach ($program as $idx => $item) {
        $bg    = ($idx % 2 === 0) ? '#ffffff' : '#fafafa';
        $t     = htmlspecialchars($item['time']  ?? '', ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $desc  = htmlspecialchars($item['desc']  ?? '', ENT_QUOTES, 'UTF-8');
        $rows .= "
        <tr>
            <td style=\"padding:14px 20px;background:{$bg};border-bottom:1px solid #f0f0f0;width:90px;vertical-align:top;\">
                <span style=\"font-weight:700;color:#B50000;font-size:15px;white-space:nowrap;\">{$t}</span>
            </td>
            <td style=\"padding:14px 20px;background:{$bg};border-bottom:1px solid #f0f0f0;vertical-align:top;\">
                <strong style=\"color:#111;font-size:15px;\">{$title}</strong><br>
                <span style=\"color:#666;font-size:14px;\">{$desc}</span>
            </td>
        </tr>";
    }
    return $rows;
}

// Všechna uživatelská data escapujeme před vložením do HTML e-mailu
$eName        = htmlspecialchars($name,        ENT_QUOTES, 'UTF-8');
$eCity        = htmlspecialchars($city,        ENT_QUOTES, 'UTF-8');
$eDate        = htmlspecialchars($date,        ENT_QUOTES, 'UTF-8');
$eTime        = htmlspecialchars($time,        ENT_QUOTES, 'UTF-8');
$eVenue       = htmlspecialchars($venue,       ENT_QUOTES, 'UTF-8');
$eProgramName = htmlspecialchars($programName, ENT_QUOTES, 'UTF-8');
$eGoogleCal   = htmlspecialchars($googleCalUrl, ENT_QUOTES, 'UTF-8');
$eIcsUrl      = htmlspecialchars($icsUrl,      ENT_QUOTES, 'UTF-8');
$eMapsUrl     = htmlspecialchars($mapsUrl,     ENT_QUOTES, 'UTF-8');
$baseUrl      = 'https://www.trnka.website/kaficko';
$programRows  = buildProgramRows($program);
$programBlock = !empty($program) ? "
    <tr>
        <td style=\"padding:0 40px 32px;\">
            <p style=\"margin:0 0 16px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#B50000;\">Program</p>
            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border:1px solid #f0f0f0;border-radius:10px;overflow:hidden;\">
                {$programRows}
            </table>
        </td>
    </tr>" : '';

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Potvrzení registrace – Previo MeetUp</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;">

<!-- Pre-header: krátký text viditelný v náhledu inboxu -->
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
    Vaše místo na Previo MeetUp {$eCity} ({$eDate}) je potvrzeno. Těšíme se na setkání!
</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

    <!-- Červený accent pruh -->
    <tr><td style="background:#B50000;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>

    <!-- Header: logo + badge -->
    <tr>
        <td style="background:#fff;padding:32px 40px 24px;border-bottom:1px solid #f0f0f0;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td>
                    <img src="{$baseUrl}/img/logo-previo.png" width="175" height="100" alt="previo"
                         style="display:block;border:0;font-size:22px;font-weight:700;color:#B50000;font-family:Arial,sans-serif;">
                </td>
                <td align="right">
                    <span style="display:inline-block;background:#fff0f0;color:#B50000;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;padding:6px 14px;border-radius:999px;border:1px solid #ffd0d0;">
                        Registrace potvrzena
                    </span>
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Pozdrav -->
    <tr>
        <td style="padding:40px 40px 32px;background:#fff;">
            <p style="margin:0 0 8px;font-size:26px;font-weight:700;color:#111;line-height:1.2;">Dobrý den, {$eName},</p>
            <p style="margin:0;font-size:16px;color:#555;line-height:1.6;">
                Vaše registrace na <strong style="color:#111;">Previo MeetUp {$eCity}</strong> je potvrzena.
                Těšíme se na setkání s vámi!
            </p>
        </td>
    </tr>

    <!-- Detaily akce -->
    <tr>
        <td style="padding:0 40px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff8f8;border:1px solid #fdd;border-radius:10px;overflow:hidden;">
                <tr><td style="background:#B50000;padding:14px 24px;">
                    <span style="color:#fff;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;">Detaily akce</span>
                </td></tr>
                <tr><td style="padding:24px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:8px 0;vertical-align:top;width:130px;"><span style="font-size:13px;color:#999;text-transform:uppercase;letter-spacing:1px;">Datum</span></td>
                            <td style="padding:8px 0;"><strong style="color:#111;font-size:15px;">{$eDate}</strong></td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;vertical-align:top;border-top:1px solid #fce8e8;"><span style="font-size:13px;color:#999;text-transform:uppercase;letter-spacing:1px;">Čas</span></td>
                            <td style="padding:8px 0;border-top:1px solid #fce8e8;">
                                <strong style="color:#111;font-size:15px;">{$eTime}</strong>
                                <span style="color:#666;font-size:14px;margin-left:6px;">({$eProgramName})</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;vertical-align:top;border-top:1px solid #fce8e8;"><span style="font-size:13px;color:#999;text-transform:uppercase;letter-spacing:1px;">Místo</span></td>
                            <td style="padding:8px 0;border-top:1px solid #fce8e8;">
                                <strong style="color:#111;font-size:15px;">{$eVenue}</strong><br>
                                <a href="{$eMapsUrl}" style="color:#B50000;font-size:14px;text-decoration:none;">Zobrazit na mapě &#8594;</a>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </td>
    </tr>

    {$programBlock}

    <!-- Přidat do kalendáře -->
    <tr>
        <td style="padding:0 40px 32px;">
            <p style="margin:0 0 16px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:#B50000;">Přidat do kalendáře</p>
            <table cellpadding="0" cellspacing="0"><tr>
                <td style="padding-right:12px;">
                    <a href="{$eGoogleCal}" target="_blank" style="display:inline-block;background:#4285F4;color:#fff;font-weight:700;font-size:14px;padding:12px 22px;border-radius:6px;text-decoration:none;">
                        Google Kalendář
                    </a>
                </td>
                <td>
                    <a href="{$eIcsUrl}" style="display:inline-block;background:#f0f4f8;color:#333;font-weight:700;font-size:14px;padding:12px 22px;border-radius:6px;text-decoration:none;border:1px solid #ddd;">
                        Outlook / Apple Kalendář
                    </a>
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Poznámka -->
    <tr>
        <td style="padding:0 40px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:8px;border-left:3px solid #e5e7eb;">
                <tr><td style="padding:16px 20px;">
                    <p style="margin:0;font-size:14px;color:#666;line-height:1.6;">
                        Pokud se nebudete moci zúčastnit, dejte nám prosím vědět odpovědí na tento e-mail.
                        Pomůžete tím ostatním zájemcům, kteří čekají na uvolněné místo.
                    </p>
                </td></tr>
            </table>
        </td>
    </tr>

    <!-- Podpis -->
    <tr>
        <td style="padding:32px 40px;background:#fafafa;border-top:1px solid #f0f0f0;">
            <p style="margin:0 0 4px;font-size:15px;color:#111;">Těšíme se na setkání,</p>
            <p style="margin:0;font-size:15px;font-weight:700;color:#111;">Tým Previo</p>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="padding:20px 40px;background:#111;border-radius:0 0 12px 12px;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td>
                    <img src="{$baseUrl}/img/logo-previo-email-white.png" width="175" height="100" alt="previo"
                         style="display:block;border:0;font-size:22px;font-weight:700;color:#fff;font-family:Arial,sans-serif;">
                    <span style="font-size:12px;color:#888;display:block;margin-top:4px;">Více hostů. Méně starostí.</span>
                </td>
                <td align="right">
                    <span style="font-size:12px;color:#888;">
                        <a href="mailto:info@previo.cz" style="color:#888;text-decoration:none;">info@previo.cz</a>
                        &nbsp;·&nbsp; +420 251 613 924
                    </span>
                </td>
            </tr></table>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;

// =============================================================================
// 7. ODESLÁNÍ PŘES PHPMAILER (SMTP)
// =============================================================================

function sendViaSMTP(string $to, string $subject, string $htmlBody, array $smtpCfg, string $adminEmail): bool {
    $mail = new PHPMailer(true); // true = exceptions enabled

    try {
        // Server
        $mail->isSMTP();
        $mail->Host       = $smtpCfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpCfg['username'];
        $mail->Password   = $smtpCfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // pro port 587
        $mail->Port       = $smtpCfg['port'];
        $mail->CharSet    = 'UTF-8';

        // Odesílatel
        $mail->setFrom($smtpCfg['from'], $smtpCfg['from_name']);
        $mail->addReplyTo($smtpCfg['reply_to']);

        // Příjemce (účastník)
        $mail->addAddress($to);

        // Obsah
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>', '</tr>'], "\n", $htmlBody));

        $mail->send();
        return true;

    } catch (MailException $e) {
        logError('PHPMailer chyba (účastník): ' . $e->getMessage());
        return false;
    }
}

function sendAdminNotification(string $adminEmail, string $subject, string $body, array $smtpCfg, string $replyTo): void {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtpCfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpCfg['username'];
        $mail->Password   = $smtpCfg['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpCfg['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtpCfg['from'], $smtpCfg['from_name']);
        $mail->addReplyTo($replyTo);
        $mail->addAddress($adminEmail);

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $body;

        $mail->send();
    } catch (MailException $e) {
        logError('PHPMailer chyba (admin): ' . $e->getMessage());
    }
}

$subject  = 'Potvrzení registrace – Previo MeetUp ' . $city . ', ' . $date;
$mailSent = sendViaSMTP($email, $subject, $htmlBody, $cfg['smtp'], $cfg['admin_email']);

// Admin kopie (plaintext)
$adminSubject = "Nová registrace: {$name} ({$city})";
$adminBody    = implode("\n", [
    "Nová registrace – Previo MeetUp",
    "Město:    {$city}",
    "Datum:    {$date}",
    "Jméno:    {$name}",
    "Hotel:    {$hotel}",
    "E-mail:   {$email}",
    "Telefon:  " . ($phone ?? '–'),
    "Typ:      {$programName}",
    "Dotaz:    {$question}",
]);
sendAdminNotification($cfg['admin_email'], $adminSubject, $adminBody, $cfg['smtp'], $email);

// =============================================================================
// 8. GOOGLE CHAT NOTIFIKACE (volitelné)
// =============================================================================

$webhookUrl = $cfg['google_chat_webhook'] ?: (getenv('GOOGLE_CHAT_WEBHOOK_URL') ?: '');
if (!empty($webhookUrl)) {
    $chatText = "Nová registrace – Previo MeetUp\nMěsto: {$city}\nJméno: {$name}\nHotel: {$hotel}\nTyp: {$programName}";
    $payload  = json_encode(['text' => $chatText], JSON_UNESCAPED_UNICODE);
    $ch = curl_init($webhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 5,
    ]);
    curl_exec($ch);
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) < 200) {
        logError('Google Chat webhook selhal');
    }
    curl_close($ch);
}

// =============================================================================
// 9. ODPOVĚĎ
// =============================================================================

if ($mailSent) {
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} else {
    // I při chybě e-mailu vrátíme success (data do Sheets prošla), ale zalogujeme
    logError("Email neodeslan na: {$email}");
    echo json_encode(['success' => true, 'warning' => 'Email neodeslan, data zpracovana.'], JSON_UNESCAPED_UNICODE);
}
