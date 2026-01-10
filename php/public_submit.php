<?php
require_once __DIR__ . '/../auth/config.php';

header('Content-Type: application/json; charset=utf-8');

$db_file = __DIR__ . '/../main.sqlite';
try {
    $db = new PDO('sqlite:' . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST ?? [];

$mapId = isset($data['mapId']) ? (int)$data['mapId'] : null;
$vehicleId = isset($data['vehicleId']) ? (int)$data['vehicleId'] : null;
$distance = isset($data['distance']) ? (int)$data['distance'] : null;
$playerName = isset($data['playerName']) ? trim($data['playerName']) : '';
$playerCountry = isset($data['playerCountry']) ? trim($data['playerCountry']) : '';

// Multiple honeypot fields (all should be empty)
$honeypot_email = isset($data['hp_email']) ? trim($data['hp_email']) : '';
$honeypot_website = isset($data['hp_website']) ? trim($data['hp_website']) : '';
$honeypot_phone = isset($data['hp_phone']) ? trim($data['hp_phone']) : '';
$honeypot_comments = isset($data['hp_comments']) ? trim($data['hp_comments']) : '';
$form_load_time = isset($data['form_load_time']) ? (int)$data['form_load_time'] : 0;
$submission_time = isset($data['submission_time']) ? (int)$data['submission_time'] : 0;

if (empty($mapId) || empty($vehicleId) || empty($distance) || empty($playerName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (map, vehicle, distance, or player name).']);
    exit;
}

// Honeypot validation: if ANY honeypot field is filled, reject
if (!empty($honeypot_email) || !empty($honeypot_website) || !empty($honeypot_phone) || !empty($honeypot_comments)) {
    http_response_code(400);
    echo json_encode(['error' => 'Spam detected']);
    exit;
}

// Timing validation: reject if form was submitted too quickly (less than 2 seconds)
if ($form_load_time > 0 && $submission_time > 0) {
    $time_spent = $submission_time - $form_load_time;
    if ($time_spent < 2000) { // 2 seconds minimum
        http_response_code(429);
        echo json_encode(['error' => 'Please take your time to fill out the form. Submissions that are too fast are rejected.']);
        exit;
    }
    // Also reject if suspiciously fast (less than 1 second) - likely automated
    if ($time_spent < 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Spam detected']);
        exit;
    }
}

if ($distance <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Distance must be a positive number.']);
    exit;
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS PendingSubmission (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        idMap INTEGER,
        idVehicle INTEGER,
        distance INTEGER,
        playerName TEXT,
        playerCountry TEXT,
        submitterIp TEXT,
        status TEXT DEFAULT 'pending',
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip) {
        $rstmt = $db->prepare("SELECT COUNT(1) AS c FROM PendingSubmission WHERE submitterIp = :ip AND submitted_at >= datetime('now','-1 hour')");
        $rstmt->execute([':ip' => $ip]);
        $rc = $rstmt->fetch(PDO::FETCH_ASSOC);
        if ($rc && isset($rc['c']) && (int)$rc['c'] >= 5) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
            exit;
        }
    }
    $stmt = $db->prepare('INSERT INTO PendingSubmission (idMap, idVehicle, distance, playerName, playerCountry, submitterIp) VALUES (:idMap, :idVehicle, :distance, :playerName, :playerCountry, :ip)');
    $stmt->execute([
        ':idMap' => $mapId,
        ':idVehicle' => $vehicleId,
        ':distance' => $distance,
        ':playerName' => $playerName,
        ':playerCountry' => $playerCountry,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode(['success' => true, 'message' => 'Submission received and is pending review by admins.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

?>
