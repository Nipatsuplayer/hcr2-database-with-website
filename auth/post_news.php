<?php
require_once __DIR__ . '/check_auth.php';
ensure_authorized_json();
header('Content-Type: application/json; charset=utf-8');
$db_file = dirname(__DIR__) . '/main.sqlite';
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST ?? [];

$title = isset($data['title']) ? trim($data['title']) : '';
$content = isset($data['content']) ? trim($data['content']) : '';

if ($title === '' || $content === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Title and content are required.']);
    exit;
}

$title_safe = strip_tags($title);
$content_safe = strip_tags($content);
$author = '';
if (isset($_SESSION['discord']) && isset($_SESSION['discord']['username'])) {
    $author = $_SESSION['discord']['username'];
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS News (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        author TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare('INSERT INTO News (title, content, author) VALUES (:title, :content, :author)');
    $stmt->execute([
        ':title' => $title_safe,
        ':content' => $content_safe,
        ':author' => $author
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
