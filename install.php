<?php
/**
 * S.NET RADIUS Manager — Installation Wizard
 * Run this once to set up database tables and first admin.
 * After completion, config/.installed is created to prevent re-running.
 */

define('BASE_PATH', __DIR__);
define('CONFIG_PATH', __DIR__ . '/config');
define('APP_NAME', 'S.NET RADIUS Manager');
define('APP_COMPANY', 'PT Network Inovation Solutions');
define('APP_TIMEZONE', 'Asia/Jayapura');
date_default_timezone_set(APP_TIMEZONE);

if (file_exists(CONFIG_PATH . '/.installed')) {
    die('<div style="font-family:sans-serif;text-align:center;padding:60px;color:#1565C0;">
        <h2>✓ Sudah Terinstal</h2>
        <p>Aplikasi sudah diinstal. <a href="/login.php">Login di sini</a></p>
    </div>');
}

if (!is_writable(CONFIG_PATH)) {
    die('<div style="font-family:sans-serif;text-align:center;padding:60px;color:#D32F2F;">
        <h2><span style="font-size:3rem;">🔒</span><br> Akses Ditolak (Permission Denied)</h2>
        <p>Folder <strong>config/</strong> tidak dapat ditulisi oleh sistem. Ini menyebabkan instalasi gagal menyimpan pengaturan.</p>
        <p style="background:#f8d7da;padding:15px;border-radius:8px;display:inline-block;text-align:left;font-family:monospace;font-size:0.9rem;">
        <strong>Cara mengatasi via SSH / Terminal:</strong><br><br>
        chown -R www:www ' . BASE_PATH . '<br>
        chmod -R 775 ' . CONFIG_PATH . '<br>
        </p>
        <p>Setelah menjalankan perintah di atas, silakan <em>refresh</em> halaman ini.</p>
    </div>');
}

session_start();

$step     = (int)($_GET['step'] ?? 1);
$errors   = [];
$success  = [];

// ── Step 1: DB Test ─────────────────────────────────────
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? 'radius');
    $db_port = (int)($_POST['db_port'] ?? 3306);

    try {
        // Connect WITHOUT db_name first to attempt creation
        @$conn_init = new mysqli($db_host, $db_user, $db_pass, "", $db_port);
        if ($conn_init->connect_error) throw new Exception($conn_init->connect_error);
        
        // Create database if not exists
        $esc_db = $conn_init->real_escape_string($db_name);
        if (!$conn_init->query("CREATE DATABASE IF NOT EXISTS `$esc_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw new Exception("Gagal membuat database: " . $conn_init->error);
        }
        $conn_init->close();

        // Now connect to the newly created database
        @$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        if ($conn->connect_error) throw new Exception($conn->connect_error);
        $conn->set_charset('utf8mb4');

        // Save to session
        $_SESSION['install_db'] = compact('db_host','db_user','db_pass','db_name','db_port');
        $success[] = 'Koneksi database berhasil!';
        $step = 3;
    } catch (Exception $e) {
        $errors[] = 'Gagal koneksi: ' . $e->getMessage();
        $step = 2;
    }
}

// ── Step 2: Create Tables ───────────────────────────────
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['install_db'])) {
    $dbc = $_SESSION['install_db'];
    $conn = new mysqli($dbc['db_host'], $dbc['db_user'], $dbc['db_pass'], $dbc['db_name'], $dbc['db_port']);
    $conn->set_charset('utf8mb4');

    $sqls = [
        // FreeRADIUS standard tables
        "CREATE TABLE IF NOT EXISTS radcheck (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            username varchar(64) NOT NULL DEFAULT '',
            attribute varchar(64) NOT NULL DEFAULT '',
            op char(2) NOT NULL DEFAULT ':=',
            value varchar(253) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY username (username(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radreply (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            username varchar(64) NOT NULL DEFAULT '',
            attribute varchar(64) NOT NULL DEFAULT '',
            op char(2) NOT NULL DEFAULT '=',
            value varchar(253) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY username (username(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radgroupcheck (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            groupname varchar(64) NOT NULL DEFAULT '',
            attribute varchar(64) NOT NULL DEFAULT '',
            op char(2) NOT NULL DEFAULT ':=',
            value varchar(253) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY groupname (groupname(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radgroupreply (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            groupname varchar(64) NOT NULL DEFAULT '',
            attribute varchar(64) NOT NULL DEFAULT '',
            op char(2) NOT NULL DEFAULT '=',
            value varchar(253) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY groupname (groupname(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radusergroup (
            username varchar(64) NOT NULL DEFAULT '',
            groupname varchar(64) NOT NULL DEFAULT '',
            priority int(11) NOT NULL DEFAULT 1,
            KEY username (username(32))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radacct (
            radacctid bigint(21) NOT NULL AUTO_INCREMENT,
            acctsessionid varchar(64) NOT NULL DEFAULT '',
            acctuniqueid varchar(32) NOT NULL DEFAULT '',
            username varchar(64) NOT NULL DEFAULT '',
            realm varchar(64) DEFAULT '',
            nasipaddress varchar(15) NOT NULL DEFAULT '',
            nasportid varchar(15) DEFAULT NULL,
            nasporttype varchar(32) DEFAULT NULL,
            acctstarttime datetime DEFAULT NULL,
            acctupdatetime datetime DEFAULT NULL,
            acctstoptime datetime DEFAULT NULL,
            acctinterval int(12) DEFAULT NULL,
            acctsessiontime int(12) unsigned DEFAULT NULL,
            acctauthentic varchar(32) DEFAULT NULL,
            connectinfo_start varchar(50) DEFAULT NULL,
            connectinfo_stop varchar(50) DEFAULT NULL,
            acctinputoctets bigint(20) DEFAULT NULL,
            acctoutputoctets bigint(20) DEFAULT NULL,
            calledstationid varchar(50) NOT NULL DEFAULT '',
            callingstationid varchar(50) NOT NULL DEFAULT '',
            acctterminatecause varchar(32) NOT NULL DEFAULT '',
            servicetype varchar(32) DEFAULT NULL,
            framedprotocol varchar(32) DEFAULT NULL,
            framedipaddress varchar(15) NOT NULL DEFAULT '',
            framedipv6address varchar(45) NOT NULL DEFAULT '',
            framedipv6prefix varchar(45) NOT NULL DEFAULT '',
            framedinterfaceid varchar(44) NOT NULL DEFAULT '',
            delegatedipv6prefix varchar(45) NOT NULL DEFAULT '',
            class varchar(64) DEFAULT NULL,
            PRIMARY KEY (radacctid),
            UNIQUE KEY acctuniqueid (acctuniqueid),
            KEY username (username),
            KEY nasipaddress (nasipaddress),
            KEY acctsessionid (acctsessionid),
            KEY acctstarttime (acctstarttime),
            KEY acctstoptime (acctstoptime),
            KEY acctupdatetime (acctupdatetime),
            KEY framedipaddress (framedipaddress)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS radpostauth (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(64) NOT NULL DEFAULT '',
            pass varchar(64) NOT NULL DEFAULT '',
            reply varchar(32) NOT NULL DEFAULT '',
            authdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS nas (
            id int(10) NOT NULL AUTO_INCREMENT,
            nasname varchar(128) NOT NULL,
            shortname varchar(32) DEFAULT NULL,
            type varchar(30) DEFAULT 'other',
            ports int(5) DEFAULT NULL,
            secret varchar(60) NOT NULL DEFAULT 'secret',
            server varchar(64) DEFAULT NULL,
            community varchar(50) DEFAULT NULL,
            description varchar(200) DEFAULT 'RADIUS Client',
            PRIMARY KEY (id),
            KEY nasname (nasname)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // Custom app tables
        "CREATE TABLE IF NOT EXISTS routers (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            ip_address varchar(45) NOT NULL,
            nas_id int(11) DEFAULT NULL,
            nas_ip varchar(128) DEFAULT '0.0.0.0/0',
            api_user varchar(64) DEFAULT 'admin',
            api_password varchar(128) DEFAULT '',
            api_port smallint(5) unsigned DEFAULT 8728,
            radius_secret varchar(128) NOT NULL,
            location text DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            last_seen datetime DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ip_address (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS profiles (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            display_name varchar(100) DEFAULT NULL,
            validity_value int(11) DEFAULT 30,
            validity_unit enum('minutes','hours','days') DEFAULT 'days',
            duration_value int(11) DEFAULT 30,
            duration_unit enum('minutes','hours','days') DEFAULT 'days',
            quota_mb bigint(20) DEFAULT 0,
            rate_up varchar(20) DEFAULT '0',
            rate_down varchar(20) DEFAULT '0',
            price decimal(10,2) DEFAULT 0.00,
            reseller_percent decimal(5,2) DEFAULT 0.00,
            router_id int(11) DEFAULT NULL,
            description text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS vouchers (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(64) NOT NULL,
            password varchar(128) NOT NULL,
            profile_id int(11) NOT NULL,
            router_id int(11) DEFAULT NULL,
            batch_id varchar(64) DEFAULT NULL,
            status enum('unused','active','expired','deleted') DEFAULT 'unused',
            used_at datetime DEFAULT NULL,
            expired_at datetime DEFAULT NULL,
            generated_by int(11) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username),
            KEY batch_id (batch_id),
            KEY status (status),
            KEY profile_id (profile_id),
            KEY router_id (router_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS admins (
            id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(64) NOT NULL,
            password varchar(255) NOT NULL,
            full_name varchar(100) DEFAULT NULL,
            role enum('superadmin','operator') DEFAULT 'operator',
            router_access json DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            last_login datetime DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS audit_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            admin_id int(11) DEFAULT NULL,
            admin_name varchar(64) DEFAULT NULL,
            action varchar(50) DEFAULT NULL,
            target varchar(100) DEFAULT NULL,
            router_id int(11) DEFAULT NULL,
            detail text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY admin_id (admin_id),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS sales_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            voucher_id int(11) DEFAULT NULL,
            voucher_username varchar(64) DEFAULT NULL,
            profile_id int(11) DEFAULT NULL,
            profile_name varchar(100) DEFAULT NULL,
            router_id int(11) DEFAULT NULL,
            sold_by int(11) DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            sold_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY router_id (router_id),
            KEY sold_at (sold_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        "CREATE TABLE IF NOT EXISTS penagihan (
            id int(11) NOT NULL AUTO_INCREMENT,
            router_id int(11) NOT NULL,
            profile_id int(11) NOT NULL,
            total_pendapatan decimal(15,2) DEFAULT 0.00,
            bagian_reseller decimal(15,2) DEFAULT 0.00,
            pendapatan_bersih decimal(15,2) DEFAULT 0.00,
            estimasi_voucher int(11) DEFAULT 0,
            voucher_aktual int(11) DEFAULT 0,
            status_kecocokan enum('sesuai','tekor','lebih') DEFAULT 'sesuai',
            catatan text,
            ditagih_oleh int(11) NOT NULL,
            tanggal date NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY router_id (router_id),
            KEY profile_id (profile_id),
            KEY tanggal (tanggal)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // ── V2 Addons: PPPoE, GenieACS, Portal ──
        "CREATE TABLE IF NOT EXISTS genie_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) DEFAULT 'GenieACS',
            url VARCHAR(255) DEFAULT 'http://localhost:7557',
            username VARCHAR(100) DEFAULT '',
            password VARCHAR(255) DEFAULT '',
            is_active TINYINT(1) DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id VARCHAR(30) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            phone VARCHAR(20) DEFAULT '',
            address TEXT,
            genie_device_id VARCHAR(255) DEFAULT '',
            device_serial VARCHAR(100) DEFAULT '',
            device_brand ENUM('FiberHome','CData','Huawei','ZTE','Unknown') DEFAULT 'Unknown',
            device_model VARCHAR(100) DEFAULT '',
            ont_tag VARCHAR(100) DEFAULT '',
            router_id INT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            notes TEXT,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS ont_configs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            genie_device_id VARCHAR(255) NOT NULL,
            config_type ENUM('wifi','wan','binding') NOT NULL,
            config_name VARCHAR(150) DEFAULT '',
            config_data TEXT NOT NULL,
            push_status ENUM('success','failed','pending') DEFAULT 'success',
            push_count INT DEFAULT 1,
            last_pushed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS pppoe_customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            router_id INT NOT NULL,
            pppoe_username VARCHAR(100) NOT NULL,
            full_name VARCHAR(150) NOT NULL DEFAULT '',
            phone VARCHAR(25) DEFAULT '',
            address TEXT,
            profile VARCHAR(100) DEFAULT '',
            monthly_price INT DEFAULT 0,
            due_day TINYINT DEFAULT 1,
            status ENUM('active','isolated','suspended') DEFAULT 'active',
            isolated_at DATETIME DEFAULT NULL,
            isolated_reason VARCHAR(255) DEFAULT '',
            last_paid_at DATE DEFAULT NULL,
            last_paid_amount INT DEFAULT 0,
            notes TEXT,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_router_user (router_id, pppoe_username),
            FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS pppoe_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            amount INT NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'cash',
            midtrans_order_id VARCHAR(100) DEFAULT NULL,
            midtrans_tx_id VARCHAR(100) DEFAULT NULL,
            midtrans_status VARCHAR(50) DEFAULT NULL,
            period_month TINYINT NOT NULL,
            period_year SMALLINT NOT NULL,
            paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes VARCHAR(255) DEFAULT '',
            created_by INT DEFAULT NULL,
            FOREIGN KEY (customer_id) REFERENCES pppoe_customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS pppoe_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "INSERT IGNORE INTO pppoe_settings (setting_key, setting_value) VALUES
            ('midtrans_server_key', ''),
            ('midtrans_client_key', ''),
            ('midtrans_mode', 'sandbox'),
            ('isolir_profile', 'isolir'),
            ('isolir_redirect_url', '/portal/isolir'),
            ('isolir_grace_days', '3'),
            ('company_name', 'S.NET Internet'),
            ('company_phone', ''),
            ('company_address', '')"
    ];

    $failed = false;
    foreach ($sqls as $sql) {
        if (!$conn->query($sql)) {
            $errors[] = 'SQL Error: ' . $conn->error;
            $failed = true;
            break;
        }
    }

    if (!$failed) {
        $success[] = 'Semua tabel berhasil dibuat!';
        $step = 5;
    } else {
        $step = 4;
    }
}

// ── Step 3: Create first admin ──────────────────────────
if ($step === 6 && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['install_db'])) {
    $adm_user = trim($_POST['adm_user'] ?? '');
    $adm_name = trim($_POST['adm_name'] ?? '');
    $adm_pass = $_POST['adm_pass'] ?? '';
    $adm_pass2 = $_POST['adm_pass2'] ?? '';

    if (!$adm_user || !$adm_pass) {
        $errors[] = 'Username dan password admin wajib diisi.';
        $step = 6;
    } elseif ($adm_pass !== $adm_pass2) {
        $errors[] = 'Password tidak sama.';
        $step = 6;
    } elseif (strlen($adm_pass) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
        $step = 6;
    } else {
        $dbc = $_SESSION['install_db'];
        $conn = new mysqli($dbc['db_host'], $dbc['db_user'], $dbc['db_pass'], $dbc['db_name'], $dbc['db_port']);
        $conn->set_charset('utf8mb4');
        $hash = password_hash($adm_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, role) VALUES (?,?,?,'superadmin') ON DUPLICATE KEY UPDATE password=VALUES(password), full_name=VALUES(full_name), role='superadmin'");
        $stmt->bind_param('sss', $adm_user, $hash, $adm_name);
        if ($stmt->execute()) {
            // Write config
            $env = "<?php\n"
                . "define('DB_HOST', '" . addslashes($dbc['db_host']) . "');\n"
                . "define('DB_USER', '" . addslashes($dbc['db_user']) . "');\n"
                . "define('DB_PASS', '" . addslashes($dbc['db_pass']) . "');\n"
                . "define('DB_NAME', '" . addslashes($dbc['db_name']) . "');\n"
                . "define('DB_PORT', " . (int)$dbc['db_port'] . ");\n"
                . "define('DB_CHARSET', 'utf8mb4');\n";
            file_put_contents(CONFIG_PATH . '/db_local.php', $env);
            touch(CONFIG_PATH . '/.installed');
            $step = 7; // done
        } else {
            $errors[] = 'Gagal buat admin: ' . $conn->error;
            $step = 6;
        }
    }
}

// ── HTML ─────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-body" style="align-items:flex-start;padding:40px 20px;">
<div class="login-card" style="max-width:520px;margin:auto;">
    <div class="login-logo">
        <img src="/assets/img/logo.png" alt="Logo">
        <h5>Instalasi <?= APP_NAME ?></h5>
        <p><?= APP_COMPANY ?></p>
    </div>
    <div class="login-divider"></div>

    <?php foreach ($errors as $e): ?>
    <div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php foreach ($success as $s): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($s) ?></div>
    <?php endforeach; ?>

    <!-- Step Indicator -->
    <div class="d-flex gap-2 mb-4">
        <?php foreach (['DB', 'Tabel', 'Admin', 'Selesai'] as $i => $label): ?>
        <?php $n = $i+1; $active = ($step >= $n*2) ? 'bg-primary' : ($step >= $n*2-1 ? 'bg-primary' : 'bg-secondary'); ?>
        <div class="flex-fill text-center">
            <div class="badge <?= $active ?> w-100 mb-1" style="padding:8px;"><?= $n ?></div>
            <small style="font-size:.7rem;"><?= $label ?></small>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($step <= 2): ?>
    <!-- Step 1: Database -->
    <h6 class="fw-700 mb-3"><i class="bi bi-database me-2 text-blue"></i>Konfigurasi Database</h6>
    <form method="POST" action="/install.php?step=2">
        <div class="mb-3">
            <label class="form-label">Host DB</label>
            <input type="text" class="form-control" name="db_host" value="localhost" required>
        </div>
        <div class="row g-2 mb-3">
            <div class="col">
                <label class="form-label">User DB</label>
                <input type="text" class="form-control" name="db_user" value="radius" required>
            </div>
            <div class="col">
                <label class="form-label">Password DB</label>
                <input type="password" class="form-control" name="db_pass">
            </div>
        </div>
        <div class="row g-2 mb-4">
            <div class="col">
                <label class="form-label">Nama Database</label>
                <input type="text" class="form-control" name="db_name" value="radius" required>
            </div>
            <div class="col">
                <label class="form-label">Port</label>
                <input type="number" class="form-control" name="db_port" value="3306">
            </div>
        </div>
        <button class="btn btn-primary w-100">Test &amp; Lanjut <i class="bi bi-arrow-right ms-2"></i></button>
    </form>

    <?php elseif ($step === 3 || $step === 4): ?>
    <!-- Step 2: Create Tables -->
    <h6 class="fw-700 mb-3"><i class="bi bi-table me-2 text-blue"></i>Buat Tabel Database</h6>
    <p class="text-muted">Klik tombol di bawah untuk membuat semua tabel yang diperlukan (FreeRADIUS + aplikasi).</p>
    <div class="bg-light rounded p-3 mb-3" style="font-size:.75rem;font-family:monospace;">
        radcheck, radreply, radgroupcheck, radgroupreply, radusergroup,<br>
        radacct, nas, routers, profiles, vouchers, admins, audit_log, sales_log, penagihan
    </div>
    <form method="POST" action="/install.php?step=4">
        <button class="btn btn-primary w-100">Buat Tabel <i class="bi bi-arrow-right ms-2"></i></button>
    </form>

    <?php elseif ($step === 5 || $step === 6): ?>
    <!-- Step 3: First Admin -->
    <h6 class="fw-700 mb-3"><i class="bi bi-person-plus me-2 text-blue"></i>Buat Akun Superadmin</h6>
    <form method="POST" action="/install.php?step=6">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="adm_user" value="admin" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" name="adm_name" value="Administrator">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="adm_pass" minlength="6" required>
            <div class="form-text">Minimal 6 karakter</div>
        </div>
        <div class="mb-4">
            <label class="form-label">Ulangi Password</label>
            <input type="password" class="form-control" name="adm_pass2" required>
        </div>
        <button class="btn btn-primary w-100">Selesai Instalasi <i class="bi bi-check-lg ms-2"></i></button>
    </form>

    <?php elseif ($step === 7): ?>
    <!-- Done -->
    <div class="text-center py-3">
        <div style="font-size:4rem; color: #2E7D32;">✓</div>
        <h4 class="fw-700 mt-2">Instalasi Selesai!</h4>
        <p class="text-muted">Aplikasi berhasil diinstal. Silakan login dengan akun yang baru dibuat.</p>
        <a href="/login.php" class="btn btn-primary btn-lg px-5">Login Sekarang</a>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
