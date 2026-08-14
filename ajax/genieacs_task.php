<?php
/**
 * AJAX - GenieACS Task (Refresh / Reboot / etc)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/GenieACS.php';

header('Content-Type: application/json');
auth_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$server_id = (int)($_POST['server_id'] ?? 0);
$dev_id = $_POST['dev_id'] ?? '';
$action = $_POST['action'] ?? '';

if (!$server_id || !$dev_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap.']);
    exit;
}

$server = db_fetch_one("SELECT * FROM genie_config WHERE id = ?", 'i', [$server_id]);
if (!$server) {
    echo json_encode(['success' => false, 'error' => 'Server tidak ditemukan.']);
    exit;
}

try {
    $api = new GenieACS($server['url'], $server['username'], $server['password']);
    
    if ($action === 'reboot') {
        $result = $api->reboot($dev_id);
        if ($api->error) throw new Exception($api->error);
        
        echo json_encode(['success' => true, 'message' => 'Perintah Reboot berhasil dikirim ke perangkat.']);
        
    } elseif ($action === 'refresh') {
        $result = $api->refresh($dev_id);
        if ($api->error) throw new Exception($api->error);
        
        echo json_encode(['success' => true, 'message' => 'Perintah Refresh parameter berhasil dikirim ke perangkat.']);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Aksi tidak valid.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Gagal mengirim perintah: ' . $e->getMessage()]);
}
