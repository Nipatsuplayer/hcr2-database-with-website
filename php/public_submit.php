<?php
require_once __DIR__ . '/../auth/config.php';
require_once __DIR__ . '/maintenance_helpers.php';
enforce_maintenance_json();

header('Content-Type: application/json; charset=utf-8');

// hCaptcha verification
function verify_hcaptcha($token, $secret) {
    if (empty($token) || empty($secret)) {
        return false;
    }
    $url = 'https://hcaptcha.com/siteverify';
    $postFields = http_build_query([
        'secret' => $secret,
        'response' => $token
    ]);

    // Prefer curl if available
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return false;
        }

        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }

    // Fallback to file_get_contents if allow_url_fopen is enabled
    if (ini_get('allow_url_fopen')) {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $postFields,
                'timeout' => 5
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return false;
        }
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }

    // Neither curl nor file_get_contents available for outbound HTTP
    error_log('verify_hcaptcha: no HTTP client available (curl missing and allow_url_fopen disabled)');
    return false;
}

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

$hcaptcha_response = isset($data['h_captcha_response']) ? trim($data['h_captcha_response']) : '';

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
// Tuning parts (expected as array of names)
$tuningParts = isset($data['tuningParts']) ? $data['tuningParts'] : [];
if (!is_array($tuningParts)) {
    // Try to handle comma-separated string
    if (is_string($tuningParts)) {
        $tuningParts = array_filter(array_map('trim', explode(',', $tuningParts)));
    } else {
        $tuningParts = [];
    }
}

// Verify hCaptcha first
if (!verify_hcaptcha($hcaptcha_response, $HCAPTCHA_SECRET_KEY)) {
    http_response_code(400);
    echo json_encode(['error' => 'hCaptcha verification failed. Please try again.']);
    exit;
}

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

// Validate tuning parts server-side: require 3 or 4 parts
if (count($tuningParts) < 3 || count($tuningParts) > 4) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide 3 or 4 tuning parts for the record.']);
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
        tuningParts TEXT,
        submitterIp TEXT,
        status TEXT DEFAULT 'pending',
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Ensure the tuningParts column exists in older databases where the table was created previously
    try {
        $cols = $db->query("PRAGMA table_info('PendingSubmission')")->fetchAll(PDO::FETCH_ASSOC);
        $hasTuning = false;
        foreach ($cols as $c) {
            if (isset($c['name']) && $c['name'] === 'tuningParts') { $hasTuning = true; break; }
        }
        if (!$hasTuning) {
            // SQLite supports ADD COLUMN; new column will be NULL by default
            $db->exec("ALTER TABLE PendingSubmission ADD COLUMN tuningParts TEXT");
        }
    } catch (Exception $e) {
        // Non-fatal: if migration fails, continue and rely on insert to throw a clear error
        error_log('PendingSubmission migration: ' . $e->getMessage());
    }

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
    $stmt = $db->prepare('INSERT INTO PendingSubmission (idMap, idVehicle, distance, playerName, playerCountry, tuningParts, submitterIp) VALUES (:idMap, :idVehicle, :distance, :playerName, :playerCountry, :tuningParts, :ip)');
    $stmt->execute([
        ':idMap' => $mapId,
        ':idVehicle' => $vehicleId,
        ':distance' => $distance,
        ':playerName' => $playerName,
        ':playerCountry' => $playerCountry,
        ':tuningParts' => implode(', ', $tuningParts),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode(['success' => true, 'message' => 'Submission received and is pending review by admins.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

?>
