<?php
require_once __DIR__ . '/auth/check_auth.php';
ensure_authorized_json();

$db_file = './main.sqlite';

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('error' => "Database connection failed: " . $e->getMessage())));
}


$data = json_decode(file_get_contents('php://input'), true);

$recordId = $data['recordId'];

try {
    
    $stmt = $db->prepare("DELETE FROM WorldRecord WHERE rowid = :recordId");
    $stmt->execute([':recordId' => $recordId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
