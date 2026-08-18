<?php
/**
 * Process — Delete PPPoE Payment Record (Superadmin only)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();
auth_require_superadmin();

$id = (int)get('id');
$csrf = get('csrf');

if ($id <= 0 || empty($csrf) || $csrf !== $_SESSION['csrf_token']) {
    flash_set('error', 'Token CSRF atau parameter tidak valid.');
    header('Location: /index.php?page=pppoe_payments');
    exit;
}

$payment = db_fetch_one("SELECT * FROM pppoe_payments WHERE id = ?", 'i', [$id]);
if (!$payment) {
    flash_set('error', 'Data pembayaran tidak ditemukan.');
    header('Location: /index.php?page=pppoe_payments');
    exit;
}

try {
    db_execute("DELETE FROM pppoe_payments WHERE id = ?", 'i', [$id]);
    audit_log('delete_pppoe_payment', "Hapus pembayaran ID #{$id} (Order: {$payment['midtrans_order_id']}, Rp {$payment['amount']})");
    flash_set('success', 'Catatan pembayaran berhasil dihapus.');
} catch (Throwable $e) {
    flash_set('error', 'Gagal menghapus pembayaran: ' . $e->getMessage());
}

header('Location: /index.php?page=pppoe_payments');
exit;
