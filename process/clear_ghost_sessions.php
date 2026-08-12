<?php
/**
 * Process - Clear Ghost Sessions & Revert Vouchers
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();
if (current_admin()['role'] !== 'superadmin') {
    flash_set('error', 'Hanya superadmin yang dapat melakukan tindakan ini.');
    header('Location: /index.php?page=active_users');
    exit;
}

try {
    $router_id = (int)get('router_id');
    if (!$router_id) {
        throw new Exception('Pilih router/cabang terlebih dahulu.');
    }
    
    // Ambil IP NAS dari router tersebut
    $router = db_fetch_one("SELECT ip_address, nas_ip FROM routers WHERE id = ?", 'i', [$router_id]);
    if (!$router) throw new Exception('Router tidak ditemukan.');
    
    // radacct biasanya mencatat IP berdasarkan yang datang, bisa ip_address API atau nas_ip
    // Kita gunakan keduanya untuk pencarian yang aman
    $ip1 = $router['ip_address'];
    $ip2 = $router['nas_ip'] && $router['nas_ip'] !== '0.0.0.0/0' ? $router['nas_ip'] : $ip1;
    
    db_begin();
    
    // Cari semua session nyangkut khusus untuk router ini
    $ghosts = db_fetch_all("
        SELECT DISTINCT username 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND acctsessiontime = 0 
          AND acctinputoctets = 0
          AND (nasipaddress = ? OR nasipaddress = ?)
    ", 'ss', [$ip1, $ip2]);
    
    $count = 0;
    foreach ($ghosts as $g) {
        $u = $g['username'];
        
        // 1. Hapus dari radacct
        db_execute("DELETE FROM radacct WHERE username = ? AND acctstoptime IS NULL AND acctsessiontime = 0 AND acctinputoctets = 0", 's', [$u]);
        
        // Cek apakah radacct untuk user ini sudah kosong sepenuhnya (artinya dia memang belum pernah pakai sama sekali)
        $has_other_usage = db_fetch_one("SELECT id FROM radacct WHERE username = ?", 's', [$u]);
        
        if (!$has_other_usage) {
            // 2. Kembalikan status voucher ke unused
            db_execute("UPDATE vouchers SET status = 'unused', used_at = NULL, expired_at = NULL WHERE username = ? AND status = 'active'", 's', [$u]);
            
            // 3. Hapus log penjualan (sales_log) yang tercatat otomatis karena false-start ini
            db_execute("DELETE FROM sales_log WHERE voucher_username = ? ORDER BY id DESC LIMIT 1", 's', [$u]);
        }
        
        $count++;
    }
    
    db_commit();
    flash_set('success', "Berhasil membersihkan {$count} sesi gantung (ghost). Voucher yang bersangkutan kini telah dikembalikan menjadi belum terpakai!");
} catch (Exception $e) {
    db_rollback();
    flash_set('error', 'Gagal: ' . $e->getMessage());
}

header('Location: /index.php?page=active_users');
exit;
