<?php
/**
 * Process — Save Admin (Add/Edit/Delete)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
auth_check();
auth_require_superadmin();

$action = get('action', '');

// DELETE via GET link
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (int)get('id');
    if (!$id || !isset($_GET['csrf']) || $_GET['csrf'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'Invalid request.'); header('Location: /index.php?page=admin_list'); exit;
    }
    if ($id === current_admin()['id']) {
        flash_set('error', 'Tidak bisa menghapus akun sendiri.'); header('Location: /index.php?page=admin_list'); exit;
    }
    db_execute("DELETE FROM admins WHERE id = ?", 'i', [$id]);
    flash_set('success', 'Admin berhasil dihapus.');
    header('Location: /index.php?page=admin_list'); exit;
}

// POST (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /index.php?page=admin_list'); exit; }
if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
    flash_set('error', 'Invalid CSRF.'); header('Location: /index.php?page=admin_list'); exit;
}

$id          = (int)post('id');
$username    = sanitize(post('username'));
$full_name   = sanitize(post('full_name', ''));
$password    = post('password', '');
$role        = post('role', 'operator') === 'superadmin' ? 'superadmin' : 'operator';
$is_active   = post('is_active', '1') === '1' ? 1 : 0;
$ra          = $_POST['router_access'] ?? [];
$router_access = empty($ra) ? null : json_encode(array_map('intval', $ra));

if (!$username) {
    flash_set('error', 'Username wajib diisi.'); header('Location: /index.php?page=' . ($id ? 'admin_edit&id='.$id : 'admin_add')); exit;
}

if ($id > 0) {
    // Edit
    if ($password) {
        if (strlen($password) < 6) { flash_set('error', 'Password minimal 6 karakter.'); header('Location: /index.php?page=admin_edit&id='.$id); exit; }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_execute("UPDATE admins SET username=?, full_name=?, password=?, role=?, router_access=?, is_active=? WHERE id=?",
            'sssssii', [$username, $full_name, $hash, $role, $router_access, $is_active, $id]);
    } else {
        db_execute("UPDATE admins SET username=?, full_name=?, role=?, router_access=?, is_active=? WHERE id=?",
            'ssssii', [$username, $full_name, $role, $router_access, $is_active, $id]);
    }
    flash_set('success', "Admin '{$username}' berhasil diperbarui.");
} else {
    if (!$password || strlen($password) < 6) {
        flash_set('error', 'Password baru minimal 6 karakter.'); header('Location: /index.php?page=admin_add'); exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    db_execute("INSERT INTO admins (username, password, full_name, role, router_access, is_active) VALUES (?,?,?,?,?,?)",
        'sssssi', [$username, $hash, $full_name, $role, $router_access, $is_active]);
    flash_set('success', "Admin '{$username}' berhasil ditambahkan.");
}

audit_log($id ? 'edit_admin' : 'add_admin', $username);
header('Location: /index.php?page=admin_list');
