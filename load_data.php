<?php
// Database connection details
$db_file = './main.sqlite'; // Updated to use the valid SQLite database file

try {
    // Create a new PDO (PHP Data Object) connection to the SQLite database
    $db = new PDO("sqlite:" . $db_file);
    // Set PDO to throw exceptions on errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle database connection errors
    die("Database connection failed: " . $e->getMessage());
}

// Function to fetch data from the database and return as JSON
function get_data($db, $table, $select = '*', $where = '', $order = '', $limit = '') {
    try {
        // Construct the SQL query
        $sql = "SELECT $select FROM $table";
        if ($where) $sql .= " WHERE $where";
        if ($order) $sql .= " ORDER BY $order";
        if ($limit) $sql .= " LIMIT $limit";

        // Prepare the SQL statement
        $stmt = $db->prepare($sql);
        // Execute the query
        $stmt->execute();
        // Fetch all rows as an associative array
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return the data as JSON
        return json_encode($data);
    } catch (PDOException $e) {
        // Handle database query errors
        return json_encode(array('error' => "Database error: " . $e->getMessage()));
    }
}

// Set the content type to JSON
header('Content-Type: application/json');

// Determine the requested data based on the 'type' parameter
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    switch ($type) {
        case 'maps':
            // Fetch all maps
            echo get_data($db, 'Map');
            break;
        case 'vehicles':
            // Fetch all vehicles
            echo get_data($db, 'Vehicle');
            break;
        case 'players':
            // Fetch all players
            echo get_data($db, 'Player');
            break;
        case 'records':
            // Fetch all world records, joining with related tables to get more information
            $sql = "SELECT
                        wr.rowid AS idRecord, -- Include rowid as a unique identifier
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
                    WHERE wr.current = 1"; // Only fetch current world records
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($records);
            break;
        default:
            // Invalid data type requested
            echo json_encode(array('error' => 'Invalid data type'));
    }
} else {
    // No data type specified
    echo json_encode(array('error' => 'No data type specified'));
}
?>
