<?php

require_once __DIR__ . '/maintenance_helpers.php';
require_once __DIR__ . '/../auth/config.php';
enforce_maintenance_json();

header('Content-Type: application/json');

try {
    $db = get_database_connection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array('error' => "Database connection failed: " . $e->getMessage()));
    exit;
}

function get_data($db, $table, $select = '*', $where = '', $order = '', $limit = '') {
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
                       string_agg(tp.nameTuningPart, ', ') as parts
                FROM TuningSetup ts
                JOIN TuningSetupParts tsp ON ts.idTuningSetup = tsp.idTuningSetup
                JOIN TuningPart tp ON tsp.idTuningPart = tp.idTuningPart
                GROUP BY ts.idTuningSetup
                ORDER BY ts.idTuningSetup
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as &$row) {
                if ($row['parts'] !== null) {
                    $parts = explode(', ', $row['parts']);
                    $row['parts'] = array_map(function($name) {
                        return ['nameTuningPart' => $name];
                    }, $parts);
                } else {
                    $row['parts'] = [];
                }
            }
            echo json_encode($data);
            break;
        case 'records':
            try {
                $db->exec("ALTER TABLE WorldRecord ADD COLUMN IF NOT EXISTS questionable SMALLINT DEFAULT 0");
                $db->exec("ALTER TABLE WorldRecord ADD COLUMN IF NOT EXISTS questionable_reason TEXT DEFAULT NULL");
            } catch (Exception $e) {
            }
            $sql = "SELECT
                        wr.idRecord AS idRecord,
                        wr.distance,
                        wr.current,
                        wr.idTuningSetup,
                        wr.questionable,
                        COALESCE(wr.questionable_reason, '') as questionable_reason,
                        m.nameMap AS map_name,
                        v.nameVehicle AS vehicle_name,
                        p.namePlayer AS player_name,
                        p.country AS player_country,
                        string_agg(tp.nameTuningPart, ', ') as tuning_parts
                    FROM WorldRecord AS wr
                    JOIN Map AS m ON wr.idMap = m.idMap
                    JOIN Vehicle AS v ON wr.idVehicle = v.idVehicle
                    JOIN Player AS p ON wr.idPlayer = p.idPlayer
                    LEFT JOIN TuningSetupParts tsp ON wr.idTuningSetup = tsp.idTuningSetup
                    LEFT JOIN TuningPart tp ON tsp.idTuningPart = tp.idTuningPart
                    WHERE wr.current = 1
                    GROUP BY wr.idRecord, wr.distance, wr.current, wr.idTuningSetup, wr.questionable, wr.questionable_reason, m.nameMap, v.nameVehicle, p.namePlayer, p.country";
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