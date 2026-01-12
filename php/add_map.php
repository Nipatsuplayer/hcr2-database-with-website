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

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST ?? [];
}

$mapName = $data['mapName'] ?? null;

try {
    if (empty($mapName)) {
        echo json_encode(['error' => 'Map name is required.']);
        exit;
    }

    $mapName = trim($mapName);

    // Check if map already exists
    $check = $db->prepare('SELECT idMap FROM Map WHERE nameMap = :name LIMIT 1');
    $check->execute([':name' => $mapName]);
    if ($check->fetch()) {
        echo json_encode(['error' => 'Map already exists in database.']);
        exit;
    }

    // Get next map ID
    $row = $db->query('SELECT COALESCE(MAX(idMap), 0) AS m FROM Map')->fetch(PDO::FETCH_ASSOC);
    $newId = (int)$row['m'] + 1;

    // Insert new map
    $stmt = $db->prepare('INSERT INTO Map (idMap, nameMap) VALUES (:idMap, :nameMap)');
    $stmt->execute([':idMap' => $newId, ':nameMap' => $mapName]);

    echo json_encode(['success' => true, 'idMap' => $newId, 'nameMap' => $mapName]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
