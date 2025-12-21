<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

$logged = isset($_SESSION['discord']) && isset($_SESSION['discord']['id']);
$id = $logged ? (string)$_SESSION['discord']['id'] : null;
$username = $logged ? ($_SESSION['discord']['username'] ?? '') : null;
$allowed = false;
if ($logged && !empty($ALLOWED_DISCORD_IDS)) {
    $allowed = in_array($id, $ALLOWED_DISCORD_IDS, true);
}
echo json_encode(['logged' => $logged, 'id' => $id, 'username' => $username, 'allowed' => $allowed]);
