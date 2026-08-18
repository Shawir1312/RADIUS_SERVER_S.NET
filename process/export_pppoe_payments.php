<?php
/**
 * Process — Export PPPoE Payments to CSV
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

$selRid    = (int)get('router_id', 0);
$selMonth  = (int)get('month', 0);
$selYear   = (int)get('year', 0);
$selMethod = get('method', '');
$selStatus = get('status', '');
$search    = trim(get('q', ''));

$where_clauses = ["1=1"];
$params = [];
$types = "";

if ($selRid > 0) {
    $where_clauses[] = "pc.router_id = ?";
    $params[] = $selRid;
    $types .= "i";
}

if ($selMonth > 0) {
    $where_clauses[] = "pp.period_month = ?";
    $params[] = $selMonth;
    $types .= "i";
}

if ($selYear > 0) {
    $where_clauses[] = "pp.period_year = ?";
    $params[] = $selYear;
    $types .= "i";
}

if ($selMethod !== '') {
    $where_clauses[] = "pp.payment_method = ?";
    $params[] = $selMethod;
    $types .= "s";
}

if ($selStatus !== '') {
    $where_clauses[] = "pp.midtrans_status = ?";
    $params[] = $selStatus;
    $types .= "s";
}

if ($search !== '') {
    $where_clauses[] = "(pc.full_name LIKE ? OR pc.pppoe_username LIKE ? OR pp.midtrans_order_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

$where_sql = implode(" AND ", $where_clauses);

$data_query = "SELECT pp.*, pc.full_name, pc.pppoe_username, pc.phone, r.name as router_name 
               FROM pppoe_payments pp 
               JOIN pppoe_customers pc ON pp.customer_id = pc.id 
               LEFT JOIN routers r ON pc.router_id = r.id 
               WHERE $where_sql 
               ORDER BY pp.paid_at DESC, pp.id DESC";

$rows = db_fetch_all($data_query, $types, $params);

$filename = 'pppoe_payments_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
// UTF-8 BOM for Excel compatibility
fputs($out, "\xEF\xBB\xBF");

// Headers
fputcsv($out, [
    'ID Pembayaran',
    'Tanggal Bayar',
    'No. Order / Ref',
    'Nama Pelanggan',
    'Username PPPoE',
    'No. Telepon',
    'Router / Cabang',
    'Periode Bulan',
    'Periode Tahun',
    'Nominal (Rp)',
    'Metode Pembayaran',
    'Status Transaksi',
    'Catatan'
]);

$months = [
    1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
    5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
    9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
];

foreach ($rows as $r) {
    $isPaid = ($r['midtrans_status'] === 'paid' || $r['midtrans_status'] === '' || $r['payment_method'] === 'cash');
    $statusText = $isPaid ? 'Lunas' : ($r['midtrans_status'] === 'pending' ? 'Pending' : ucfirst($r['midtrans_status']));

    fputcsv($out, [
        $r['id'],
        $r['paid_at'],
        $r['midtrans_order_id'] ?: '-',
        $r['full_name'],
        $r['pppoe_username'],
        $r['phone'] ?: '-',
        $r['router_name'] ?: '-',
        $months[$r['period_month']] ?? $r['period_month'],
        $r['period_year'],
        $r['amount'],
        strtoupper($r['payment_method']),
        $statusText,
        $r['notes'] ?: '-'
    ]);
}

fclose($out);
exit;
