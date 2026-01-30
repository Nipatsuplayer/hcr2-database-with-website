<?php

require_once __DIR__ . '/maintenance_helpers.php';
enforce_maintenance_json();

header('Content-Type: application/json');

$db_file = __DIR__ . '/../main.sqlite';
try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(array('error' => "Database connection failed: " . $e->getMessage()));
    exit;
}

function get_data($db, $table, $select = '*', $where = '', $order = '', $limit = '') {
    // Whitelist allowed tables to prevent SQL injection
    $allowed_tables = ['Map', 'Vehicle', 'Player', 'TuningPart'];
    if (!in_array($table, $allowed_tables)) {
        return json_encode(array('error' => 'Invalid table'));
    }
    
    try {
        $sql = "SELECT $select FROM $table";
        if ($where) $sql .= " WHERE $where";
        if ($order) $sql .= " ORDER BY $order";
        if ($limit) $sql .= " LIMIT $limit";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($data);
    } catch (PDOException $e) {
        return json_encode(array('error' => 'Database error'));
    }
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];
    switch ($type) {
        case 'maps':
            echo get_data($db, 'Map');
            break;
        case 'vehicles':
            echo get_data($db, 'Vehicle');
            break;
        case 'players':
            echo get_data($db, 'Player');
            break;
        case 'tuning_parts':
            echo get_data($db, 'TuningPart', '*', '', 'nameTuningPart');
            break;
        case 'tuning_setups':
            $sql = "
                SELECT ts.idTuningSetup,
                       GROUP_CONCAT(tp.nameTuningPart, ', ') as parts
                FROM TuningSetup ts
                JOIN TuningSetupParts tsp ON ts.idTuningSetup = tsp.idTuningSetup
                JOIN TuningPart tp ON tsp.idTuningPart = tp.idTuningPart
                GROUP BY ts.idTuningSetup
                ORDER BY ts.idTuningSetup
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // For each setup, get the parts as array
            foreach ($data as &$row) {
                $parts = explode(', ', $row['parts']);
                $row['parts'] = array_map(function($name) {
                    return ['nameTuningPart' => $name];
                }, $parts);
            }
            echo json_encode($data);
            break;
        case 'records':
            $sql = "SELECT
                        wr.rowid AS idRecord,
                        wr.distance,
                        wr.current,
                        wr.idTuningSetup,
                        m.nameMap AS map_name,
                        v.nameVehicle AS vehicle_name,
                        p.namePlayer AS player_name,
                        p.country AS player_country,
                        GROUP_CONCAT(tp.nameTuningPart, ', ') as tuning_parts
                    FROM WorldRecord AS wr
                    JOIN Map AS m ON wr.idMap = m.idMap
                    JOIN Vehicle AS v ON wr.idVehicle = v.idVehicle
                    JOIN Player AS p ON wr.idPlayer = p.idPlayer
                    LEFT JOIN TuningSetupParts tsp ON wr.idTuningSetup = tsp.idTuningSetup
                    LEFT JOIN TuningPart tp ON tsp.idTuningPart = tp.idTuningPart
                    WHERE wr.current = 1
                    GROUP BY wr.rowid, wr.distance, wr.current, wr.idTuningSetup, m.nameMap, v.nameVehicle, p.namePlayer, p.country"; 
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($records);
            break;
        default:
            echo json_encode(array('error' => 'Invalid data type'));
    }
} else {
    echo json_encode(array('error' => 'No data type specified'));
}
?>
