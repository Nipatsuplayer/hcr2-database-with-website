<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function request($url, $method = 'GET', $headers = [], $body = null, $timeout = 10) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) {
            return ['body' => null, 'code' => $code ?: 0, 'error' => $err ?: 'cURL error'];
        }
        return ['body' => $resp, 'code' => $code, 'error' => null];
    }

    if (!ini_get('allow_url_fopen')) {
        return ['body' => null, 'code' => 0, 'error' => 'No HTTP client available (enable cURL or allow_url_fopen)'];
    }

    $headerLines = '';
    foreach ($headers as $h) $headerLines .= $h . "\r\n";
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => $headerLines,
            'content' => $body ?? '',
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $m)) {
            $code = (int)$m[1];
        }
    }
    if ($resp === false) {
        return ['body' => null, 'code' => $code, 'error' => 'file_get_contents failed'];
    }
    return ['body' => $resp, 'code' => $code, 'error' => null];
}

if (!isset($_GET['code'])) {
    http_response_code(400);
    echo "Missing code parameter.";
    exit;
}
$code = $_GET['code'];

if (empty($DISCORD_CLIENT_ID) || empty($DISCORD_CLIENT_SECRET) || empty($DISCORD_REDIRECT_URI)) {
    http_response_code(500);
    echo "Discord OAuth not configured. Set DISCORD_CLIENT_ID, DISCORD_CLIENT_SECRET and DISCORD_REDIRECT_URI.";
    exit;
}

$token_url = 'https://discord.com/api/oauth2/token';
$post_fields = http_build_query([
    'client_id' => $DISCORD_CLIENT_ID,
    'client_secret' => $DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $DISCORD_REDIRECT_URI
]);

$resp = request($token_url, 'POST', ['Content-Type: application/x-www-form-urlencoded'], $post_fields);
if ($resp['error']) {
    http_response_code(502);
    echo "Token request failed: " . htmlspecialchars($resp['error']);
    exit;
}
$tokenData = json_decode($resp['body'], true);
if (!is_array($tokenData)) {
    http_response_code(502);
    echo "Invalid token response from Discord.";
    exit;
}
if (isset($tokenData['error'])) {
    http_response_code(400);
    echo "Discord token error: " . htmlspecialchars($tokenData['error']);
    exit;
}
if (empty($tokenData['access_token'])) {
    http_response_code(502);
    echo "No access token received from Discord.";
    exit;
}
$accessToken = $tokenData['access_token'];

$user_url = 'https://discord.com/api/users/@me';
$auth_header = "Authorization: Bearer {$accessToken}";
$resp = request($user_url, 'GET', [$auth_header]);
if ($resp['error']) {
    http_response_code(502);
    echo "User info request failed: " . htmlspecialchars($resp['error']);
    exit;
}
$user = json_decode($resp['body'], true);
if (!is_array($user) || empty($user['id'])) {
    http_response_code(502);
    echo "Failed to fetch Discord user info.";
    exit;
}

session_regenerate_id(true);
$_SESSION['discord'] = [
    'id' => (string)$user['id'],
    'username' => ($user['username'] ?? '') . (isset($user['discriminator']) ? ('#' . $user['discriminator']) : '')
];

$allowed = false;
if (!empty($ALLOWED_DISCORD_IDS) && isset($_SESSION['discord']['id'])) {
    $allowed = in_array((string)$_SESSION['discord']['id'], $ALLOWED_DISCORD_IDS, true);
}

if ($allowed) {
    header('Location: /../php/admin.php', true, 302);
} else {
    header('Location: /index.html', true, 302);
}
exit;
?>