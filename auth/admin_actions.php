<?php
require_once __DIR__ . '/check_auth.php';
ensure_authorized_json();

$db_file = realpath(__DIR__ . '/../main.sqlite');
$backups_dir = __DIR__ . '/../backups';

if (!file_exists($backups_dir)) @mkdir($backups_dir, 0750, true);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_db') {
    if (!file_exists($db_file)) {
        http_response_code(404);
        echo "Database file not found.";
        exit;
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="main.sqlite"');
    header('Content-Length: ' . filesize($db_file));
    readfile($db_file);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? null;

try {
    if ($action === 'create_backup') {
        $ts = date('Ymd-His');
        $fname = "main-{$ts}.sqlite";
        $dst = $backups_dir . '/' . $fname;
        if (!copy($db_file, $dst)) throw new Exception('Failed to copy DB file.');
        echo json_encode(['success' => true, 'filename' => $fname]);
        exit;
    }

    if ($action === 'list_backups') {
        $files = array_values(array_filter(scandir($backups_dir), function($f){ return $f !== '.' && $f !== '..'; }));
        $list = [];
        foreach ($files as $f) {
            $path = $backups_dir . '/' . $f;
            if (!is_file($path)) continue;
            $list[] = [
                'name' => $f,
                'size' => filesize($path),
                'mtime' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
        echo json_encode(['backups' => $list]);
        exit;
    }

    if ($action === 'delete') {
        $filename = $_POST['filename'] ?? '';
        $path = realpath($backups_dir . '/' . basename($filename));
        if (!$path || strpos($path, realpath($backups_dir)) !== 0) throw new Exception('Invalid filename');
        if (!file_exists($path)) throw new Exception('File not found');
        unlink($path);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'restore') {
        $filename = $_POST['filename'] ?? '';
        $path = realpath($backups_dir . '/' . basename($filename));
        if (!$path || strpos($path, realpath($backups_dir)) !== 0) throw new Exception('Invalid filename');
        if (!file_exists($path)) throw new Exception('File not found');
        $tmp = $db_file . '.restore_tmp';
        if (!copy($path, $tmp)) throw new Exception('Failed to copy backup to temp');
        if (!rename($tmp, $db_file)) {
            @unlink($tmp);
            throw new Exception('Failed to replace database file');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'import') {
        if (!isset($_FILES['sqlfile']) || !is_uploaded_file($_FILES['sqlfile']['tmp_name'])) {
            throw new Exception('No file uploaded');
        }
        $content = file_get_contents($_FILES['sqlfile']['tmp_name']);
        if ($content === false) throw new Exception('Failed to read uploaded file');

        $pdo = new PDO('sqlite:' . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        try {
            $pdo->exec($content);
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    if ($action === 'integrity') {
        $pdo = new PDO('sqlite:' . $db_file);
        $stmt = $pdo->query('PRAGMA integrity_check;');
        $result = $stmt->fetchColumn();
        echo json_encode(['ok' => true, 'result' => $result]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
