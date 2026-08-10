<?php
/**
 * AJAX — Router Status Check
 * Tests API connectivity and counts active users per router.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once LIB_PATH . '/routeros_api.class.php';

auth_check();
session_write_close(); // Prevent session locking during slow API calls
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id || !can_access_router($id)) {
    echo json_encode(['online' => false, 'active_users' => 0, 'error' => 'Access denied']);
    exit;
}

$router = get_router($id);
if (!$router) {
    echo json_encode(['online' => false, 'active_users' => 0, 'error' => 'Router not found']);
    exit;
}

// Check active users from radacct
$active = (int)(db_fetch_one(
    "SELECT COUNT(*) AS n FROM radacct WHERE nasipaddress = ? AND acctstoptime IS NULL",
    's', [$router['ip_address']]
)['n'] ?? 0);

// Try API connection
$online   = false;
$identity = '';
$error    = '';

try {
    ini_set('default_socket_timeout', 3);
    $api = new RouterosAPI();
    $api->debug = false;
    if ($api->connect($router['ip_address'], $router['api_user'], $router['api_password'], (int)$router['api_port'])) {
        $online   = true;
        $ident    = $api->comm('/system/identity/print');
        $identity = $ident[0]['name'] ?? '';

        // Update last_seen
        db_execute("UPDATE routers SET last_seen = NOW() WHERE id = ?", 'i', [$id]);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

echo json_encode([
    'online'       => $online,
    'active_users' => $active,
    'identity'     => $identity,
    'error'        => $error,
]);
