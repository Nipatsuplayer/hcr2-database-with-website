<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function is_logged_in() {
    return isset($_SESSION['discord']) && isset($_SESSION['discord']['id']);
}

function is_allowed() {
    global $ALLOWED_DISCORD_IDS;
    if (!is_logged_in()) return false;
    $uid = (string)$_SESSION['discord']['id'];
    if (empty($ALLOWED_DISCORD_IDS)) return false;
    return in_array($uid, $ALLOWED_DISCORD_IDS, true);
}

function ensure_authorized_json() {
    header('Content-Type: application/json; charset=utf-8');
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required.']);
        exit;
    }
    if (!is_allowed()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: insufficient permissions.']);
        exit;
    }
}
