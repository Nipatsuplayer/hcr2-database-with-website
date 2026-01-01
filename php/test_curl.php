<?php
echo "=== PHP Configuration Test ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "cURL Extension: " . (extension_loaded('curl') ? "ENABLED" : "DISABLED") . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? "ON" : "OFF") . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? "ENABLED" : "DISABLED") . "\n";

echo "\n=== Loaded Extensions ===\n";
$ext = get_loaded_extensions();
foreach ($ext as $e) {
    if (stripos($e, 'curl') !== false || stripos($e, 'openssl') !== false || stripos($e, 'stream') !== false) {
        echo "- $e\n";
    }
}
