<?php
$app_start_dt = "2016-04-28" . "10:30 a.m.";
$app_end_dt = "2016-04-30" . "11:00 a.m.";
$subject="Google Calender Invitation";
$dtstart = gmdate("Ymd\THis\Z", strtotime($app_start_dt . " UTC"));
$dtend = gmdate("Ymd\THis\Z", strtotime($app_end_dt . " UTC"));
$todaystamp = gmdate("Ymd\THis\Z");
$headers = "From: from_name <ranjita@redsquares.in>\n";
$headers .= "Reply-To: from_name <ranjita@redsquares.in>\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-class: urn:content-classes:calendarmessage\r\n";
$headers .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST\n';
$headers .= 'Content-Disposition: attachment';
$message = '';
//Create ICAL Content (Google rfc 2445 for details and examples of usage) 
$ical = 'BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VEVENT
DTSTART:' . $dtstart . '
DTEND:' . $dtend . '
DTSTAMP:' . $todaystamp . '
ORGANIZER;CN="from_name":MAILTO:ranjita@redsquares.in
UID:' . 001 . '@something.com
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=from_name;X-NUM-GUESTS=0:mailto:ranjita@redsquares.in
CREATED:' . $todaystamp . '
DESCRIPTION:Meeting Description
LAST-MODIFIED:' . $todaystamp . '
LOCATION:My meeting place
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:My Subject
TRANSP:TRANSPARENT
END:VEVENT
END:VCALENDAR';
$message .= $ical;
$to_email_id = "ranjita225254@gmail.com";
//SEND MAIL
$mail_sent = mail($to_email_id, $subject, $message, $headers);
if ($mail_sent) {
    echo 'mail sent';
} else {
    echo 'not sent';
}
?>
