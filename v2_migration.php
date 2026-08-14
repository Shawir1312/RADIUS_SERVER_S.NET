<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

echo "<h2>Menjalankan Migrasi Database Fase 3</h2>";

// Tambah kolom ont_sn
$check = db_fetch_one("SHOW COLUMNS FROM pppoe_customers LIKE 'ont_sn'");
if (!$check) {
    db_execute("ALTER TABLE pppoe_customers ADD COLUMN ont_sn VARCHAR(50) DEFAULT '' AFTER status");
    echo "Kolom ont_sn berhasil ditambahkan ke tabel pppoe_customers.<br>";
} else {
    echo "Kolom ont_sn sudah ada.<br>";
}

echo "<h3>Migrasi Selesai! Silakan hapus file ini demi keamanan.</h3>";
