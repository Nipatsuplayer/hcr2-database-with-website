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

$mapId = $data['mapId'] ?? null;
$vehicleId = $data['vehicleId'] ?? null;
$distance = $data['distance'] ?? null;
$playerId = $data['playerId'] ?? null;
$newPlayerName = $data['newPlayerName'] ?? null;
$country = $data['country'] ?? null;
$playerName = $data['playerName'] ?? null;
$tuningSetupId = $data['tuningSetupId'] ?? null;

try {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($mapId) || empty($vehicleId) || empty($distance)) {
        echo json_encode(['error' => 'Missing required fields (map, vehicle, or distance).']);
        exit;
    }

    $mapId = (int)$mapId;
    $vehicleId = (int)$vehicleId;
    if (!is_numeric($distance) || (int)$distance <= 0) {
        echo json_encode(['error' => 'Distance must be a positive number.']);
        exit;
    }
    $distance = (int)$distance;

    $db->beginTransaction();

    if (!empty($newPlayerName)) {
        $row = $db->query('SELECT COALESCE(MAX(idPlayer), 0) AS m FROM Player')->fetch(PDO::FETCH_ASSOC);
        $newId = (int)$row['m'] + 1;

        $stmt = $db->prepare("INSERT INTO Player (idPlayer, namePlayer, country) VALUES (:idPlayer, :namePlayer, :country)");
        $stmt->execute([':idPlayer' => $newId, ':namePlayer' => $newPlayerName, ':country' => $country]);
        $playerId = $newId;
    }

    if ((is_null($playerId) || $playerId === '') && !empty($playerName)) {
        $stmt = $db->prepare('SELECT idPlayer FROM Player WHERE namePlayer = :name LIMIT 1');
        $stmt->execute([':name' => $playerName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $playerId = (int)$row['idPlayer'];
        }
    }

    if (is_null($playerId) && empty($newPlayerName)) {
        $db->rollBack();
        echo json_encode(['error' => 'No valid player selected or provided.']);
        exit;
    }

    if (!is_null($playerId)) {
        $check = $db->prepare('SELECT 1 FROM Player WHERE idPlayer = :id LIMIT 1');
        $check->execute([':id' => $playerId]);
        if (!$check->fetch()) {
            $db->rollBack();
            echo json_encode(['error' => 'Selected player does not exist.']);
            exit;
        }
    }

    $playerId = is_null($playerId) || $playerId === '' ? null : (int)$playerId;

    $stmt = $db->prepare("DELETE FROM WorldRecord WHERE idMap = :idMap AND idVehicle = :idVehicle");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId]);

    $stmt = $db->prepare("INSERT INTO WorldRecord (idMap, idVehicle, idPlayer, distance, current, idTuningSetup) VALUES (:idMap, :idVehicle, :idPlayer, :distance, 1, :idTuningSetup)");
    $stmt->execute([':idMap' => $mapId, ':idVehicle' => $vehicleId, ':idPlayer' => $playerId, ':distance' => $distance, ':idTuningSetup' => $tuningSetupId]);

    $db->commit();

    $mapStmt = $db->prepare('SELECT nameMap FROM Map WHERE idMap = :idMap LIMIT 1');
    $mapStmt->execute([':idMap' => $mapId]);
    $mapRow = $mapStmt->fetch(PDO::FETCH_ASSOC);
    $mapName = $mapRow ? $mapRow['nameMap'] : 'Unknown';

    $vehicleStmt = $db->prepare('SELECT nameVehicle FROM Vehicle WHERE idVehicle = :idVehicle LIMIT 1');
    $vehicleStmt->execute([':idVehicle' => $vehicleId]);
    $vehicleRow = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
    $vehicleName = $vehicleRow ? $vehicleRow['nameVehicle'] : 'Unknown';

    $playerName = 'Unknown';
    if (!is_null($playerId)) {
        $playerStmt = $db->prepare('SELECT namePlayer FROM Player WHERE idPlayer = :idPlayer LIMIT 1');
        $playerStmt->execute([':idPlayer' => $playerId]);
        $playerRow = $playerStmt->fetch(PDO::FETCH_ASSOC);
        $playerName = $playerRow ? $playerRow['namePlayer'] : 'Unknown';
    } elseif (!empty($newPlayerName)) {
        $playerName = $newPlayerName;
    }

    echo json_encode([
        'success' => true,
        'playerId' => $playerId,
        'mapName' => $mapName,
        'vehicleName' => $vehicleName,
        'playerName' => $playerName,
        'distance' => $distance
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>
