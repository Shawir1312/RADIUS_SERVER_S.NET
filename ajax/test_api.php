<?php
/**
 * AJAX — Test RouterOS API Connection
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once LIB_PATH . '/routeros_api.class.php';

auth_check();
session_write_close();
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id || !can_access_router($id)) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$router = get_router($id);
if (!$router) {
    echo json_encode(['success' => false, 'error' => 'Router not found']);
    exit;
}

try {
    ini_set('default_socket_timeout', 5);
    $api = new RouterosAPI();
    $api->debug = false;
    if ($api->connect($router['ip_address'], $router['api_user'], $router['api_password'], (int)$router['api_port'])) {
        $ident = $api->comm('/system/identity/print');
        $identity = $ident[0]['name'] ?? 'Unknown';
        echo json_encode(['success' => true, 'identity' => $identity]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Login gagal — periksa username/password API']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
