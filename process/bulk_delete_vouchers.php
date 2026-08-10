<?php
/**
 * Process — Bulk Delete Vouchers (by filter)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php?page=voucher_list'); exit;
}
if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    flash_set('error', 'Invalid CSRF.'); header('Location: /index.php?page=voucher_list'); exit;
}
if (empty($_POST['confirm_all'])) {
    flash_set('error', 'Konfirmasi hapus belum dicentang.'); header('Location: /index.php?page=voucher_list'); exit;
}

$profile_id = post('profile_id');
$batch_id   = sanitize(post('batch_id'));
$status     = post('status');

// Build WHERE
$where = ["status != 'deleted'"];
$params = [];
$types = '';

if ($profile_id !== '') {
    $where[] = "profile_id = ?"; $params[] = (int)$profile_id; $types .= 'i';
}
if ($batch_id !== '') {
    $where[] = "batch_id = ?"; $params[] = $batch_id; $types .= 's';
}
if ($status !== '') {
    $where[] = "status = ?"; $params[] = $status; $types .= 's';
}

// Router Access
$access = accessible_router_ids();
if ($access !== null) {
    if (empty($access)) {
        flash_set('error', 'Anda tidak memiliki akses ke router mana pun.');
        header('Location: /index.php?page=voucher_list'); exit;
    }
    $pls = implode(',', array_fill(0, count($access), '?'));
    $where[] = "(router_id IN ({$pls}) OR router_id IS NULL)";
    foreach ($access as $rid) { $params[] = $rid; $types .= 'i'; }
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Fetch all target usernames to delete from radcheck/radreply
$vouchers = db_fetch_all("SELECT id, username FROM vouchers {$where_sql}", $types, $params);

if (empty($vouchers)) {
    flash_set('warning', 'Tidak ada voucher yang cocok dengan kriteria filter.');
    header('Location: /index.php?page=voucher_list'); exit;
}

db_begin();
try {
    // Delete in chunks for safety
    $chunks = array_chunk($vouchers, 1000);
    
    foreach ($chunks as $chunk) {
        $usernames = array_column($chunk, 'username');
        $ids = array_column($chunk, 'id');
        
        $u_pls = implode(',', array_fill(0, count($usernames), '?'));
        $u_types = str_repeat('s', count($usernames));
        
        $i_pls = implode(',', array_fill(0, count($ids), '?'));
        $i_types = str_repeat('i', count($ids));
        
        // Remove from Mikrotik RADIUS
        db_execute("DELETE FROM radcheck WHERE username IN ({$u_pls})", $u_types, $usernames);
        db_execute("DELETE FROM radreply WHERE username IN ({$u_pls})", $u_types, $usernames);
        db_execute("DELETE FROM radusergroup WHERE username IN ({$u_pls})", $u_types, $usernames);
        
        // Mark as deleted in App (Hard delete as requested)
        db_execute("DELETE FROM vouchers WHERE id IN ({$i_pls})", $i_types, $ids);
    }
    
    db_commit();

    // Audit log
    $target = "Filter: ";
    if ($profile_id) $target .= "Profile $profile_id, ";
    if ($batch_id) $target .= "Batch $batch_id, ";
    if ($status) $target .= "Status $status, ";
    if ($target === "Filter: ") $target = "ALL VOUCHERS";
    
    audit_log('bulk_delete_vouchers', $target, 0, json_encode(['count' => count($vouchers)]));

    flash_set('success', number_format(count($vouchers)) . ' voucher berhasil dihapus massal.');
} catch (Throwable $e) {
    db_rollback();
    flash_set('error', 'Gagal hapus massal: ' . $e->getMessage());
}

header('Location: /index.php?page=voucher_list');
