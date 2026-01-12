<?php
// Server-side entrypoint to enforce maintenance before serving the static HTML
require_once __DIR__ . '/php/maintenance_helpers.php';
enforce_maintenance_html();

// If not in maintenance (or admin allowed), serve the existing index.html
// Read and output the file contents
$html = __DIR__ . '/index.html';
if (file_exists($html)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($html);
    exit;
}

// Fallback simple message
echo "<html><body><h1>Index not found</h1></body></html>";
?>
