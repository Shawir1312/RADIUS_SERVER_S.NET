<?php
/**
 * Export Laporan Penagihan to Excel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

$filter_start = get('start_date', date('Y-m-01'));
$filter_end   = get('end_date', date('Y-m-d'));
$router_filter = (int)get('router_id');

$sql_history = "SELECT p.*, r.name as router_name, pr.name as profile_name, a.full_name as admin_name 
                FROM penagihan p 
                JOIN routers r ON p.router_id = r.id 
                JOIN profiles pr ON p.profile_id = pr.id 
                LEFT JOIN admins a ON p.ditagih_oleh = a.id 
                WHERE p.tanggal BETWEEN ? AND ?";
$types_history = 'ss';
$params_history = [$filter_start, $filter_end];

if ($router_filter > 0) {
    $sql_history .= " AND p.router_id = ?";
    $types_history .= 'i';
    $params_history[] = $router_filter;
}
$sql_history .= " ORDER BY p.created_at DESC";

$history = db_fetch_all($sql_history, $types_history, $params_history);

// Generate Excel (HTML table approach)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Penagihan_{$filter_start}_sd_{$filter_end}.xls");
header("Pragma: no-cache");
header("Expires: 0");

$total_kotor = 0;
$total_bersih = 0;
$total_bagian = 0;

echo '<table border="1">';
echo '<tr>';
echo '<th colspan="9" style="font-size:16px; font-weight:bold; text-align:center;">LAPORAN PENAGIHAN RESELLER</th>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="9" style="text-align:center;">Periode: ' . $filter_start . ' s/d ' . $filter_end . '</th>';
echo '</tr>';
echo '<tr>';
echo '<th>Tanggal</th>';
echo '<th>Waktu</th>';
echo '<th>Cabang Router</th>';
echo '<th>Reseller (Profil)</th>';
echo '<th>Total Pendapatan (Kotor)</th>';
echo '<th>Bagian Reseller</th>';
echo '<th>Pendapatan Bersih</th>';
echo '<th>Estimasi vs Aktual Voucher</th>';
echo '<th>Status Kecocokan</th>';
echo '<th>Catatan</th>';
echo '<th>Ditagih Oleh</th>';
echo '</tr>';

foreach ($history as $h) {
    $total_kotor += (float)$h['total_pendapatan'];
    $total_bagian += (float)$h['bagian_reseller'];
    $total_bersih += (float)$h['pendapatan_bersih'];
    
    echo '<tr>';
    echo '<td>' . date('d/m/Y', strtotime($h['tanggal'])) . '</td>';
    echo '<td>' . date('H:i:s', strtotime($h['created_at'])) . '</td>';
    echo '<td>' . htmlspecialchars($h['router_name']) . '</td>';
    echo '<td>' . htmlspecialchars($h['profile_name']) . '</td>';
    echo '<td>' . $h['total_pendapatan'] . '</td>';
    echo '<td>' . $h['bagian_reseller'] . '</td>';
    echo '<td>' . $h['pendapatan_bersih'] . '</td>';
    echo '<td>' . $h['estimasi_voucher'] . ' vs ' . $h['voucher_aktual'] . '</td>';
    echo '<td>' . strtoupper($h['status_kecocokan']) . '</td>';
    echo '<td>' . htmlspecialchars($h['catatan'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($h['admin_name']) . '</td>';
    echo '</tr>';
}

echo '<tr>';
echo '<th colspan="4" style="text-align:right;">TOTAL:</th>';
echo '<th>' . $total_kotor . '</th>';
echo '<th>' . $total_bagian . '</th>';
echo '<th>' . $total_bersih . '</th>';
echo '<th colspan="4"></th>';
echo '</tr>';
echo '</table>';
