#!/usr/bin/env php
<?php
/**
 * S.NET RADIUS Manager — Cron: Auto-Expire Vouchers
 * Run via crontab: * /5 * * * * php /var/www/html/radius-hotspot/cron/expire_vouchers.php
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

// ── Clear bogus expired_at for unused vouchers (from old generate_voucher bug) ──
db_execute("UPDATE vouchers SET expired_at = NULL WHERE status = 'unused' AND expired_at IS NOT NULL");

// ── Catch up missing expired_at for already active vouchers ─────────────────
$missing_exp = db_fetch_all("
    SELECT v.id, v.used_at, p.duration_value, p.duration_unit
    FROM vouchers v JOIN profiles p ON v.profile_id = p.id
    WHERE v.status = 'active' AND v.expired_at IS NULL AND v.used_at IS NOT NULL
");
foreach ($missing_exp as $m) {
    $ds = duration_to_seconds($m['duration_value'], $m['duration_unit']);
    $exp = date('Y-m-d H:i:s', strtotime($m['used_at']) + $ds);
    db_execute("UPDATE vouchers SET expired_at = ? WHERE id = ?", 'si', [$exp, $m['id']]);
}

// ── Sync Active Vouchers ─────────────────────────────────────────────────────
// Vouchers that have been used (have radacct entry) but status is still 'unused'
sync_active_vouchers();
$log("Synced active vouchers");

// ── Find expired vouchers ─────────────────────────────────────────────
// Vouchers that are 'active' but their time has passed expired_at
$past_expired = db_fetch_all(
    "SELECT id, username FROM vouchers WHERE status = 'active' AND expired_at < NOW() AND expired_at IS NOT NULL"
);

$log("Found " . count($past_expired) . " vouchers past expiry date");

foreach ($past_expired as $v) {
    $username = $v['username'];
    $log("Expiring: {$username}");

    db_begin();
    try {
        db_execute("UPDATE vouchers SET status = 'expired' WHERE id = ?", 'i', [(int)$v['id']]);

        // Remove from radcheck and radreply (voucher spent)
        db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$username]);
        db_execute("DELETE FROM radreply WHERE username = ?", 's', [$username]);

        // Fix stale sessions: Mark any active radacct sessions for this user as stopped
        db_execute(
            "UPDATE radacct SET acctstoptime = NOW(), acctterminatecause = 'Session-Timeout'
             WHERE username = ? AND acctstoptime IS NULL",
            's', [$username]
        );

        // Audit entry
        db_execute(
            "INSERT INTO audit_log (admin_id, admin_name, action, target, ip_address, detail, created_at)
             VALUES (0, 'CRON', 'auto_expire', ?, 'cron', 'Expired by date', NOW())",
            's', [$username]
        );

        db_commit();
        $log("  → Voucher expired and removed from RADIUS tables");

        // Kick from Mikrotik to prevent lingering sessions
        $acct = db_fetch_one("SELECT nasipaddress FROM radacct WHERE username = ? ORDER BY acctstarttime DESC LIMIT 1", 's', [$username]);
        if ($acct) {
            $router = db_fetch_one("SELECT ip_address, api_user, api_password, api_port FROM routers WHERE ip_address = ? OR nas_ip = ?", 'ss', [$acct['nasipaddress'], $acct['nasipaddress']]);
            if ($router) {
                try {
                    require_once LIB_PATH . '/routeros_api.class.php';
                    $api = new RouterosAPI();
                    $api->debug = false;
                    if ($api->connect($router['ip_address'], $router['api_user'], $router['api_password'], (int)$router['api_port'])) {
                        $active_users = $api->comm("/ip/hotspot/active/print", ["?user" => $username]);
                        foreach ($active_users as $au) {
                            $api->comm("/ip/hotspot/active/remove", [".id" => $au['.id']]);
                            $log("  → Kicked {$username} from Mikrotik ({$router['ip_address']})");
                        }
                        $api->disconnect();
                    }
                } catch (Throwable $e) {
                    $log("  → API Error: " . $e->getMessage());
                }
            }
        }
    } catch (Throwable $e) {
        db_rollback();
        $log("  ERROR: " . $e->getMessage());
    }
}

$log("=== expire_vouchers cron finished ===\n");
