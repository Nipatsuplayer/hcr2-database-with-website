<?php

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
        return json_encode(array('error' => "Database error: " . $e->getMessage()));
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
        case 'records':
            $sql = "SELECT
                        wr.rowid AS idRecord,
                        wr.distance,
                        wr.current,
                        m.nameMap AS map_name,
                        v.nameVehicle AS vehicle_name,
                        p.namePlayer AS player_name,
                        p.country AS player_country
                    FROM WorldRecord AS wr
                    JOIN Map AS m ON wr.idMap = m.idMap
                    JOIN Vehicle AS v ON wr.idVehicle = v.idVehicle
                    JOIN Player AS p ON wr.idPlayer = p.idPlayer
                    WHERE wr.current = 1"; 
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
