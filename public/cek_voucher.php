<?php
/**
 * Public — Cek Status Voucher
 * Halaman ini dapat diakses tanpa login, ditampilkan via iframe di halaman hotspot Mikrotik.
 * Tidak memerlukan session/auth.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

// Izinkan iframe dari mana saja (Mikrotik hotspot)
header('X-Frame-Options: ALLOWALL');

$voucher  = null;
$error    = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = trim($_POST['kode'] ?? '');
    $searched = true;

    if (!$kode) {
        $error = 'Masukkan kode voucher terlebih dahulu.';
    } else {
        $sql = "
            SELECT 
                v.username,
                v.status,
                v.used_at,
                v.expired_at,
                v.created_at,
                p.name          AS profile_name,
                p.duration_value,
                p.duration_unit,
                p.rate_up,
                p.rate_down,
                p.quota_mb,
                a.full_name     AS reseller_name
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN admins   a ON v.generated_by = a.id
            WHERE v.username = ?
        ";
        $voucher = db_fetch_one($sql, 's', [$kode]);

        if (!$voucher) {
            $error = 'Voucher tidak ditemukan. Periksa kembali kode yang Anda masukkan.';
        }
    }
}

// Helper — label & badge status
function status_label(string $s): array {
    return match($s) {
        'unused'  => ['Belum Digunakan',   'bg-secondary'],
        'active'  => ['Sedang Aktif',       'bg-warning text-dark'],
        'expired' => ['Sudah Kedaluwarsa',  'bg-danger'],
        'deleted' => ['Dihapus',            'bg-dark'],
        default   => ['Tidak Diketahui',    'bg-secondary'],
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Voucher — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            padding: 16px;
            font-size: 14px;
        }
        .card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        .card-header { background: #1565C0; color: #fff; border-radius: 12px 12px 0 0 !important; padding: 12px 16px; }
        .info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #dee2e6; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6c757d; font-size: .82rem; }
        .info-value { font-weight: 600; }
        .status-active  { color: #e67e22; }
        .status-expired { color: #c0392b; }
        .status-unused  { color: #6c757d; }
    </style>
</head>
<body>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-ticket-perforated-fill"></i>
            <strong>Cek Status Voucher</strong>
        </div>
        <div class="card-body p-3">
            <form method="POST" class="d-flex gap-2 mb-3">
                <input type="text" class="form-control form-control-sm" name="kode"
                       placeholder="Masukkan kode voucher…"
                       value="<?= htmlspecialchars($_POST['kode'] ?? '') ?>"
                       required autofocus>
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
            </div>

            <?php elseif ($voucher): ?>
            <?php [$label, $badge] = status_label($voucher['status']); ?>

            <!-- Status utama -->
            <div class="text-center mb-3">
                <span class="badge <?= $badge ?> fs-6 px-3 py-2">
                    <?php if ($voucher['status'] === 'active'): ?>
                        <i class="bi bi-wifi me-1"></i>
                    <?php elseif ($voucher['status'] === 'expired'): ?>
                        <i class="bi bi-x-circle me-1"></i>
                    <?php else: ?>
                        <i class="bi bi-ticket me-1"></i>
                    <?php endif; ?>
                    <?= $label ?>
                </span>
            </div>

            <!-- Detail voucher -->
            <div class="info-row">
                <span class="info-label"><i class="bi bi-ticket me-1"></i>Kode Voucher</span>
                <span class="info-value font-monospace"><?= htmlspecialchars($voucher['username']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-box me-1"></i>Paket / Profil</span>
                <span class="info-value"><?= htmlspecialchars($voucher['profile_name'] ?? '-') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-clock me-1"></i>Durasi</span>
                <span class="info-value">
                    <?= ($voucher['duration_value'] ?? 0) > 0
                        ? $voucher['duration_value'] . ' ' . trans_unit($voucher['duration_unit'] ?? 'days')
                        : 'Unlimited' ?>
                </span>
            </div>
            <?php if (($voucher['rate_down'] ?? '0') !== '0' || ($voucher['rate_up'] ?? '0') !== '0'): ?>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-speedometer2 me-1"></i>Kecepatan</span>
                <span class="info-value">↓ <?= $voucher['rate_down'] ?> / ↑ <?= $voucher['rate_up'] ?></span>
            </div>
            <?php endif; ?>
            <?php if (($voucher['quota_mb'] ?? 0) > 0): ?>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-hdd me-1"></i>Kuota</span>
                <span class="info-value"><?= $voucher['quota_mb'] ?> MB</span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-person me-1"></i>Reseller / Dibeli di</span>
                <span class="info-value"><?= htmlspecialchars($voucher['reseller_name'] ?? '-') ?></span>
            </div>
            <?php if ($voucher['used_at']): ?>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-play-circle me-1"></i>Mulai Digunakan</span>
                <span class="info-value"><?= date('d/m/Y H:i', strtotime($voucher['used_at'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($voucher['expired_at']): ?>
            <div class="info-row">
                <span class="info-label"><i class="bi bi-calendar-x me-1"></i>Berlaku Hingga</span>
                <span class="info-value <?= $voucher['status'] === 'expired' ? 'text-danger' : '' ?>">
                    <?= date('d/m/Y H:i', strtotime($voucher['expired_at'])) ?>
                </span>
            </div>
            <?php endif; ?>

            <?php endif; ?>

            <?php if (!$searched): ?>
            <p class="text-muted text-center mb-0" style="font-size:.82rem;">
                <i class="bi bi-info-circle me-1"></i>Masukkan kode voucher untuk mengecek statusnya
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
