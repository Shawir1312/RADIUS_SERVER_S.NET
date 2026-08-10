<?php
/**
 * Reports — Export CSV
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../include/functions.php';
auth_check();

$type   = get('type', 'sales');
$from   = get('from', date('Y-m-01'));
$to     = get('to', date('Y-m-d'));
$rid    = (int)get('router_id');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="snet_' . $type . '_' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$out = fopen('php://output', 'w');

if ($type === 'sales') {
    fputcsv($out, ['Waktu', 'Username Voucher', 'Profil', 'Router', 'Harga', 'Admin']);
    $where = ['DATE(sl.sold_at) BETWEEN ? AND ?'];
    $params = [$from, $to]; $types = 'ss';
    if ($rid) { $where[] = 'sl.router_id = ?'; $params[] = $rid; $types .= 'i'; }
    $rows = db_fetch_all(
        "SELECT sl.sold_at, sl.voucher_username, sl.profile_name, r.name AS rname, sl.price, a.username AS aname
         FROM sales_log sl LEFT JOIN routers r ON sl.router_id=r.id LEFT JOIN admins a ON sl.sold_by=a.id
         WHERE " . implode(' AND ', $where) . " ORDER BY sl.sold_at DESC",
        $types, $params
    );
    foreach ($rows as $r) {
        fputcsv($out, [date('d/m/Y H:i', strtotime($r['sold_at'])), $r['voucher_username'],
            $r['profile_name'], $r['rname'] ?? 'Semua', 'Rp '.number_format($r['price'],0,',','.'), $r['aname']]);
    }
} elseif ($type === 'vouchers') {
    fputcsv($out, ['Username', 'Password', 'Profil', 'Router', 'Batch', 'Status', 'Dibuat']);
    $rows = db_fetch_all(
        "SELECT v.username, v.password, p.name AS pname, r.name AS rname, v.batch_id, v.status, v.created_at
         FROM vouchers v LEFT JOIN profiles p ON v.profile_id=p.id LEFT JOIN routers r ON v.router_id=r.id
         WHERE v.status != 'deleted' ORDER BY v.created_at DESC LIMIT 5000"
    );
    foreach ($rows as $r) {
        fputcsv($out, [$r['username'], $r['password'], $r['pname'], $r['rname'] ?? 'Semua',
            $r['batch_id'], $r['status'], $r['created_at']]);
    }
} elseif ($type === 'radacct') {
    fputcsv($out, ['Session ID', 'Username', 'NAS IP', 'Start', 'Stop', 'Durasi(s)', 'DL(bytes)', 'UL(bytes)', 'IP']);
    $where = ['DATE(ra.acctstarttime) BETWEEN ? AND ?'];
    $params = [$from, $to]; $types = 'ss';
    if ($rid) {
        $r = db_fetch_one("SELECT ip_address FROM routers WHERE id=?", 'i', [$rid]);
        if ($r) { $where[] = 'ra.nasipaddress = ?'; $params[] = $r['ip_address']; $types .= 's'; }
    }
    $rows = db_fetch_all(
        "SELECT ra.acctsessionid, ra.username, ra.nasipaddress, ra.acctstarttime, ra.acctstoptime,
                ra.acctsessiontime, ra.acctoutputoctets, ra.acctinputoctets, ra.framedipaddress
         FROM radacct ra WHERE " . implode(' AND ', $where) . " ORDER BY ra.acctstarttime DESC LIMIT 5000",
        $types, $params
    );
    foreach ($rows as $r) {
        fputcsv($out, [$r['acctsessionid'], $r['username'], $r['nasipaddress'],
            $r['acctstarttime'], $r['acctstoptime'] ?? '', $r['acctsessiontime'],
            $r['acctoutputoctets'], $r['acctinputoctets'], $r['framedipaddress']]);
    }
}

fclose($out);
exit;
