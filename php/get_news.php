<?php
$require_config = require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/maintenance_helpers.php';
enforce_maintenance_json();
header('Content-Type: application/json; charset=utf-8');
$db_file = __DIR__ . '/../main.sqlite';
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0 || $limit > 100) $limit = 10;

try {
    $db->exec("CREATE TABLE IF NOT EXISTS News (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        author TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare('SELECT id, title, content, author, created_at FROM News ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['news' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
