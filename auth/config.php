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
