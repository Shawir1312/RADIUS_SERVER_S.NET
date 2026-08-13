<?php
/**
 * CRON - Auto Clear Ghost Sessions
 * Dijalankan setiap menit untuk mendeteksi router yang mati listrik 
 * atau sesi yang terputus secara tidak wajar.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

// Waktu toleransi (dalam menit). 
// Jika Mikrotik diset Interim-Update setiap 1-3 menit, 
// maka jika 10 menit tidak ada update, kita anggap routernya mati / koneksi terputus.
$timeout_minutes = 10; 

try {
    db_begin();
    
    // ---------------------------------------------------------
    // KASUS 1: False Start (0 Detik / 0 Bytes) yang nyangkut lama
    // ---------------------------------------------------------
    $ghosts_0 = db_fetch_all("
        SELECT DISTINCT username 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND acctsessiontime = 0 
          AND acctinputoctets = 0
          AND acctstarttime < DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ", 'i', [$timeout_minutes]);
    
    foreach ($ghosts_0 as $g) {
        $u = $g['username'];
        db_execute("DELETE FROM radacct WHERE username = ? AND acctstoptime IS NULL AND acctsessiontime = 0 AND acctinputoctets = 0", 's', [$u]);
        
        $has_other_usage = db_fetch_one("SELECT radacctid FROM radacct WHERE username = ?", 's', [$u]);
        if (!$has_other_usage) {
            db_execute("UPDATE vouchers SET status = 'unused', used_at = NULL, expired_at = NULL WHERE username = ? AND status = 'active'", 's', [$u]);
            db_execute("DELETE FROM sales_log WHERE voucher_username = ? ORDER BY id DESC LIMIT 1", 's', [$u]);
        }
    }
    
    // ---------------------------------------------------------
    // KASUS 2: Router Mati Listrik (Sesi Menggantung dengan Traffic)
    // ---------------------------------------------------------
    // Cek last update (menggunakan acctupdatetime, jika NULL gunakan acctstarttime)
    $active_ghosts = db_fetch_all("
        SELECT radacctid, username, acctstarttime, acctsessiontime 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND (acctsessiontime > 0 OR acctinputoctets > 0)
          AND COALESCE(acctupdatetime, acctstarttime) < DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ", 'i', [$timeout_minutes]);

    foreach ($active_ghosts as $ag) {
        // Paksa tutup sesinya, amankan sisa waktu voucher!
        db_execute("
            UPDATE radacct 
            SET acctstoptime = CASE 
                    WHEN acctsessiontime > 0 THEN DATE_ADD(acctstarttime, INTERVAL acctsessiontime SECOND)
                    ELSE NOW() 
                END,
                acctterminatecause = 'NAS-Error-AutoClose'
            WHERE radacctid = ?
        ", 'i', [$ag['radacctid']]);
    }
    
    db_commit();
    
    // Output untuk log cron (hanya print jika ada eksekusi)
    $total_0 = count($ghosts_0);
    $total_active = count($active_ghosts);
    if ($total_0 > 0 || $total_active > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Auto-Clean Ghost: $total_0 sesi direset (unused), $total_active sesi aktif ditutup (router mati).\n";
    }
    
} catch (Exception $e) {
    db_rollback();
    echo "[" . date('Y-m-d H:i:s') . "] Error CRON Auto-Clean: " . $e->getMessage() . "\n";
}
