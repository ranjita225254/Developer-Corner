<?php
ini_set("display_errors",1);
// My device token here (without spaces):
//$deviceToken = 'cc445a6b1ac2833cfb92d1155fba1326adbc0bf9408b27bd246910d8519129fb';
$deviceToken = '68fc79b07b49fc73fd315a29eb74a19805fbd54a6d4779135fef97fff76bd988';

// My private key's passphrase here:
$passphrase = '1234';

// My alert message here:
$message = 'New Push Notification!';

//badge
$badge = 1;

$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'TechmotionDevelopment.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
$fp = stream_socket_client(
    'ssl://gateway.sandbox.push.apple.com:2195', $err,
    $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp)
exit("Failed to connect: $err $errstr" . PHP_EOL);

echo 'Connected to APNS' . PHP_EOL;

// Create the payload body
$body['aps'] = array(
    'alert' => $message,
    'badge' => $badge,
    'sound' => 'default'
);

// Encode the payload as JSON
$payload = json_encode($body);

// Build the binary notification
$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
    echo 'Error, notification not sent' . PHP_EOL;
else
    echo 'notification sent!' . PHP_EOL;

// Close the connection to the server
fclose($fp);

?>
