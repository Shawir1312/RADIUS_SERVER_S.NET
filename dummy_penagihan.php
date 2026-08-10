<?php
/**
 * Script untuk membuat 2000 data dummy penagihan
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/include/functions.php';

// Cek apakah sudah login sebagai superadmin (opsional, tapi untuk keamanan)
session_start();
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_role'] !== 'superadmin') {
    die("Akses ditolak. Silakan login sebagai superadmin terlebih dahulu.");
}

$admin_id = $_SESSION['admin_id'];

// Ambil profil reseller
$profiles = db_fetch_all("SELECT id, router_id, price, reseller_percent FROM profiles WHERE reseller_percent > 0 AND is_active = 1");

if (empty($profiles)) {
    die("Tidak ada data reseller. Buat paket/profil reseller terlebih dahulu.");
}

// Ambil semua router jika profil tidak terikat router
$routers = db_fetch_all("SELECT id FROM routers");
if (empty($routers)) {
    die("Tidak ada data router.");
}

echo "Memulai pembuatan 2000 data dummy penagihan...<br>";
flush();

$inserted = 0;

for ($i = 0; $i < 2000; $i++) {
    // Random profile
    $p = $profiles[array_rand($profiles)];
    $profile_id = $p['id'];
    
    // Tentukan router
    $router_id = $p['router_id'] ?: $routers[array_rand($routers)]['id'];
    
    // Random tanggal (6 bulan terakhir)
    $timestamp = time() - rand(0, 180 * 24 * 60 * 60);
    $tanggal = date('Y-m-d', $timestamp);
    $created_at = date('Y-m-d H:i:s', $timestamp);
    
    // Random voucher aktual (yang harusnya ditagih)
    $voucher_aktual = rand(10, 100);
    
    // Tentukan status (70% Sesuai, 15% Tekor, 15% Lebih)
    $rand_status = rand(1, 100);
    
    if ($rand_status <= 70) {
        $estimasi_voucher = $voucher_aktual;
        $status = 'sesuai';
        $catatan = '';
    } elseif ($rand_status <= 85) {
        // Tekor (estimasi/yang dibayar lebih kecil dari aktual/yang terjual)
        $estimasi_voucher = $voucher_aktual - rand(1, 10);
        $status = 'tekor';
        $catatan = 'Di pakai oleh reseller ' . rand(1, 5) . ' voucher, sisa hilang';
    } else {
        // Lebih (estimasi/yang dibayar lebih besar dari aktual/yang terjual)
        $estimasi_voucher = $voucher_aktual + rand(1, 5);
        $status = 'lebih';
        $catatan = 'Kelebihan setor';
    }
    
    // Hitung uang
    $price = (float)$p['price'];
    $percent = (float)$p['reseller_percent'];
    
    $total_pendapatan = $estimasi_voucher * $price;
    $bagian_reseller = $total_pendapatan * ($percent / 100);
    $pendapatan_bersih = $total_pendapatan - $bagian_reseller;
    
    // Insert
    db_execute(
        "INSERT INTO penagihan (router_id, profile_id, total_pendapatan, bagian_reseller, pendapatan_bersih, estimasi_voucher, voucher_aktual, status_kecocokan, catatan, ditagih_oleh, tanggal, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        'iidddiississ',
        [
            $router_id, $profile_id, $total_pendapatan, $bagian_reseller, $pendapatan_bersih, 
            $estimasi_voucher, $voucher_aktual, $status, $catatan, $admin_id, $tanggal, $created_at
        ]
    );
    
    $inserted++;
}

echo "Sukses! Berhasil menambahkan $inserted data dummy penagihan.<br>";
echo "<a href='/index.php?page=penagihan_report'>Kembali ke Laporan Penagihan</a>";
