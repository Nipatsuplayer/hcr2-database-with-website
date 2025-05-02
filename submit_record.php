<?php
$db_file = './main.sqlite';

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(array('error' => "Database connection failed: " . $e->getMessage())));
}


$data = json_decode(file_get_contents('php://input'), true);

$mapId = $data['mapId'];
$vehicleId = $data['vehicleId'];
$distance = $data['distance'];
$playerId = $data['playerId'];
$newPlayerName = $data['newPlayerName'];
$country = $data['country'];

try {}
    if ($newPlayerName) {
        $stmt = $db->prepare("INSERT INTO Player (namePlayer, country) VALUES (:namePlayer, :country)");
        $stmt->execute([':namePlayer' => $newPlayerName, ':country' => $country]);
        $playerId = $db->lastInsertId();
    }

    
    $stmt = $db->prepare("DELETE FROM WorldRecord WHERE idMap = :idMap AND idVehicle = :idVehicle");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId]);

    
    $stmt = $db->prepare("INSERT INTO WorldRecord (idMap, idVehicle, idPlayer, distance, current) VALUES (:idMap, :idVehicle, :idPlayer, :distance, 1)");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId, ':idPlayer' => $playerId, ':distance' => $distance]);

    echo json_encode(['success' => true]);
}  catch(PDOException $e) {}
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
