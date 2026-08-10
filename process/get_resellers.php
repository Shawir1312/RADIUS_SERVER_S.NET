<?php
/**
 * AJAX Endpoint - Get Resellers (Profiles) for a specific Router
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

$router_id = (int)get('router_id');

header('Content-Type: application/json');

if ($router_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch profiles that belong to this router (or all routers) and have reseller_percent > 0
$sql = "SELECT p.id, p.name, p.price, p.reseller_percent, p.router_id, r.name as router_name
        FROM profiles p
        LEFT JOIN routers r ON p.router_id = r.id
        WHERE (p.router_id = ? OR p.router_id IS NULL) 
          AND p.reseller_percent > 0 
          AND p.is_active = 1
        ORDER BY p.name ASC";
$profiles = db_fetch_all($sql, 'i', [$router_id]);

foreach ($profiles as &$p) {
    $pid = $p['id'];
    $total_used = db_fetch_one("SELECT COUNT(*) as c FROM vouchers WHERE profile_id = ? AND status IN ('active', 'expired')", 'i', [$pid])['c'] ?? 0;
    $total_billed = db_fetch_one("SELECT SUM(estimasi_voucher) as c FROM penagihan WHERE profile_id = ?", 'i', [$pid])['c'] ?? 0;
    $p['unbilled_vouchers'] = max(0, $total_used - $total_billed);
    $p['tekor_count'] = db_fetch_one("SELECT COUNT(*) as c FROM penagihan WHERE profile_id = ? AND status_kecocokan = 'tekor'", 'i', [$pid])['c'] ?? 0;
}

echo json_encode($profiles);
