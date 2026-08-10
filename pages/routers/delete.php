<?php
/**
 * Delete Router
 */
auth_require_superadmin();
$id = (int)get('id');
if (!$id) { flash_set('error', 'Router tidak valid.'); header('Location: /index.php?page=router_list'); exit; }
$router = get_router($id);
if (!$router) { flash_set('error', 'Router tidak ditemukan.'); header('Location: /index.php?page=router_list'); exit; }
// Delete from nas table
if ($router['nas_id']) db_execute("DELETE FROM nas WHERE id = ?", 'i', [(int)$router['nas_id']]);
db_execute("DELETE FROM routers WHERE id = ?", 'i', [$id]);
audit_log('delete_router', $router['ip_address'], $id);
flash_set('success', "Router '{$router['name']}' berhasil dihapus.");
header('Location: /index.php?page=router_list');
