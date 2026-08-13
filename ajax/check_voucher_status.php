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
            a.full_name AS reseller_name,
            a.role     AS reseller_role
        FROM vouchers v
        LEFT JOIN profiles p ON v.profile_id = p.id
        LEFT JOIN admins   a ON v.generated_by = a.id
        WHERE v.username = ?
    ";
    
    $voucher = db_fetch_one($sql, 's', [$username]);

    if ($voucher) {
        // Jika generate oleh superadmin, tampilkan nama perusahaan bukan nama personal
        if ($voucher['reseller_role'] === 'superadmin') {
            $reseller_display = APP_COMPANY; // dari config.php
        } else {
            $reseller_display = $voucher['reseller_name'] ?: APP_COMPANY;
        }

        echo json_encode([
            'success'        => true,
            'username'       => $voucher['username'],
            'status'         => $voucher['status'],
            'profile_name'   => $voucher['profile_name'] ?: '-',
            'reseller_name'  => $reseller_display,
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Voucher not found']);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
