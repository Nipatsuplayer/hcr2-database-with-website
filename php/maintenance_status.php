<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/maintenance_helpers.php';
header('Content-Type: application/json; charset=utf-8');

$enabled = is_maintenance_enabled();
$allowed = is_request_admin_allowed();

echo json_encode(['maintenance' => $enabled, 'allowed' => $allowed]);

?>
