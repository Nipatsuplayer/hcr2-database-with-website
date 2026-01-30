<?php
require_once __DIR__ . '/../auth/check_auth.php';
ensure_authorized_json();

$db_file = __DIR__ . '/../main.sqlite';

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('error' => "Database connection failed: " . $e->getMessage())));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST ?? [];
}

$partName = trim($data['partName'] ?? '');

if (empty($partName)) {
    echo json_encode(['error' => 'Tuning part name is required.']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO TuningPart (nameTuningPart) VALUES (:name)");
    $stmt->execute([':name' => $partName]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>