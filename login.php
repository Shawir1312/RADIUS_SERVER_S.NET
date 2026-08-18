<?php
/**
 * S.NET RADIUS Hotspot Manager — Login Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/include/functions.php';

auth_start();

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: /index.php?page=dashboard');
    exit;
}

$error   = '';
$timeout = !empty($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (!auth_login($username, $password)) {
        $error = 'Username atau password salah, atau akun tidak aktif.';
        // Brief delay to mitigate brute force
        sleep(1);
    } else {
        flash_set('success', 'Selamat datang, ' . htmlspecialchars($_SESSION['admin_name'] ?: $username) . '!');
        header('Location: /index.php?page=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="S.NET, S.NET LUSUO, S.NET MOROTAI, SNET MANAGER, WIFI, HOTSPOT, snet manager, snet desa lusuo, snet lusuo, snet morotai, snet wifi, wifi snet, wifi morotai, snet manger, aplikasi snet, s.net manager, s.net desa lusuo, s.net wifi, wifi s.net, aplikasi s.net">
    <meta name="description" content="APLIKASI S.NET MANAGER, ALAMAT : PULAU MOROTAI, MOROTAI UTARA DESA LUSUO">
    <meta name="author" content="S.NET">
    <meta name="robots" content="index, follow">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= filemtime(__DIR__ . '/assets/css/app.css') ?>">
    <style>
        body { margin: 0; padding: 0; }
        .theme-toggle-login { position: fixed; top: 15px; right: 15px; }
        .login-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff !important;
            padding: 8px 18px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            margin-bottom: 8px;
        }
        .login-logo-box img {
            height: 52px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            display: block;
        }
    </style>
    <script>
        (function() {
            const theme = localStorage.getItem('snet-theme') || 'light';
            if (theme === 'dark') document.documentElement.setAttribute('data-bs-theme', 'dark');
        })();
    </script>
</head>
<body class="login-body">

<button type="button" class="btn btn-outline-secondary theme-toggle theme-toggle-login" title="Ganti Tema" aria-label="Ganti Tema">
    <i class="bi bi-moon-fill dark-icon d-none"></i>
    <i class="bi bi-sun-fill light-icon"></i>
</button>

<main class="login-card">
    <!-- Logo -->
    <div class="login-logo">
        <div class="login-logo-box">
            <img src="/assets/img/logo.png?v=<?= filemtime(__DIR__ . '/assets/img/logo.png') ?>" alt="<?= APP_COMPANY ?>">
        </div>
        <h5><?= APP_NAME ?></h5>
        <p><?= APP_COMPANY ?></p>
    </div>
    <div class="login-divider"></div>

    <?php if ($timeout): ?>
    <div class="alert alert-warning auto-dismiss" role="alert">
        <i class="bi bi-clock-history me-2"></i>Sesi Anda berakhir. Silakan login kembali.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/login.php" autocomplete="off" novalidate>
        <div class="mb-3">
            <label class="form-label" for="username">
                <i class="bi bi-person me-1 text-blue"></i>Username
            </label>
            <input type="text" class="form-control" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="Masukkan username" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">
                <i class="bi bi-lock me-1 text-blue"></i>Password
            </label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Masukkan password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1" aria-label="Tampilkan password">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg fw-600">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </button>
    </form>

    <div class="text-center mt-3">
        <small class="text-muted">
            Belum setup? <a href="/install.php" style="color: #0b5ed7; font-weight: 500;">Klik di sini untuk instalasi</a>
        </small>
    </div>

    <div class="text-center mt-4 mb-2">
        <div style="font-size: 0.85rem; font-weight: 600; color: var(--bs-secondary-color); letter-spacing: 0.5px;">
            &copy; <?= date('Y') ?> <span style="background: linear-gradient(135deg, #0b5ed7, #20c997); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Shawir</span>
        </div>
        <div style="font-size: 0.7rem; color: var(--bs-tertiary-color); font-weight: 500; margin-top: 2px;">
            S.NET MANAGER &bull; All Rights Reserved
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script>
const pwd = document.getElementById('password');
const eye = document.getElementById('eyeIcon');
const toggleBtn = document.getElementById('togglePwd');
if(toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        if (pwd.type === 'password') {
            pwd.type = 'text';
            eye.className = 'bi bi-eye-slash';
        } else {
            pwd.type = 'password';
            eye.className = 'bi bi-eye';
        }
    });
}
// Auto-dismiss alerts
document.querySelectorAll('.auto-dismiss').forEach(el => {
    setTimeout(() => el.style.display = 'none', 5000);
});
</script>
</body>
</html>
