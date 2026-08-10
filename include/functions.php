<?php
/**
 * S.NET RADIUS Hotspot Management
 * Global Helper Functions
 */

// ───── String / Random ─────────────────────────────────────────────────

function random_string(int $length, string $type = 'mix'): string {
    $lower  = 'abcdefghjkmnpqrstuvwxyz';   // no i,l,o
    $upper  = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    $digits = '23456789';                   // no 0,1
    $map = [
        'lower'  => $lower,
        'upper'  => $upper,
        'num'    => $digits,
        'upplow' => $lower . $upper,
        'mix'    => $lower . $digits,
        'mix1'   => $upper . $digits,
        'mix2'   => $lower . $upper . $digits,
    ];
    $chars = $map[$type] ?? ($lower . $upper . $digits);
    $result = '';
    $max    = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, $max)];
    }
    return $result;
}

function generate_batch_id(): string {
    return 'B' . date('ymd') . strtoupper(random_string(4, 'mix1'));
}

// ───── Bytes Formatting ────────────────────────────────────────────────

function format_bytes(int|float $bytes, int $precision = 2): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow   = min((int) floor(log($bytes, 1024)), count($units) - 1);
    $val   = $bytes / (1024 ** $pow);
    return round($val, $precision) . ' ' . $units[$pow];
}

function bytes_to_mb(int $bytes): float {
    return round($bytes / 1048576, 2);
}

function mb_to_bytes(float $mb): int {
    return (int) ($mb * 1048576);
}

// ───── Duration / Time ─────────────────────────────────────────────────

/**
 * Convert profile duration to seconds (for Session-Timeout RADIUS attr).
 */
function duration_to_seconds(int $value, string $unit): int {
    $map = [
        'minutes' => $value * 60,
        'hours'   => $value * 3600,
        'days'    => $value * 86400,
    ];
    return $map[$unit] ?? ($value * 3600);
}

function seconds_to_human(int $secs): string {
    if ($secs <= 0) return 'Unlimited';
    $d = intdiv($secs, 86400);
    $h = intdiv($secs % 86400, 3600);
    $m = intdiv($secs % 3600, 60);
    $parts = [];
    if ($d) $parts[] = "{$d}d";
    if ($h) $parts[] = "{$h}h";
    if ($m) $parts[] = "{$m}m";
    return implode(' ', $parts) ?: '< 1m';
}

function session_duration_human(?string $start, ?string $stop = null): string {
    if (!$start) return '-';
    $from = strtotime($start);
    $to   = $stop ? strtotime($stop) : time();
    return seconds_to_human($to - $from);
}

// ───── RADIUS Rate-Limit Format ────────────────────────────────────────

/**
 * Build Mikrotik-Rate-Limit value: "upload/download"
 * e.g. rate_limit_attr("10M", "20M") → "10M/20M"
 */
function rate_limit_attr(string $up, string $down): string {
    $up   = $up   ?: '0';
    $down = $down ?: '0';
    return "{$up}/{$down}";
}

// ───── Pagination ──────────────────────────────────────────────────────

function paginate(int $total, int $per_page, int $current_page, string $url_base): array {
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current_page,
        'total_pages' => $total_pages,
        'offset'      => $offset,
        'url_base'    => $url_base,
    ];
}

function pagination_html(array $p): string {
    if ($p['total_pages'] <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm mb-0">';
    $prev = $p['current'] - 1;
    $next = $p['current'] + 1;
    $disabled = $p['current'] <= 1 ? 'disabled' : '';
    $html .= "<li class='page-item {$disabled}'><a class='page-link' href='{$p['url_base']}&page={$prev}'>«</a></li>";
    $range = range(max(1, $p['current']-2), min($p['total_pages'], $p['current']+2));
    foreach ($range as $pg) {
        $active = $pg === $p['current'] ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$p['url_base']}&page={$pg}'>{$pg}</a></li>";
    }
    $disabled2 = $p['current'] >= $p['total_pages'] ? 'disabled' : '';
    $html .= "<li class='page-item {$disabled2}'><a class='page-link' href='{$p['url_base']}&page={$next}'>»</a></li>";
    $html .= '</ul></nav>';
    return $html;
}

// ───── Sanitization ────────────────────────────────────────────────────

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function sanitize_username(string $str): string {
    // Voucher usernames: alphanumeric + dash/underscore, max 64 chars
    return substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', trim($str)), 0, 64);
}

function post(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = ''): mixed {
    return $_GET[$key] ?? $default;
}

// ───── Flash Messages ──────────────────────────────────────────────────

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function flash_html(): string {
    $f = flash_get();
    if (!$f) return '';
    $map = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
    ];
    $type = $map[$f['type']] ?? 'info';
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>"
         . htmlspecialchars($f['msg'], ENT_QUOTES)
         . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

// ───── Router / NAS Helpers ────────────────────────────────────────────

function get_all_routers(): array {
    $access = accessible_router_ids();
    if ($access === null) {
        return db_fetch_all("SELECT * FROM routers ORDER BY name ASC");
    }
    if (empty($access)) return [];
    $placeholders = implode(',', array_fill(0, count($access), '?'));
    $types = str_repeat('i', count($access));
    return db_fetch_all("SELECT * FROM routers WHERE id IN ({$placeholders}) ORDER BY name ASC", $types, $access);
}

function get_router(int $id): ?array {
    return db_fetch_one("SELECT * FROM routers WHERE id = ? LIMIT 1", 'i', [$id]);
}

// ───── Price Formatting ────────────────────────────────────────────────

function format_price(float $price): string {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// ───── Status Badge ────────────────────────────────────────────────────

function voucher_status_badge(string $status): string {
    $map = [
        'unused'  => "<span class='badge bg-secondary'>Belum Dipakai</span>",
        'active'  => "<span class='badge bg-success'>Aktif</span>",
        'expired' => "<span class='badge bg-danger'>Kadaluarsa</span>",
        'deleted' => "<span class='badge bg-dark'>Dihapus</span>",
    ];
    return $map[$status] ?? "<span class='badge bg-light text-dark'>{$status}</span>";
}

function router_status_badge(bool $online): string {
    return $online
        ? "<span class='badge bg-success'><i class='bi bi-circle-fill me-1'></i>Online</span>"
        : "<span class='badge bg-danger'><i class='bi bi-circle-fill me-1'></i>Offline</span>";
}

function sync_active_vouchers() {
    $newly_active = db_fetch_all("
        SELECT v.id, v.username, v.profile_id, p.name AS profile_name, p.price, v.router_id, ra.acctstarttime, p.duration_value, p.duration_unit
        FROM vouchers v
        JOIN radacct ra ON v.username = ra.username
        JOIN profiles p ON v.profile_id = p.id
        WHERE v.status = 'unused'
    ");

    foreach ($newly_active as $v) {
        db_begin();
        try {
            $duration_s = duration_to_seconds($v['duration_value'], $v['duration_unit']);
            $used_at = $v['acctstarttime'];
            $expired_at = date('Y-m-d H:i:s', strtotime($used_at) + $duration_s);

            db_execute(
                "UPDATE vouchers SET status = 'active', used_at = ?, expired_at = ? WHERE id = ?",
                'ssi', [$used_at, $expired_at, $v['id']]
            );

            // Record sale
            db_execute(
                "INSERT INTO sales_log (voucher_id, voucher_username, profile_id, profile_name, router_id, price, sold_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                'isissds', 
                [$v['id'], $v['username'], $v['profile_id'], $v['profile_name'], $v['router_id'], $v['price'], $used_at]
            );

            db_commit();
        } catch(Throwable $e) {
            db_rollback();
        }
    }
}

/**
 * Global cleanup routine to expire vouchers and clear stale sessions.
 */
function run_auto_expire_vouchers($log = null) {
    if (!$log) $log = function($msg) {};

    // ── Clear bogus expired_at for unused vouchers ──
    db_execute("UPDATE vouchers SET expired_at = NULL WHERE status = 'unused' AND expired_at IS NOT NULL");

    // ── Fix Stale Sessions globally ──
    db_execute("
        UPDATE radacct ra
        JOIN vouchers v ON ra.username = v.username
        SET ra.acctstoptime = NOW(), ra.acctterminatecause = 'Admin-Reset'
        WHERE v.status IN ('expired', 'deleted') AND ra.acctstoptime IS NULL
    ");

    // ── Catch up missing expired_at ──
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

    sync_active_vouchers();

    // ── Find expired vouchers ──
    $past_expired = db_fetch_all(
        "SELECT id, username FROM vouchers WHERE status = 'active' AND expired_at < NOW() AND expired_at IS NOT NULL"
    );

    if (count($past_expired) > 0) {
        require_once __DIR__ . '/../lib/routeros_api.class.php';
    }

    foreach ($past_expired as $v) {
        $username = $v['username'];
        $log("Expiring: {$username}");
        db_begin();
        try {
            db_execute("UPDATE vouchers SET status = 'expired' WHERE id = ?", 'i', [(int)$v['id']]);
            db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$username]);
            db_execute("DELETE FROM radreply WHERE username = ?", 's', [$username]);
            db_execute("
                UPDATE radacct SET acctstoptime = NOW(), acctterminatecause = 'Session-Timeout'
                WHERE username = ? AND acctstoptime IS NULL", 's', [$username]
            );
            db_execute(
                "INSERT INTO audit_log (admin_id, admin_name, action, target, ip_address, detail, created_at)
                 VALUES (0, 'SYSTEM', 'auto_expire', ?, 'system', 'Expired by date', NOW())", 's', [$username]
            );
            db_commit();
            $log("  → Voucher expired and removed from RADIUS tables");

            // Kick from Mikrotik
            $acct = db_fetch_one("SELECT nasipaddress FROM radacct WHERE username = ? ORDER BY acctstarttime DESC LIMIT 1", 's', [$username]);
            if ($acct) {
                $router = db_fetch_one("SELECT ip_address, api_user, api_password, api_port FROM routers WHERE ip_address = ? OR nas_ip = ?", 'ss', [$acct['nasipaddress'], $acct['nasipaddress']]);
                if ($router) {
                    try {
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
                    } catch (Throwable $e) {}
                }
            }
        } catch (Throwable $e) {
            db_rollback();
        }
    }
}
