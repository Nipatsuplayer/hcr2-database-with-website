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

$vehicleName = $data['vehicleName'] ?? null;

try {
    if (empty($vehicleName)) {
        echo json_encode(['error' => 'Vehicle name is required.']);
        exit;
    }

    $vehicleName = trim($vehicleName);

    // Check if vehicle already exists
    $check = $db->prepare('SELECT idVehicle FROM Vehicle WHERE nameVehicle = :name LIMIT 1');
    $check->execute([':name' => $vehicleName]);
    if ($check->fetch()) {
        echo json_encode(['error' => 'Vehicle already exists in database.']);
        exit;
    }

    // Get next vehicle ID
    $row = $db->query('SELECT COALESCE(MAX(idVehicle), 0) AS m FROM Vehicle')->fetch(PDO::FETCH_ASSOC);
    $newId = (int)$row['m'] + 1;

    // Insert new vehicle
    $stmt = $db->prepare('INSERT INTO Vehicle (idVehicle, nameVehicle) VALUES (:idVehicle, :nameVehicle)');
    $stmt->execute([':idVehicle' => $newId, ':nameVehicle' => $vehicleName]);

    echo json_encode(['success' => true, 'idVehicle' => $newId, 'nameVehicle' => $vehicleName]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
