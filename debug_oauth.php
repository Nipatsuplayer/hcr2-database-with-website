<?php
require_once __DIR__ . '/auth/config.php';

echo "=== Discord OAuth Configuration ===\n";
echo "CLIENT_ID: " . (empty($DISCORD_CLIENT_ID) ? "NOT SET" : "***") . "\n";
echo "CLIENT_SECRET: " . (empty($DISCORD_CLIENT_SECRET) ? "NOT SET" : "***") . "\n";
echo "REDIRECT_URI: $DISCORD_REDIRECT_URI\n";
echo "CONFIGURED: " . ($DISCORD_OAUTH_CONFIGURED ? "YES" : "NO") . "\n\n";

echo "=== Testing cURL directly ===\n";

if (!extension_loaded('curl')) {
    echo "ERROR: cURL extension not loaded!\n";
    exit;
}

echo "cURL Version: " . curl_version()['version'] . "\n";
echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n\n";

// Test simple request to Discord API
$test_url = 'https://discord.com/api/users/@me';
echo "Testing connection to: $test_url\n";

$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capture verbose output
$verbose = fopen('php://temp', 'r+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

echo "\nMaking request...\n";
$resp = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);
curl_close($ch);

if ($err) {
    echo "ERROR: $err\n";
} else {
    echo "HTTP Code: " . $info['http_code'] . "\n";
    echo "Response received\n";
    if (!empty($verboseLog)) {
        echo "\nDetailed log:\n";
        echo $verboseLog;
    }
}
