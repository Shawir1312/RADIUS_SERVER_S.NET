<?php
/**
 * GenieACS Servers — Save Process
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();
csrf_verify();

$admin = current_admin();
if ($admin['role'] !== 'superadmin') {
    flash_set('error', 'Akses ditolak.');
    header('Location: /index.php?page=dashboard');
    exit;
}

$id = (int)post('id');
$name = trim(post('name'));
$url = rtrim(trim(post('url')), '/');
$username = trim(post('username'));
$password = post('password');
$is_active = (int)post('is_active');

if (!$name || !$url) {
    flash_set('error', 'Data tidak lengkap (Nama dan URL wajib diisi).');
    header('Location: ' . ($id ? "/index.php?page=genieacs_edit&id=$id" : '/index.php?page=genieacs_add'));
    exit;
}

if ($id) {
    if ($password) {
        db_execute("UPDATE genie_config SET name=?, url=?, username=?, password=?, is_active=? WHERE id=?", 
            'ssssii', [$name, $url, $username, $password, $is_active, $id]);
    } else {
        db_execute("UPDATE genie_config SET name=?, url=?, username=?, is_active=? WHERE id=?", 
            'sssii', [$name, $url, $username, $is_active, $id]);
    }
    flash_set('success', 'Konfigurasi GenieACS berhasil diperbarui.');
} else {
    db_execute("INSERT INTO genie_config (name, url, username, password, is_active) VALUES (?, ?, ?, ?, ?)", 
        'ssssi', [$name, $url, $username, $password, $is_active]);
    flash_set('success', 'Server GenieACS berhasil ditambahkan.');
}

header('Location: /index.php?page=genieacs_servers');
exit;
