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
$timeout_minutes = 10; 

try {
    db_begin();
    
    // PERHATIAN: 
    // Fitur pembersihan otomatis (Cron) SEMENTARA DIMATIKAN karena Mikrotik Anda 
    // belum dikonfigurasi untuk mengirimkan "Interim-Update" ke RADIUS.
    // 
    // Jika Interim-Update tidak dikirim oleh Mikrotik, RADIUS tidak bisa membedakan
    // mana user yang MASIH AKTIF dan mana yang SUDAH MATI, karena data Download/Upload
    // akan terlihat selalu "0" atau sama terus, sehingga user asli malah ikut terhapus.
    
    /* 
    --- KODE DI BAWAH INI DINONAKTIFKAN DULU SAMPAI MIKROTIK DISETTING INTERIM-UPDATE ---

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
    
    $active_ghosts = db_fetch_all("
        SELECT radacctid, username, acctstarttime, acctsessiontime 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND (acctsessiontime > 0 OR acctinputoctets > 0)
          AND COALESCE(acctupdatetime, acctstarttime) < DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ", 'i', [$timeout_minutes]);

    foreach ($active_ghosts as $ag) {
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
    */
    
    db_commit();
    echo "[" . date('Y-m-d H:i:s') . "] CRON Auto-Clean dinonaktifkan sementara karena Mikrotik Interim-Update belum disetting.\n";
    
} catch (Exception $e) {
    db_rollback();
    echo "[" . date('Y-m-d H:i:s') . "] Error CRON Auto-Clean: " . $e->getMessage() . "\n";
}
