<?php
/**
 * S.NET RADIUS Manager — HTML Header + Top Navbar
 * Include at top of every authenticated page.
 * Requires: $page_title variable set before including.
 */

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$admin = current_admin();
$flash_html = flash_html();
$initials = strtoupper(substr($admin['name'] ?: $admin['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
    <meta name="description" content="S.NET RADIUS Hotspot Voucher Management System">
    <title><?= htmlspecialchars($page_title ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <!-- Print CSS -->
    <link rel="stylesheet" href="/assets/css/print.css" media="print">
</head>
<body>
<!-- Page Loader -->
<div id="page-loader"><div class="spinner-ring"></div></div>

<!-- Mobile Sidebar Backdrop -->
<div id="sidebar-backdrop"></div>

<!-- Sidebar -->
<?php include __DIR__ . '/sidebar.php'; ?>

<!-- Topbar -->
<nav id="topbar">
    <div class="topbar-left">
        <button id="sidebar-toggle" title="Toggle Sidebar">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title d-flex align-items-center gap-2">
            <!-- Mobile Logo -->
            <img src="/assets/img/logo.png" class="d-inline-block d-md-none" style="height:24px;" alt="Logo">
            <span class="d-none d-sm-inline"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></span>
        </span>
    </div>
    <div class="topbar-right">
        <!-- Router Filter (shown on pages that support it) -->
        <?php if (!empty($show_router_filter) && !empty($all_routers)): ?>
        <select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href=this.value">
            <option value="?page=<?= get('page') ?>">Semua Router</option>
            <?php foreach ($all_routers as $r): ?>
            <option value="?page=<?= get('page') ?>&router_id=<?= $r['id'] ?>"
                <?= (get('router_id') == $r['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($r['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <!-- User Menu -->
        <div class="dropdown">
            <div class="topbar-user" data-bs-toggle="dropdown">
                <div class="topbar-avatar"><?= $initials ?></div>
                <div class="topbar-user-info d-none d-md-block">
                    <div class="topbar-user-name"><?= htmlspecialchars($admin['name'] ?: $admin['username']) ?></div>
                    <small><?= $admin['role'] === 'superadmin' ? 'Super Admin' : 'Operator' ?></small>
                </div>
                <i class="bi bi-chevron-down" style="font-size:.7rem;color:var(--gray-500)"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:180px;">
                <li><h6 class="dropdown-header"><?= htmlspecialchars($admin['username']) ?></h6></li>
                <?php if ($admin['role'] === 'superadmin'): ?>
                <li><a class="dropdown-item" href="/index.php?page=settings"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main id="main-content">

<!-- Flash Message -->
<?php if ($flash_html): echo $flash_html; endif; ?>
