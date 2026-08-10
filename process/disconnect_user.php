<?php
/**
 * Process — Disconnect User
 * Sends CoA Disconnect-Request, fallback to RouterOS API.
 * Returns JSON response.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once LIB_PATH . '/radius_coa.php';

auth_check();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']); exit;
}

if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF']); exit;
}

$username   = sanitize(post('username'));
$session_id = post('session_id', '');

if (!$username) {
    echo json_encode(['success' => false, 'error' => 'Username required']); exit;
}

// Find the active session to get the NAS IP
$session = db_fetch_one(
    "SELECT ra.nasipaddress, ra.acctsessionid, r.id AS router_id
     FROM radacct ra
     LEFT JOIN routers r ON r.ip_address = ra.nasipaddress
     WHERE ra.username = ? AND ra.acctstoptime IS NULL
     ORDER BY ra.acctstarttime DESC LIMIT 1",
    's', [$username]
);

if (!$session) {
    echo json_encode(['success' => false, 'error' => 'Sesi tidak ditemukan di radacct']); exit;
}

$router = null;
if ($session['router_id']) {
    $router = get_router($session['router_id']);
    if (!can_access_router($session['router_id'])) {
        echo json_encode(['success' => false, 'error' => 'Akses ditolak untuk router ini']); exit;
    }
}

if (!$router) {
    echo json_encode(['success' => false, 'error' => 'Router tidak ditemukan untuk sesi ini']); exit;
}

// Try disconnect
$result = disconnect_user_from_router($router, $username, $session['acctsessionid'] ?: null);

if ($result['success']) {
    audit_log('disconnect_user', $username, $router['id'], json_encode(['method' => $result['method']]));
    // Also mark radacct as stopped
    db_execute(
        "UPDATE radacct SET acctstoptime = NOW(), acctterminatecause = 'Admin-Reset'
         WHERE username = ? AND acctstoptime IS NULL ORDER BY acctstarttime DESC LIMIT 1",
        's', [$username]
    );
}

echo json_encode($result);
