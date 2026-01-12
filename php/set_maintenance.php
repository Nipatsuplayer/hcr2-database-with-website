<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/maintenance_helpers.php';
header('Content-Type: application/json; charset=utf-8');

// Only allow admin users
if (!is_request_admin_allowed()) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = $data['action'] ?? $_POST['action'] ?? null;

$path = maintenance_flag_path();
try {
    if ($action === 'enable' || $action === 'on' || $action === '1') {
        // create file
        file_put_contents($path, "1");
        echo json_encode(['success' => true, 'maintenance' => true]);
        exit;
    }
    if ($action === 'disable' || $action === 'off' || $action === '0') {
        if (file_exists($path)) unlink($path);
        echo json_encode(['success' => true, 'maintenance' => false]);
        exit;
    }
    // toggle if none provided
    if (file_exists($path)) { unlink($path); $m=false; } else { file_put_contents($path, "1"); $m=true; }
    echo json_encode(['success' => true, 'maintenance' => $m]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to change maintenance state']);
}

?>
