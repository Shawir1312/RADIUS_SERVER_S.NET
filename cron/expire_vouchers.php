#!/usr/bin/env php
<?php
/**
 * S.NET RADIUS Manager — Cron: Auto-Expire Vouchers
 * Run via crontab: */5 * * * * php /var/www/html/radius-hotspot/cron/expire_vouchers.php
 *
 * Checks radacct for sessions that have exceeded their Session-Timeout,
 * marks the corresponding vouchers as expired, and removes from radcheck/radreply.
 */

define('CLI_MODE', true);
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('LIB_PATH', BASE_PATH . '/lib');

// Load local DB config if exists
if (file_exists(CONFIG_PATH . '/db_local.php')) {
    require_once CONFIG_PATH . '/db_local.php';
} else {
    require_once CONFIG_PATH . '/database.php';
}
// Remaining constants
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', 'Asia/Makassar');
date_default_timezone_set(APP_TIMEZONE);

require_once CONFIG_PATH . '/database.php';
require_once BASE_PATH . '/include/functions.php';

$log = function(string $msg) {
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] {$msg}";
    echo $line . "\n";
    // Append to log file
    $logdir = BASE_PATH . '/logs';
    if (!is_dir($logdir)) mkdir($logdir, 0755, true);
    file_put_contents($logdir . '/cron.log', $line . "\n", FILE_APPEND);
};

$log("=== expire_vouchers cron started ===");

// ── Sync Active Vouchers ─────────────────────────────────────────────────────
// Vouchers that have been used (have radacct entry) but status is still 'unused'
sync_active_vouchers();
$log("Synced active vouchers");

// ── Find expired active sessions ─────────────────────────────────────────────
// Sessions where acctstoptime IS NULL and they started longer ago than Session-Timeout
// We use the Session-Timeout from radreply table.
$expired_sessions = db_fetch_all(
    "SELECT ra.username, ra.radacctid, ra.acctstarttime, ra.nasipaddress,
            COALESCE(rr.value, 86400) AS session_timeout
     FROM radacct ra
     LEFT JOIN radreply rr ON rr.username = ra.username AND rr.attribute = 'Session-Timeout'
     WHERE ra.acctstoptime IS NULL
       AND ra.acctstarttime < DATE_SUB(NOW(), INTERVAL COALESCE(rr.value, 86400) SECOND)"
);

$log("Found " . count($expired_sessions) . " expired sessions");

foreach ($expired_sessions as $session) {
    $username = $session['username'];
    $log("Expiring: {$username} (started: {$session['acctstarttime']}, timeout: {$session['session_timeout']}s)");

    db_begin();
    try {
        // Mark radacct session as stopped
        db_execute(
            "UPDATE radacct SET acctstoptime = NOW(), acctterminatecause = 'Session-Timeout'
             WHERE radacctid = ?",
            'i', [(int)$session['radacctid']]
        );

        // Check if voucher exists and is still active
        $voucher = db_fetch_one("SELECT id, status FROM vouchers WHERE username = ? LIMIT 1", 's', [$username]);
        if ($voucher && $voucher['status'] === 'active') {
            // Mark as expired
            db_execute("UPDATE vouchers SET status = 'expired' WHERE id = ?", 'i', [(int)$voucher['id']]);

            // Remove from radcheck and radreply (voucher spent)
            db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$username]);
            db_execute("DELETE FROM radreply WHERE username = ?", 's', [$username]);

            $log("  → Voucher expired and removed from RADIUS tables");
        }

        // Audit entry
        db_execute(
            "INSERT INTO audit_log (admin_id, admin_name, action, target, ip_address, detail, created_at)
             VALUES (0, 'CRON', 'auto_expire', ?, 'cron', ?, NOW())",
            'ss', [$username, json_encode(['session_id' => $session['radacctid']])]
        );

        db_commit();
    } catch (Throwable $e) {
        db_rollback();
        $log("  ERROR: " . $e->getMessage());
    }
}

// ── Also mark vouchers past their expired_at date as expired ─────────────────
$past_expired = db_fetch_all(
    "SELECT id, username FROM vouchers WHERE status = 'unused' AND expired_at < NOW() AND expired_at IS NOT NULL"
);

$log("Found " . count($past_expired) . " vouchers past expiry date (unused)");

foreach ($past_expired as $v) {
    db_begin();
    try {
        db_execute("UPDATE vouchers SET status = 'expired' WHERE id = ?", 'i', [(int)$v['id']]);
        db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$v['username']]);
        db_execute("DELETE FROM radreply WHERE username = ?", 's', [$v['username']]);
        db_commit();
        $log("  Expired (date): {$v['username']}");
    } catch (Throwable $e) {
        db_rollback();
        $log("  ERROR: " . $e->getMessage());
    }
}

$log("=== expire_vouchers cron finished ===\n");
