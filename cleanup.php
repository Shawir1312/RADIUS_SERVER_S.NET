<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    db_execute("DELETE FROM vouchers WHERE status = 'deleted'");
    echo "<h1>Berhasil!</h1><p>Semua voucher yang berstatus 'deleted' (110 voucher) telah dibersihkan secara permanen dari database.</p><p>Silakan hapus file ini demi keamanan.</p>";
} catch (Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
