<?php
/**
 * AJAX — Active Users (JSON)
 * Returns list of active sessions from radacct, enriched with router info.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();
session_write_close();
header('Content-Type: application/json');

// Count-only mode for badge
$count_only = !empty($_GET['count']);

$filter_router = (int)($_GET['router_id'] ?? 0);
$access = accessible_router_ids();

// Build WHERE
$where  = ["ra.acctstoptime IS NULL"];
$params = [];
$types  = '';

if ($filter_router) {
    // Get router IP for this router_id
    $router = db_fetch_one("SELECT ip_address FROM routers WHERE id = ?", 'i', [$filter_router]);
    if ($router) {
        $where[]  = "ra.nasipaddress = ?";
        $params[] = $router['ip_address'];
        $types   .= 's';
    }
} elseif ($access !== null && !empty($access)) {
    $ips = db_fetch_all(
        "SELECT ip_address FROM routers WHERE id IN (" . implode(',', array_fill(0, count($access), '?')) . ")",
        str_repeat('i', count($access)), $access
    );
    if (!empty($ips)) {
        $ip_list = array_column($ips, 'ip_address');
        $pls = implode(',', array_fill(0, count($ip_list), '?'));
        $where[] = "ra.nasipaddress IN ({$pls})";
        foreach ($ip_list as $ip) { $params[] = $ip; $types .= 's'; }
    }
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

if ($count_only) {
    $cnt = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM radacct ra {$where_sql}", $types, $params)['n'] ?? 0);
    echo json_encode(['count' => $cnt]);
    exit;
}

$rows = db_fetch_all(
    "SELECT ra.radacctid, ra.username, ra.nasipaddress, ra.callingstationid, ra.framedipaddress,
            ra.acctstarttime, ra.acctinputoctets, ra.acctoutputoctets,
            r.name AS router_name, v.profile_id, v.expired_at,
            p.name AS profile, p.duration_value, p.duration_unit,
            rr.value AS session_timeout
     FROM radacct ra
     LEFT JOIN routers r ON r.ip_address = ra.nasipaddress
     LEFT JOIN vouchers v ON v.username = ra.username
     LEFT JOIN profiles p ON p.id = v.profile_id
     LEFT JOIN radreply rr ON rr.username = ra.username AND rr.attribute = 'Session-Timeout'
     {$where_sql}
     ORDER BY ra.acctstarttime DESC
     LIMIT 200",
    $types, $params
);

$users = array_map(function($row) {
    $validity_text = '';
    $duration_text = '';

    if (!empty($row['expired_at'])) {
        $val = strtotime($row['expired_at']) - time();
        $validity_text = $val <= 0 ? 'Habis' : seconds_to_human($val);
    }
    
    if (!empty($row['duration_value']) && $row['duration_value'] > 0) {
        $limit = duration_to_seconds((int)$row['duration_value'], $row['duration_unit']);
        $used_closed = (int)(db_fetch_one("SELECT SUM(acctsessiontime) as used FROM radacct WHERE username = ? AND acctstoptime IS NOT NULL", 's', [$row['username']])['used'] ?? 0);
        $used_active = 0;
        $active_sessions = db_fetch_all("SELECT acctstarttime FROM radacct WHERE username = ? AND acctstoptime IS NULL", 's', [$row['username']]);
        foreach ($active_sessions as $sess) {
            $used_active += max(0, time() - strtotime($sess['acctstarttime']));
        }
        $dur_rem = $limit - $used_closed - $used_active;
        $duration_text = $dur_rem <= 0 ? 'Habis' : seconds_to_human($dur_rem);
    }
    
    $sisa_waktu = '';
    if ($duration_text) {
        $sisa_waktu .= '<div style="font-size:0.7rem;margin-bottom:2px;" title="Sisa Kuota Waktu (Durasi)"><i class="bi bi-hourglass-split"></i> ' . $duration_text . '</div>';
    }
    if ($validity_text) {
        $sisa_waktu .= '<div style="font-size:0.7rem;color:var(--red);" title="Sisa Masa Aktif (Validity)"><i class="bi bi-calendar-x"></i> ' . $validity_text . '</div>';
    }
    if ($sisa_waktu === '') {
        $sisa_waktu = '<span style="font-size:0.75rem;">Unlimited</span>';
    }

    return [
        'radacctid'        => (string)$row['radacctid'],
        'username'         => $row['username'],
        'nasipaddress'     => $row['nasipaddress'],
        'router_name'      => $row['router_name'] ?? $row['nasipaddress'],
        'callingstationid' => $row['callingstationid'],
        'framedipaddress'  => $row['framedipaddress'],
        'duration'         => session_duration_human($row['acctstarttime']),
        'sisa_waktu'       => $sisa_waktu,
        'dl'               => format_bytes((int)$row['acctoutputoctets']),
        'ul'               => format_bytes((int)$row['acctinputoctets']),
        'profile'          => $row['profile'],
    ];
}, $rows);

echo json_encode(['count' => count($users), 'users' => $users]);
