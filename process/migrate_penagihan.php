<?php
/**
 * Migration Script for Penagihan Feature
 * Run this script once to update the database schema.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "Memulai migrasi database...<br>";

// 1. Add reseller_percent to profiles table if it doesn't exist
$check = db()->query("SHOW COLUMNS FROM profiles LIKE 'reseller_percent'");
if ($check->num_rows === 0) {
    if (db()->query("ALTER TABLE profiles ADD COLUMN reseller_percent DECIMAL(5,2) DEFAULT 0.00")) {
        echo "Berhasil: Kolom reseller_percent ditambahkan ke tabel profiles.<br>";
    } else {
        echo "Gagal: " . db()->error . "<br>";
    }
} else {
    echo "Info: Kolom reseller_percent sudah ada di tabel profiles.<br>";
}

// 2. Create penagihan table
$sql = "CREATE TABLE IF NOT EXISTS penagihan (
    id int(11) NOT NULL AUTO_INCREMENT,
    router_id int(11) NOT NULL,
    profile_id int(11) NOT NULL,
    total_pendapatan decimal(15,2) DEFAULT 0.00,
    bagian_reseller decimal(15,2) DEFAULT 0.00,
    pendapatan_bersih decimal(15,2) DEFAULT 0.00,
    estimasi_voucher int(11) DEFAULT 0,
    voucher_aktual int(11) DEFAULT 0,
    status_kecocokan enum('sesuai','tekor','lebih') DEFAULT 'sesuai',
    catatan text,
    ditagih_oleh int(11) NOT NULL,
    tanggal date NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY router_id (router_id),
    KEY profile_id (profile_id),
    KEY tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (db()->query($sql)) {
    echo "Berhasil: Tabel penagihan berhasil dibuat atau sudah ada.<br>";
} else {
    echo "Gagal: " . db()->error . "<br>";
}

echo "<br>Migrasi selesai! Silakan hapus file ini jika sudah tidak diperlukan.";
