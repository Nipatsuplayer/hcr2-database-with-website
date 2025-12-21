<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($DISCORD_CLIENT_ID) || empty($DISCORD_CLIENT_SECRET)) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "Discord OAuth not configured. Set DISCORD_CLIENT_ID and DISCORD_CLIENT_SECRET in environment variables or edit auth/config.php.";
    exit;
}

$params = [
    'response_type' => 'code',
    'client_id' => $DISCORD_CLIENT_ID,
    'scope' => 'identify',
    'redirect_uri' => $DISCORD_REDIRECT_URI,
    'prompt' => 'consent'
];

$authorize = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
header('Location: ' . $authorize);
exit;