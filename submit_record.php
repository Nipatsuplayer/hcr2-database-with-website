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
$data = json_decode(file_get_contents('php://input'), true);

$mapId = $data['mapId'];
$vehicleId = $data['vehicleId'];
$distance = $data['distance'];
$playerId = $data['playerId'];
$newPlayerName = $data['newPlayerName'];
$country = $data['country'];

try {
    // If a new player is added, insert into the Player table
    if ($newPlayerName) {
        $stmt = $db->prepare("INSERT INTO Player (namePlayer, country) VALUES (:namePlayer, :country)");
        $stmt->execute([':namePlayer' => $newPlayerName, ':country' => $country]);
        $playerId = $db->lastInsertId(); // Get the new player's ID
    }

    // Delete old records for the same vehicle and map
    $stmt = $db->prepare("DELETE FROM WorldRecord WHERE idMap = :idMap AND idVehicle = :idVehicle");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId]);

    // Insert the new record into the WorldRecord table
    $stmt = $db->prepare("INSERT INTO WorldRecord (idMap, idVehicle, idPlayer, distance, current) VALUES (:idMap, :idVehicle, :idPlayer, :distance, 1)");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId, ':idPlayer' => $playerId, ':distance' => $distance]);

    // Return success and the player id used for this record (new or existing)
    echo json_encode(['success' => true, 'playerId' => $playerId]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
