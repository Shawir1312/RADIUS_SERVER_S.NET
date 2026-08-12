<?php
/**
 * Process — Backup (SQL dump) / Restore
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
auth_check();
auth_require_superadmin();

$action = post('action', '') ?: get('action', 'backup');

// BACKUP — SQL dump of app tables
if ($action === 'backup' && isset($_GET['csrf']) && $_GET['csrf'] === $_SESSION['csrf_token']) {
    $date   = date('Ymd_His');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=snet_backup_{$date}.sql");

    echo "-- S.NET RADIUS Manager Full Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $res = db()->query("SHOW TABLES");
    while ($r = $res->fetch_row()) $tables[] = $r[0];

    foreach ($tables as $table) {
        echo "-- --------------------------------------------------------\n";
        echo "-- Table structure for table `{$table}`\n";
        echo "-- --------------------------------------------------------\n";
        echo "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $res2 = db()->query("SHOW CREATE TABLE `{$table}`");
        if ($res2 && $row2 = $res2->fetch_row()) {
            echo $row2[1] . ";\n\n";
        }
        
        $result = db()->query("SELECT * FROM `{$table}`");
        if (!$result || $result->num_rows === 0) {
            echo "\n";
            continue;
        }

        echo "-- Dumping data for table `{$table}`\n";
        
        $cols = [];
        $fi   = $result->fetch_fields();
        foreach ($fi as $f) $cols[] = '`' . $f->name . '`';
        $col_str = implode(',', $cols);

        while ($row = $result->fetch_assoc()) {
            $vals = array_map(function($v) {
                return $v === null ? 'NULL' : "'" . db()->real_escape_string((string)$v) . "'";
            }, array_values($row));
            echo "INSERT INTO `{$table}` ({$col_str}) VALUES (" . implode(',', $vals) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    exit;
}

// RESTORE
if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'Invalid CSRF.'); header('Location: /index.php?page=backup'); exit;
    }
    if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        flash_set('error', 'File tidak valid.'); header('Location: /index.php?page=backup'); exit;
    }
    $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
    if ($sql === false) {
        flash_set('error', 'Gagal membaca file.'); header('Location: /index.php?page=backup'); exit;
    }
    db()->multi_query($sql);
    while (db()->more_results()) db()->next_result();
    audit_log('restore_backup', 'sql_file');
    flash_set('success', 'Database berhasil direstore.');
    header('Location: /index.php?page=backup'); exit;
}

flash_set('error', 'Invalid request.');
header('Location: /index.php?page=backup');
