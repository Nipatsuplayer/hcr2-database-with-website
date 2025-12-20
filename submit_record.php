<?php
// Database connection details
$db_file = './main.sqlite';

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('error' => "Database connection failed: " . $e->getMessage())));
}

// Get the submitted data
// Try to decode JSON payload. If absent or invalid, fall back to $_POST for robustness.
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // fall back to form-encoded POST if present
    $data = $_POST ?? [];
}

// Safely extract fields (avoid notices when keys missing)
$mapId = $data['mapId'] ?? null;
$vehicleId = $data['vehicleId'] ?? null;
$distance = $data['distance'] ?? null;
$playerId = $data['playerId'] ?? null;
$newPlayerName = $data['newPlayerName'] ?? null;
$country = $data['country'] ?? null;
// optional: playerName (text shown in select) to help server lookup when client doesn't send numeric id
$playerName = $data['playerName'] ?? null;
// (playerName already set)

try {
    header('Content-Type: application/json; charset=utf-8');

    // Basic validation: ensure required fields exist
    if (empty($mapId) || empty($vehicleId) || empty($distance)) {
        echo json_encode(['error' => 'Missing required fields (map, vehicle, or distance).']);
        exit;
    }

    // Normalize numeric values
    $mapId = (int)$mapId;
    $vehicleId = (int)$vehicleId;
    // distance should be positive integer
    if (!is_numeric($distance) || (int)$distance <= 0) {
        echo json_encode(['error' => 'Distance must be a positive number.']);
        exit;
    }
    $distance = (int)$distance;

    // Use a transaction to keep operations atomic
    $db->beginTransaction();

    // If a new player is added, insert into the Player table with a new numeric id
    if (!empty($newPlayerName)) {
        // Compute next idPlayer because schema does not use AUTOINCREMENT
        $row = $db->query('SELECT COALESCE(MAX(idPlayer), 0) AS m FROM Player')->fetch(PDO::FETCH_ASSOC);
        $newId = (int)$row['m'] + 1;

        $stmt = $db->prepare("INSERT INTO Player (idPlayer, namePlayer, country) VALUES (:idPlayer, :namePlayer, :country)");
        $stmt->execute([':idPlayer' => $newId, ':namePlayer' => $newPlayerName, ':country' => $country]);
        $playerId = $newId; // use the newly created id
    }

    // If no numeric playerId was provided but a playerName was, try to look it up
    if ((is_null($playerId) || $playerId === '') && !empty($playerName)) {
        $stmt = $db->prepare('SELECT idPlayer FROM Player WHERE namePlayer = :name LIMIT 1');
        $stmt->execute([':name' => $playerName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $playerId = (int)$row['idPlayer'];
        }
    }

    // If still no playerId and no new player provided, that's an error
    if (is_null($playerId) && empty($newPlayerName)) {
        // rollback and error
        $db->rollBack();
        echo json_encode(['error' => 'No valid player selected or provided.']);
        exit;
    }

    // If we have a playerId, verify it exists in Player table
    if (!is_null($playerId)) {
        $check = $db->prepare('SELECT 1 FROM Player WHERE idPlayer = :id LIMIT 1');
        $check->execute([':id' => $playerId]);
        if (!$check->fetch()) {
            $db->rollBack();
            echo json_encode(['error' => 'Selected player does not exist.']);
            exit;
        }
    }

    // Ensure $playerId is an integer (existing player or newly created)
    $playerId = is_null($playerId) || $playerId === '' ? null : (int)$playerId;

    // Delete old records for the same vehicle and map (remove current flags)
    $stmt = $db->prepare("DELETE FROM WorldRecord WHERE idMap = :idMap AND idVehicle = :idVehicle");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId]);

    // Insert the new record into the WorldRecord table
    $stmt = $db->prepare("INSERT INTO WorldRecord (idMap, idVehicle, idPlayer, distance, current) VALUES (:idMap, :idVehicle, :idPlayer, :distance, 1)");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId, ':idPlayer' => $playerId, ':distance' => $distance]);

    $db->commit();

    // Return success and the player id used for this record (new or existing)
    echo json_encode(['success' => true, 'playerId' => $playerId]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
