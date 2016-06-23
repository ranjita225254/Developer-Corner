<?php
//download mpdf library from here http://www.mpdf1.com/mpdf/index.php
include("mpdf.php"); //Include mPDF Class 
$pdf = new mPDF(); // Create new mPDF Document
$pdf->AddPage();
$pdf->SetMargins(1,1,1,1);
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(200,75,75);
$pdf->Cell(40, 10, 'Hello To Pdf World',0, 0, 'C');
$pdf->Ln(10);
$html.= "<table>"
        . "<tr><td>Vendor Name</td><td>Shpping Details</td><td>Address</td></tr>"
        . "<tr><td>Ranjita Gupta</td><td>This is just a text.</td><td>Lucknow</td></tr>"
        . "</table>";
$pdf->WriteHTML(utf8_encode($html));
$filename = "invoice-" . date("d-m-Y") . '.pdf'; //Your Filename with local date and time
$content = $pdf->Output('', 'S');
$content = chunk_split(base64_encode($content));
$mailto = 'ranjita@redsquares.in'; //Mailto here
$from_name = 'Ranjita Gupta'; //Name of sender mail
$from_mail = 'ranjita@redsquares.in'; //Mailfrom here
$subject = 'Send Invioce';
$message = 'Invoice Enclosed within the attachment.!';
//Headers of PDF and e-mail
$boundary = "XYZ-" . date("dmYis") . "-ZYX";
$header = "--$boundary\r\n";
$header .= "Content-Transfer-Encoding: 8bits\r\n";
$header .= "Content-Type: text/html; charset=ISO-8859-1\r\n\r\n"; // or utf-8
$header .= "$message\r\n";
$header .= "--$boundary\r\n";
$header .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
$header .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
$header .= "Content-Transfer-Encoding: base64\r\n\r\n";
$header .= "$content\r\n";
$header .= "--$boundary--\r\n";
$header2 = "MIME-Version: 1.0\r\n";
$header2 .= "From:ranjita@redsquares.in.in\r\n";
$header2 .= "Reply-To: ranjita@redsquares.in.in\r\n";
$header2 .= "Content-type: multipart/mixed; boundary=\"$boundary\"\r\n";
$header2 .= "$boundary\r\n";
 mail($mailto, $subject, $header, $header2, "-r" . $from_mail);
$pdf->Output($filename, 'I');
exit;
?>
