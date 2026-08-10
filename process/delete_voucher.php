<?php
/**
 * Process — Delete Voucher(s)
 * Removes from radcheck, radreply, and marks vouchers table as deleted.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'Invalid CSRF.'); header('Location: /index.php?page=voucher_list'); exit;
    }
    $ids_str = $_POST['ids'] ?? '';
    $ids = array_map('intval', array_filter(explode(',', $ids_str)));
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Single delete via link
    $ids = [(int)get('id')];
}

if (empty($ids)) {
    flash_set('error', 'Tidak ada voucher yang dipilih.');
    header('Location: /index.php?page=voucher_list'); exit;
}

// Fetch usernames for these IDs (only vouchers accessible to this admin)
$access = accessible_router_ids();
$pls    = implode(',', array_fill(0, count($ids), '?'));
$types  = str_repeat('i', count($ids));

$vouchers = db_fetch_all(
    "SELECT id, username, router_id FROM vouchers WHERE id IN ({$pls}) AND status != 'deleted'",
    $types, $ids
);

// Filter by router access for operators
if ($access !== null) {
    $vouchers = array_filter($vouchers, fn($v) => $v['router_id'] === null || in_array($v['router_id'], $access));
}

if (empty($vouchers)) {
    flash_set('error', 'Voucher tidak ditemukan atau akses ditolak.');
    header('Location: /index.php?page=voucher_list'); exit;
}

db_begin();
try {
    foreach ($vouchers as $v) {
        $u = $v['username'];
        // Remove from radcheck, radreply, and radusergroup
        db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$u]);
        db_execute("DELETE FROM radreply WHERE username = ?", 's', [$u]);
        db_execute("DELETE FROM radusergroup WHERE username = ?", 's', [$u]);
        
        // Mark as deleted in vouchers (Hard delete as requested)
        db_execute("DELETE FROM vouchers WHERE id = ?", 'i', [$v['id']]);
    }
    db_commit();

    $deleted_users = array_column($vouchers, 'username');
    audit_log('delete_voucher', implode(',', $deleted_users), 0,
        json_encode(['count' => count($vouchers)]));

    flash_set('success', count($vouchers) . ' voucher berhasil dihapus.');
} catch (Throwable $e) {
    db_rollback();
    flash_set('error', 'Gagal hapus voucher: ' . $e->getMessage());
}

header('Location: /index.php?page=voucher_list');
