<?php
/** Delete Profile */
auth_require_superadmin();
$id = (int)get('id');
$used = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM vouchers WHERE profile_id=? AND status='active'", 'i', [$id])['n'] ?? 0);
if ($used > 0) { flash_set('error', "Ada {$used} voucher aktif menggunakan profil ini. Hapus/expire voucher dulu."); header('Location: /index.php?page=profile_list'); exit; }
db_execute("DELETE FROM profiles WHERE id = ?", 'i', [$id]);
flash_set('success', 'Profil berhasil dihapus.');
header('Location: /index.php?page=profile_list');
