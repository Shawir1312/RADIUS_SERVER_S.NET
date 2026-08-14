<?php
/**
 * AJAX - Test GenieACS Connection
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/GenieACS.php';

header('Content-Type: application/json');
auth_check();

$admin = current_admin();
if ($admin['role'] !== 'superadmin') {
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

// CSRF Check (using POST for safety)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID server tidak valid.']);
    exit;
}

$server = db_fetch_one("SELECT * FROM genie_config WHERE id = ?", 'i', [$id]);
if (!$server) {
    echo json_encode(['success' => false, 'error' => 'Server tidak ditemukan di database.']);
    exit;
}

try {
    $api = new GenieACS($server['url'], $server['username'], $server['password']);
    // Test by fetching 1 device
    $devices = $api->getDevices('{}', '_id'); 
    
    if ($api->error) {
        throw new Exception($api->error);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Koneksi Berhasil! Terhubung ke server GenieACS.',
        'devices_count' => count($devices) // Just as a fun info
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Koneksi gagal: ' . $e->getMessage()]);
}
