<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "--- TEST SMTP CONNECTION ---\n\n";

$host = getenv('SMTP_HOST') ?: 'NOT SET';
$port = (int)(getenv('SMTP_PORT') ?: 587);
$user = getenv('SMTP_USER') ?: 'NOT SET';
$pass = str_replace(' ', '', getenv('SMTP_PASSWORD') ?: '');

echo "SMTP_HOST: $host\n";
echo "SMTP_PORT: $port\n";
echo "SMTP_USER: $user\n";
echo "SMTP_PASSWORD: " . (empty($pass) ? 'NOT SET' : str_repeat('*', strlen($pass))) . "\n\n";

if ($host === 'NOT SET') {
    die("SMTP variables are not set in environment.\n");
}

$transport = $port === 465 ? 'ssl://' : '';
$socketStr = $transport . $host . ':' . $port;
echo "Connecting to: $socketStr\n";

$socket = stream_socket_client($socketStr, $errno, $errstr, 10);
if (!$socket) {
    die("Connection failed: $errno - $errstr\n");
}
echo "Connected successfully!\n\n";

stream_set_timeout($socket, 10);

$readLine = function() use ($socket) {
    $response = trim(fgets($socket, 514));
    echo "<< $response\n";
    return $response;
};

$readResponse = function() use ($socket) {
    $response = '';
    while (($line = fgets($socket, 514)) !== false) {
        $response .= trim($line) . "\n";
        echo "<< " . trim($line) . "\n";
        if (isset($line[3]) && $line[3] !== '-') {
            break;
        }
    }
    return trim($response);
};

$write = function($cmd) use ($socket) {
    // Hide password in logs
    if (strpos($cmd, 'AUTH LOGIN') === false && !preg_match('/^[a-zA-Z0-9+\/]+={0,2}$/', $cmd)) {
        echo ">> $cmd\n";
    } else {
        if ($cmd === 'AUTH LOGIN') echo ">> AUTH LOGIN\n";
        else echo ">> [BASE64 DATA]\n";
    }
    fwrite($socket, $cmd . "\r\n");
};

$response = $readLine();
if (strpos($response, '220') !== 0) {
    die("Error: Did not receive 220 banner.\n");
}

$write("EHLO localhost");
$response = $readResponse();

if ($port === 587 && stripos($response, 'STARTTLS') !== false) {
    $write('STARTTLS');
    $response = $readLine();
    
    if (strpos($response, '220') === 0) {
        echo "[Enabling TLS...]\n";
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            die("Error: stream_socket_enable_crypto failed.\n");
        }
        echo "[TLS Enabled successfully!]\n";
        
        $write("EHLO localhost");
        $response = $readResponse();
    }
}

if ($user && $pass) {
    $write('AUTH LOGIN');
    $response = $readLine();
    
    $write(base64_encode($user));
    $response = $readLine();
    
    $write(base64_encode($pass));
    $response = $readLine();
    if (strpos($response, '235') !== 0) {
        die("Error: Authentication failed.\n");
    }
}

$write("QUIT");
$readLine();
fclose($socket);

echo "\n--- TEST COMPLETED SUCCESSFULLY ---\n";
