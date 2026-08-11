<?php
/**
 * Process - Delete Sale Log
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

$id = (int)get('id');
$admin = current_admin();

if ($id <= 0) {
    flash_set('error', 'ID tidak valid.');
    header('Location: /index.php?page=report_sales');
    exit;
}

try {
    $sale = db_fetch_one("SELECT * FROM sales_log WHERE id = ?", 'i', [$id]);
    
    if (!$sale) {
        throw new Exception("Data penjualan tidak ditemukan.");
    }
    
    if ($admin['role'] !== 'superadmin' && $sale['sold_by'] != $admin['id']) {
        throw new Exception("Anda tidak memiliki akses untuk menghapus data ini.");
    }
    
    db_execute("DELETE FROM sales_log WHERE id = ?", 'i', [$id]);
    
    audit_log('hapus_penjualan', "Menghapus riwayat penjualan voucher: {$sale['voucher_username']}", $sale['router_id']);
    
    flash_set('success', 'Data penjualan berhasil dihapus.');
    
} catch (Throwable $e) {
    flash_set('error', 'Gagal menghapus penjualan: ' . $e->getMessage());
}

header('Location: /index.php?page=report_sales');
