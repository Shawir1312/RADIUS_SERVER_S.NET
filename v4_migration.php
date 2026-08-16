<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

echo "<h2>Menjalankan Migrasi Database Fase 4 (Part 2)</h2>";

$checkPassword = db_fetch_one("SHOW COLUMNS FROM pppoe_customers LIKE 'portal_password'");
if (!$checkPassword) {
    db_execute("ALTER TABLE pppoe_customers ADD COLUMN portal_password VARCHAR(255) DEFAULT '' AFTER pppoe_username");
    echo "Kolom portal_password berhasil ditambahkan.<br>";
}

$checkUsername = db_fetch_one("SHOW COLUMNS FROM pppoe_customers LIKE 'portal_username'");
if (!$checkUsername) {
    db_execute("ALTER TABLE pppoe_customers ADD COLUMN portal_username VARCHAR(100) DEFAULT '' AFTER portal_password");
    echo "Kolom portal_username berhasil ditambahkan.<br>";
}

echo "<h3>Migrasi Selesai! Silakan hapus file ini demi keamanan.</h3>";
