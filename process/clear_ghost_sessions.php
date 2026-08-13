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
    
    // ---------------------------------------------------------
    // KASUS 1: False Start (0 Detik / 0 Bytes) -> Kembalikan Voucher
    // ---------------------------------------------------------
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
        db_execute("DELETE FROM radacct WHERE username = ? AND acctstoptime IS NULL AND acctsessiontime = 0 AND acctinputoctets = 0", 's', [$u]);
        
        $has_other_usage = db_fetch_one("SELECT radacctid FROM radacct WHERE username = ?", 's', [$u]);
        if (!$has_other_usage) {
            db_execute("UPDATE vouchers SET status = 'unused', used_at = NULL, expired_at = NULL WHERE username = ? AND status = 'active'", 's', [$u]);
            db_execute("DELETE FROM sales_log WHERE voucher_username = ? ORDER BY id DESC LIMIT 1", 's', [$u]);
        }
        $count++;
    }

    // ---------------------------------------------------------
    // KASUS 2: Router Mati Listrik (Sesi Menggantung dengan Traffic)
    // ---------------------------------------------------------
    // Sesi masih NULL acctstoptime, tapi waktu/traffic > 0.
    // Kita paksa tutup sesinya agar sisa waktu voucher tidak terpotong terus.
    $active_ghosts = db_fetch_all("
        SELECT radacctid, username 
        FROM radacct 
        WHERE acctstoptime IS NULL 
          AND (acctsessiontime > 0 OR acctinputoctets > 0)
          AND (nasipaddress = ? OR nasipaddress = ?)
    ", 'ss', [$ip1, $ip2]);

    $count_closed = 0;
    foreach ($active_ghosts as $ag) {
        // Jika acctsessiontime ada nilainya, acctstoptime = acctstarttime + acctsessiontime. 
        // Kalau tidak, pakai NOW().
        db_execute("
            UPDATE radacct 
            SET acctstoptime = CASE 
                    WHEN acctsessiontime > 0 THEN DATE_ADD(acctstarttime, INTERVAL acctsessiontime SECOND)
                    ELSE NOW() 
                END,
                acctterminatecause = 'NAS-Error'
            WHERE radacctid = ?
        ", 'i', [$ag['radacctid']]);
        
        $count_closed++;
    }
    
    db_commit();
    flash_set('success', "Berhasil mereset {$count} sesi 0-detik (dikembalikan jadi unused) dan menutup paksa {$count_closed} sesi aktif (mencegah waktu voucher habis akibat router mati).");
} catch (Exception $e) {
    db_rollback();
    flash_set('error', 'Gagal: ' . $e->getMessage());
}

header('Location: /index.php?page=active_users');
exit;
