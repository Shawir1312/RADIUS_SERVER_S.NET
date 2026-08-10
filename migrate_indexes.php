<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    echo "Menambahkan Index ke tabel vouchers...\n";
    db_execute("ALTER TABLE vouchers 
                ADD INDEX idx_status (status),
                ADD INDEX idx_profile (profile_id),
                ADD INDEX idx_batch (batch_id),
                ADD INDEX idx_router (router_id)");
    echo "Index berhasil ditambahkan! Database sekarang super cepat.\n";
} catch (Exception \) {
    if (strpos(\->getMessage(), 'Duplicate key name') !== false) {
        echo "Index sudah ada (Aman).\n";
    } else {
        echo "Error: " . \->getMessage() . "\n";
    }
}
