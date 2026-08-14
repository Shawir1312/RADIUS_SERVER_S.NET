<?php
require_once __DIR__ . '/config/database.php';

// Cek apakah admin sudah login (Keamanan agar tidak sembarang orang bisa mengeksekusi)
require_once __DIR__ . '/config/auth.php';
auth_start();
if (empty($_SESSION['admin_id'])) {
    die("Akses Ditolak: Anda harus login sebagai admin terlebih dahulu.");
}

$sql_file = __DIR__ . '/v2_migration.sql';
if (!file_exists($sql_file)) {
    die("File v2_migration.sql tidak ditemukan!");
}

$sql = file_get_contents($sql_file);
$conn = db();
$conn->multi_query($sql);
$success = true;

do {
    if ($res = $conn->store_result()) {
        $res->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->error) {
    echo "<h2>Error Migrasi:</h2><p style='color:red;'>" . $conn->error . "</p>";
} else {
    echo "<h2>Sukses!</h2><p style='color:green;'>Semua tabel V2 berhasil ditambahkan ke database.</p>";
    echo "<p>Silakan hapus file run_migration.php dan v2_migration.sql ini demi keamanan.</p>";
}
