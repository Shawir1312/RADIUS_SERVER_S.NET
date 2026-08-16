<?php
session_start();
if (!isset($_SESSION['portal_customer_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

$cid = $_SESSION['portal_customer_id'];
$customer = db_fetch_one("SELECT * FROM pppoe_customers WHERE id = ?", 'i', [$cid]);

if (!$customer) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch payments
$payments = db_fetch_all("SELECT * FROM pppoe_payments WHERE customer_id = ? ORDER BY paid_at DESC LIMIT 5", 'i', [$cid]);

$status_badge = '';
if ($customer['status'] === 'active') {
    $status_badge = '<span class="badge badge-active">Aktif</span>';
} elseif ($customer['status'] === 'isolated') {
    $status_badge = '<span class="badge badge-isolated">Terisolir</span>';
} else {
    $status_badge = '<span class="badge">'.ucfirst($customer['status']).'</span>';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - S.NET Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #f4f7f6; color: #333; }
        .navbar {
            background: #2a5298; color: white; padding: 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .navbar a { color: white; text-decoration: none; font-weight: 500; margin-left: 15px; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px;}
        .card h3 { margin-bottom: 15px; color: #1e3c72; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #eee; padding-bottom: 8px;}
        .info-label { font-weight: 500; color: #666; }
        .info-value { font-weight: 600; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-isolated { background: #fee2e2; color: #991b1b; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px;}
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        
        .btn-logout { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 8px; transition: 0.3s; }
        .btn-logout:hover { background: rgba(255,255,255,0.3); }
        .nav-links a {opacity: 0.8;}
        .nav-links a.active {opacity: 1; font-weight: 600; border-bottom: 2px solid white; padding-bottom: 5px;}
    </style>
</head>
<body>
    <div class="navbar">
        <div style="font-weight: 700; font-size: 18px;">S.NET Portal</div>
        <div class="nav-links">
            <a href="index.php" class="active">Dashboard</a>
            <?php if (!empty($customer['ont_sn'])): ?>
            <a href="wifi.php">Pengaturan WiFi</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">Keluar</a>
        </div>
    </div>

    <div class="container">
        <h2>Selamat datang, <?= htmlspecialchars($customer['full_name']) ?>!</h2>
        <p style="color: #666; margin-bottom: 25px; margin-top: 5px;">Kelola layanan internet S.NET Anda dari sini.</p>

        <div class="grid">
            <div class="card">
                <h3>Informasi Layanan</h3>
                <div class="info-row">
                    <span class="info-label">Status Koneksi</span>
                    <span class="info-value"><?= $status_badge ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username PPPoE</span>
                    <span class="info-value"><?= htmlspecialchars($customer['pppoe_username']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paket Layanan</span>
                    <span class="info-value"><?= htmlspecialchars($customer['profile']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tagihan Bulanan</span>
                    <span class="info-value">Rp <?= number_format($customer['monthly_price'], 0, ',', '.') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Jatuh Tempo</span>
                    <span class="info-value">Tanggal <?= htmlspecialchars($customer['due_day']) ?> Setiap Bulan</span>
                </div>
            </div>
            
            <?php if ($customer['status'] === 'isolated'): ?>
            <div class="card" style="border-left: 4px solid #ef4444;">
                <h3 style="color: #ef4444;">Perhatian!</h3>
                <p>Layanan Anda saat ini sedang <strong>terisolir</strong> karena keterlambatan pembayaran.</p>
                <p style="margin-top: 10px; font-size: 14px; color: #666;">Alasan: <?= htmlspecialchars($customer['isolated_reason']) ?></p>
                <p style="margin-top: 10px;">Silakan selesaikan tagihan Anda agar layanan dapat dinikmati kembali.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Riwayat Pembayaran Terakhir</h3>
            <?php if (count($payments) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Periode Tagihan</th>
                            <th>Nominal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?= date('d M Y H:i', strtotime($p['paid_at'])) ?></td>
                            <td>Bulan <?= $p['period_month'] . '/' . $p['period_year'] ?></td>
                            <td>Rp <?= number_format($p['amount'], 0, ',', '.') ?></td>
                            <td><?= ucfirst($p['payment_method']) ?> (<?= ucfirst($p['midtrans_status'] ?: 'Sukses') ?>)</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">Belum ada riwayat pembayaran.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
