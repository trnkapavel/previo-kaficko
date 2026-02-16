<?php
$dateStart = '20241024T100000'; // YYYYMMDDTHHmmSS
$dateEnd = '20241024T153000';
$address = 'Liberec (Adresa bude upřesněna)';
$title = 'Previo MeetUp: Liberec';
$description = 'Setkání hoteliérů s týmem Previo.';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=invite.ics');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Previo//MeetUp//CS\r\n";
echo "BEGIN:VEVENT\r\n";
echo "DTSTART:{$dateStart}\r\n";
echo "DTEND:{$dateEnd}\r\n";
echo "SUMMARY:{$title}\r\n";
echo "DESCRIPTION:{$description}\r\n";
echo "LOCATION:{$address}\r\n";
echo "END:VEVENT\r\n";
echo "END:VCALENDAR";
?>