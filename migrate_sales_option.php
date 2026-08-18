<?php
/**
 * Migration Script — Add include_in_sales column to profiles table
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

echo "<h2>Migrasi Database: Opsi Pencatatan Penjualan Profil Hotspot</h2>";

try {
    $check = db_fetch_one("SHOW COLUMNS FROM profiles LIKE 'include_in_sales'");
    if (!$check) {
        db_execute("ALTER TABLE profiles ADD COLUMN include_in_sales TINYINT(1) DEFAULT 1 AFTER price");
        echo "<p style='color:green;'>[SUKSES] Kolom <code>include_in_sales</code> berhasil ditambahkan ke tabel <code>profiles</code>.</p>";
    } else {
        echo "<p style='color:blue;'>[INFO] Kolom <code>include_in_sales</code> sudah ada pada tabel <code>profiles</code>.</p>";
    }
    echo "<h3>Migrasi selesai dengan sukses.</h3>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>[GAGAL] " . htmlspecialchars($e->getMessage()) . "</p>";
}
