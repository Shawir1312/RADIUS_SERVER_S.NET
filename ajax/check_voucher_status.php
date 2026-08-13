<?php
/**
 * AJAX — Check Voucher Status (Public API for Mikrotik Login Page)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

// Enable CORS so Mikrotik Hotspot page can fetch this API
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');
if (!$username) {
    echo json_encode(['success' => false, 'error' => 'Username required']);
    exit;
}

try {
    $sql = "
        SELECT 
            v.username, 
            v.status, 
            p.name AS profile_name, 
            a.full_name AS reseller_name
        FROM vouchers v
        LEFT JOIN profiles p ON v.profile_id = p.id
        LEFT JOIN admins a ON v.generated_by = a.id
        WHERE v.username = ?
    ";
    
    $voucher = db_fetch_one($sql, 's', [$username]);

    if ($voucher) {
        // Return details
        echo json_encode([
            'success'        => true,
            'username'       => $voucher['username'],
            'status'         => $voucher['status'], // 'unused', 'active', 'expired', 'deleted'
            'profile_name'   => $voucher['profile_name'] ?: 'Unknown Profile',
            'reseller_name'  => $voucher['reseller_name'] ?: 'Unknown Reseller'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Voucher not found']);
    }

} catch (Throwable $e) {
    // Return standard error json
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
