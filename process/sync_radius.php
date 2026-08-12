<?php
/**
 * Process — Sync RADIUS Data
 * Rebuilds radcheck and radreply from the vouchers table.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php?page=backup');
    exit;
}

// CSRF check
if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
    flash_set('error', 'Invalid CSRF token.');
    header('Location: /index.php?page=backup');
    exit;
}

$admin = current_admin();

// Check if admin is superadmin
if ($admin['role'] !== 'superadmin') {
    flash_set('error', 'Hanya superadmin yang dapat melakukan sinkronisasi.');
    header('Location: /index.php?page=backup');
    exit;
}

db_begin();
try {
    // 1. Truncate existing tables
    db()->query("TRUNCATE TABLE radcheck");
    db()->query("TRUNCATE TABLE radreply");

    // 2. Fetch all non-deleted vouchers with profile info
    $vouchers = db_fetch_all("
        SELECT v.username, v.password, p.duration_value, p.duration_unit, p.quota_mb, p.rate_up, p.rate_down 
        FROM vouchers v 
        JOIN profiles p ON v.profile_id = p.id 
        WHERE v.status != 'deleted'
    ");

    $count = 0;
    foreach ($vouchers as $v) {
        $u = $v['username'];
        $p = $v['password'];

        // radcheck
        db_execute("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Cleartext-Password', ':=', ?)", 'ss', [$u, $p]);
        
        // radreply: Session-Timeout
        $dur_s = duration_to_seconds($v['duration_value'], $v['duration_unit']);
        if ($dur_s > 0) {
            $dur_str = (string)$dur_s;
            db_execute("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Session-Timeout', ':=', ?)", 'ss', [$u, $dur_str]);
        }
        
        // radreply: Mikrotik-Rate-Limit
        $rl = rate_limit_attr($v['rate_up'] ?: '0', $v['rate_down'] ?: '0');
        if ($rl !== '0/0') {
            db_execute("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Mikrotik-Rate-Limit', '=', ?)", 'ss', [$u, $rl]);
        }
        
        // radreply: Mikrotik-Total-Limit
        $qm = (int)$v['quota_mb'];
        if ($qm > 0) {
            $qb = (string)mb_to_bytes($qm);
            db_execute("INSERT INTO radreply (username, attribute, op, value) VALUES (?, 'Mikrotik-Total-Limit', ':=', ?)", 'ss', [$u, $qb]);
        }
        
        $count++;
    }

    // Log the action
    log_activity($admin['id'], "Melakukan sinkronisasi RADIUS (Memulihkan {$count} voucher ke database radius)");

    db_commit();
    flash_set('success', "Sinkronisasi Berhasil! {$count} voucher telah dipulihkan ke mesin RADIUS.");
} catch (Exception $e) {
    db_rollback();
    flash_set('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
}

header('Location: /index.php?page=backup');
exit;
