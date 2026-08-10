<?php
/** Voucher Delete from list page */
// Redirect to process handler
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../include/functions.php';
auth_check();
$id = (int)get('id');
if (!$id) { flash_set('error', 'ID tidak valid.'); header('Location: /index.php?page=voucher_list'); exit; }
// Simulate POST for the delete handler
$_POST['ids'] = (string)$id;
$_POST['csrf'] = $_SESSION['csrf_token'];
include __DIR__ . '/../../process/delete_voucher.php';
