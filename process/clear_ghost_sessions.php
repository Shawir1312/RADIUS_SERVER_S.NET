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
    db_begin();
    
    // Cari semua session nyangkut (baru terhubung 0 detik, 0 bytes, dan belum logoff)
    $ghosts = db_fetch_all("
        SELECT DISTINCT username 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND acctsessiontime = 0 
          AND acctinputoctets = 0
    ");
    
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
