<?php
/**
 * Process - Delete Penagihan
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
    header('Location: /index.php?page=penagihan_report');
    exit;
}

try {
    // Check if the record exists and belongs to today and was created by this admin
    $penagihan = db_fetch_one("SELECT * FROM penagihan WHERE id = ?", 'i', [$id]);
    
    if (!$penagihan) {
        throw new Exception("Data penagihan tidak ditemukan.");
    }
    
    if ($penagihan['ditagih_oleh'] != $admin['id'] && $admin['role'] !== 'superadmin') {
        throw new Exception("Anda tidak memiliki akses untuk menghapus data ini.");
    }
    
    if (date('Y-m-d', strtotime($penagihan['created_at'])) !== date('Y-m-d') && $admin['role'] !== 'superadmin') {
        throw new Exception("Hanya data hari ini yang bisa dihapus/dibatalkan.");
    }
    
    // Delete
    db_execute("DELETE FROM penagihan WHERE id = ?", 'i', [$id]);
    
    // Audit log
    audit_log('hapus_penagihan', "Membatalkan penagihan ID: {$id} sejumlah Rp " . number_format($penagihan['total_pendapatan'], 0, ',', '.'), $penagihan['router_id']);
    
    flash_set('success', 'Data penagihan berhasil dibatalkan dan dihapus.');
    
} catch (Throwable $e) {
    flash_set('error', 'Gagal membatalkan penagihan: ' . $e->getMessage());
}

header('Location: /index.php?page=penagihan_report');
