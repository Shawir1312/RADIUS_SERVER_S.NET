<?php
/**
 * Process - Save Penagihan (Billing Report)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php?page=penagihan_report');
    exit;
}

if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    flash_set('error', 'Invalid CSRF.');
    header('Location: /index.php?page=penagihan_report');
    exit;
}

$router_id = (int)post('router_id');
$profile_id = (int)post('profile_id');
$total_pendapatan = (float)post('total_pendapatan');
$catatan = sanitize(post('catatan'));
$admin_id = current_admin()['id'];
$tanggal = date('Y-m-d');

if ($router_id <= 0 || $profile_id <= 0 || $total_pendapatan <= 0) {
    flash_set('error', 'Data tidak valid.');
    header('Location: /index.php?page=penagihan_report');
    exit;
}

try {
    // 1. Get profile data
    $profile = db_fetch_one("SELECT * FROM profiles WHERE id = ?", 'i', [$profile_id]);
    if (!$profile) {
        throw new Exception("Profile/Reseller tidak ditemukan.");
    }
    
    $price = (float)$profile['price'];
    $percent = (float)$profile['reseller_percent'];
    
    // 2. Calculate values
    $bagian_reseller = $total_pendapatan * ($percent / 100);
    $pendapatan_bersih = $total_pendapatan - $bagian_reseller;
    $estimasi_voucher = $price > 0 ? floor($total_pendapatan / $price) : 0;
    
    // 3. Calculate actual used vouchers vs billed
    // Total voucher used all time for this profile
    $total_used = db_fetch_one("SELECT COUNT(*) as c FROM vouchers WHERE profile_id = ? AND status IN ('active', 'expired')", 'i', [$profile_id])['c'] ?? 0;
    
    // Total voucher already billed all time for this profile
    $total_billed = db_fetch_one("SELECT SUM(estimasi_voucher) as c FROM penagihan WHERE profile_id = ?", 'i', [$profile_id])['c'] ?? 0;
    
    // Unbilled vouchers before this transaction
    $unbilled_vouchers = max(0, $total_used - $total_billed);
    
    // 4. Determine status
    if ($estimasi_voucher == $unbilled_vouchers) {
        $status = 'sesuai';
    } elseif ($estimasi_voucher < $unbilled_vouchers) {
        $status = 'tekor';
    } else {
        $status = 'lebih';
    }
    
    // 5. Save to database
    db_execute(
        "INSERT INTO penagihan (router_id, profile_id, total_pendapatan, bagian_reseller, pendapatan_bersih, estimasi_voucher, voucher_aktual, status_kecocokan, catatan, ditagih_oleh, tanggal) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        'iidddiissis',
        [
            $router_id, $profile_id, $total_pendapatan, $bagian_reseller, $pendapatan_bersih, 
            $estimasi_voucher, $unbilled_vouchers, $status, $catatan, $admin_id, $tanggal
        ]
    );
    
    // 6. Audit log
    audit_log('tambah_penagihan', "Reseller ID: {$profile_id} - Rp " . number_format($total_pendapatan, 0, ',', '.'), $router_id);
    
    flash_set('success', "Laporan penagihan berhasil disimpan! Status: " . strtoupper($status));
    
} catch (Throwable $e) {
    flash_set('error', 'Gagal menyimpan penagihan: ' . $e->getMessage());
}

header('Location: /index.php?page=penagihan_report');
