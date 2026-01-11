<?php
$envPaths = [ __DIR__ . '/.env', dirname(__DIR__) . '/.env' ];
foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            if (strlen($val) >= 2 && ($val[0] === '"' && substr($val, -1) === '"' || $val[0] === "'" && substr($val, -1) === "'")) {
                $val = substr($val, 1, -1);
            }
            if (getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
        break;
    }
}

$DISCORD_CLIENT_ID = getenv('DISCORD_CLIENT_ID') ?: '';
$DISCORD_CLIENT_SECRET = getenv('DISCORD_CLIENT_SECRET') ?: '';
$DISCORD_REDIRECT_URI = getenv('DISCORD_REDIRECT_URI') ?: 'http://localhost:8000/auth/discord_callback.php';

$raw = getenv('ALLOWED_DISCORD_IDS') ?: '';
$ALLOWED_DISCORD_IDS = [];
if ($raw !== '') {
    $ALLOWED_DISCORD_IDS = array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
}

$DISCORD_OAUTH_CONFIGURED = (!empty($DISCORD_CLIENT_ID) && !empty($DISCORD_CLIENT_SECRET) && !empty($DISCORD_REDIRECT_URI));

$HCAPTCHA_SITE_KEY = getenv('HCAPTCHA_SITE_KEY') ?: '';
$HCAPTCHA_SECRET_KEY = getenv('HCAPTCHA_SECRET_KEY') ?: '';

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header("Permissions-Policy: interest-cohort=()");
    header('X-Permitted-Cross-Domain-Policies: none');

    $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://api.github.com https://cdnjs.buymeacoffee.com https://js.hcaptcha.com; connect-src 'self' https://api.github.com https://cdnjs.buymeacoffee.com https://hcaptcha.com; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://hcaptcha.com; font-src 'self' data:; frame-src https://hcaptcha.com;";
    header('Content-Security-Policy: ' . $csp);

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
    }
}

ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');
