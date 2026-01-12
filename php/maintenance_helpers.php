<?php
// Helpers for maintenance mode
// Maintenance is enabled by creating a file named MAINTENANCE in the repo root.

function maintenance_flag_path() {
    return __DIR__ . '/../MAINTENANCE';
}

function is_maintenance_enabled() {
    return file_exists(maintenance_flag_path());
}

// Check if current session belongs to an allowed admin user (same logic as admin.php)
function is_request_admin_allowed() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $allowed = false;
    if (isset($_SESSION['discord']) && !empty($GLOBALS['ALLOWED_DISCORD_IDS'])) {
        $allowed = in_array((string)($_SESSION['discord']['id'] ?? ''), $GLOBALS['ALLOWED_DISCORD_IDS'], true);
    }
    return $allowed;
}

// For JSON endpoints: if maintenance is enabled and user is not admin, return a JSON error and exit
function enforce_maintenance_json() {
    if (is_maintenance_enabled() && !is_request_admin_allowed()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(503);
        echo json_encode(['error' => 'Site is under maintenance. Please try again later.']);
        exit;
    }
}

// For HTML pages: if maintenance enabled and user not admin, render simple maintenance page and exit
function enforce_maintenance_html() {
    if (is_maintenance_enabled() && !is_request_admin_allowed()) {
        http_response_code(503);
        ?><!doctype html>
        <html><head><meta charset="utf-8"><title>Maintenance</title>
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;color:#222;display:flex;align-items:center;justify-content:center;height:100vh;margin:0} .card{background:#fff;padding:24px;border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,0.08);max-width:720px;text-align:center}</style>
        </head><body><div class="card"><h1>We'll be back soon</h1><p>Site is temporarily under maintenance. Please check back later.</p></div></body></html><?php
        exit;
    }
}

?>
