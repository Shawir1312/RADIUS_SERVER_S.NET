<?php
/**
 * AJAX Endpoint - Get Resellers (Profiles) for a specific Router
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

auth_check();

$router_id = (int)get('router_id');

header('Content-Type: application/json');

if ($router_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch profiles that belong to this router (or all routers) and have reseller_percent > 0
$sql = "SELECT id, name, price, reseller_percent 
        FROM profiles 
        WHERE (router_id = ? OR router_id IS NULL) 
          AND reseller_percent > 0 
          AND is_active = 1
        ORDER BY name ASC";
$profiles = db_fetch_all($sql, 'i', [$router_id]);

echo json_encode($profiles);
