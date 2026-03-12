<?php
function czechDateToYmd(string $s): string {
    static $m = ['ledna'=>'01','února'=>'02','března'=>'03','dubna'=>'04','května'=>'05','června'=>'06',
                 'července'=>'07','srpna'=>'08','září'=>'09','října'=>'10','listopadu'=>'11','prosince'=>'12'];
    if (preg_match('/(\d+)\.\s*(\S+)\s+(\d{4})/', $s, $p)) {
        $month = $m[strtolower($p[2])] ?? '01';
        return $p[3] . $month . sprintf('%02d', (int)$p[1]);
    }
    return date('Ymd');
}

$type      = $_GET['type'] ?? 'connect';
$dataFile  = ($type === 'prolite') ? 'data-odpoledne.json' : 'data-rano.json';
$eventData = file_exists($dataFile) ? (json_decode(file_get_contents($dataFile), true) ?: []) : [];

$city     = $eventData['city']  ?? 'Neznámé místo';
$date     = $eventData['date']  ?? '';
$time     = $eventData['time']  ?? '09:30';
$venue    = $eventData['venue'] ?? '';

$dateYmd   = czechDateToYmd($date);
$timeStart = str_replace(':', '', $time) . '00';
$timeEnd   = ($type === 'prolite') ? '170000' : '120000';
$programName = ($type === 'prolite') ? 'Odpolední blok (PRO/LITE)' : 'Dopolední blok (Connect)';

$dtStart = $dateYmd . 'T' . $timeStart;
$dtEnd   = $dateYmd . 'T' . $timeEnd;
$uid     = 'previo-meetup-' . $dateYmd . '-' . $type . '@previo.cz';
$now     = gmdate('Ymd\THis\Z');

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=previo-meetup-' . $city . '.ics');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Previo//MeetUp//CS\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "BEGIN:VEVENT\r\n";
echo "UID:{$uid}\r\n";
echo "DTSTAMP:{$now}\r\n";
echo "DTSTART:{$dtStart}\r\n";
echo "DTEND:{$dtEnd}\r\n";
echo "SUMMARY:Previo MeetUp – {$city}\r\n";
echo "DESCRIPTION:{$programName}\\nSetkání s týmem Previo. Vstup zdarma.\r\n";
echo "LOCATION:{$venue}\\, {$city}\r\n";
echo "STATUS:CONFIRMED\r\n";
echo "END:VEVENT\r\n";
echo "END:VCALENDAR";
?>
