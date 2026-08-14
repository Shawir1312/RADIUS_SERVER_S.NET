<?php
/**
 * GenieACS Servers — Delete
 */
$admin = current_admin();
if ($admin['role'] !== 'superadmin') {
    flash_set('error', 'Akses ditolak.');
    header('Location: /index.php?page=dashboard');
    exit;
}

$id = (int)get('id');
if ($id) {
    db_execute("DELETE FROM genie_config WHERE id = ?", 'i', [$id]);
    flash_set('success', 'Server GenieACS berhasil dihapus.');
}

header('Location: /index.php?page=genieacs_servers');
exit;
