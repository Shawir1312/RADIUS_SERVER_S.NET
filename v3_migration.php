<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

echo "<h2>Menjalankan Migrasi Database Fase 4</h2>";

$check = db_fetch_one("SHOW COLUMNS FROM pppoe_customers LIKE 'portal_password'");
if (!$check) {
    db_execute("ALTER TABLE pppoe_customers ADD COLUMN portal_password VARCHAR(255) DEFAULT '' AFTER pppoe_username");
    echo "Kolom portal_password berhasil ditambahkan ke tabel pppoe_customers.<br>";
} else {
    echo "Kolom portal_password sudah ada.<br>";
}

echo "<h3>Migrasi Selesai! Silakan hapus file ini demi keamanan.</h3>";
