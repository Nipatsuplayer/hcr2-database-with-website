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

$partIds = $data['partIds'] ?? [];
if (!is_array($partIds) || count($partIds) < 2 || count($partIds) > 4) {
    echo json_encode(['error' => 'Must select 2 to 4 tuning parts.']);
    exit;
}

sort($partIds);

try {
    $db->beginTransaction();

    // Check if a setup with these exact parts already exists
    $inPlaceholders = str_repeat('?,', count($partIds) - 1) . '?';
    $stmt = $db->prepare("
        SELECT idTuningSetup
        FROM TuningSetup
        WHERE idTuningSetup IN (
            SELECT idTuningSetup
            FROM TuningSetupParts
            WHERE idTuningPart IN ($inPlaceholders)
            GROUP BY idTuningSetup
            HAVING COUNT(*) = ?
        )
        AND NOT EXISTS (
            SELECT 1
            FROM TuningSetupParts tsp
            WHERE tsp.idTuningSetup = TuningSetup.idTuningSetup
            AND tsp.idTuningPart NOT IN ($inPlaceholders)
        )
    ");
    $params = array_merge($partIds, [count($partIds)], $partIds);
    $stmt->execute($params);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $db->rollBack();
        echo json_encode(['error' => 'A setup with these parts already exists.']);
        exit;
    }

    // Insert new setup
    $stmt = $db->prepare("INSERT INTO TuningSetup DEFAULT VALUES");
    $stmt->execute();
    $setupId = $db->lastInsertId();

    // Insert parts
    $stmt = $db->prepare("INSERT INTO TuningSetupParts (idTuningSetup, idTuningPart) VALUES (?, ?)");
    foreach ($partIds as $partId) {
        $stmt->execute([$setupId, $partId]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>