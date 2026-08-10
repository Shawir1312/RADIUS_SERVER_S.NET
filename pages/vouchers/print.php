<?php
/**
 * Voucher — Print Page
 * Print-friendly voucher cards for a batch or individual vouchers.
 */
$page_title = 'Cetak Voucher';

$batch_id   = get('batch_id');
$voucher_id = (int)get('voucher_id');

if ($batch_id) {
    $vouchers = db_fetch_all(
        "SELECT v.*, p.name AS profile_name, p.display_name, p.duration_value, p.duration_unit,
                p.quota_mb, p.rate_up, p.rate_down, p.price,
                r.name AS router_name
         FROM vouchers v
         LEFT JOIN profiles p ON v.profile_id = p.id
         LEFT JOIN routers r ON v.router_id = r.id
         WHERE v.batch_id = ? ORDER BY v.id ASC",
        's', [$batch_id]
    );
} elseif ($voucher_id) {
    $row = db_fetch_one(
        "SELECT v.*, p.name AS profile_name, p.display_name, p.duration_value, p.duration_unit,
                p.quota_mb, p.rate_up, p.rate_down, p.price,
                r.name AS router_name
         FROM vouchers v
         LEFT JOIN profiles p ON v.profile_id = p.id
         LEFT JOIN routers r ON v.router_id = r.id
         WHERE v.id = ?",
        'i', [$voucher_id]
    );
    $vouchers = $row ? [$row] : [];
} else {
    flash_set('error', 'Tidak ada voucher yang dipilih untuk dicetak.');
    header('Location: /index.php?page=voucher_list');
    exit;
}

if (empty($vouchers)) {
    flash_set('error', 'Voucher tidak ditemukan.');
    header('Location: /index.php?page=voucher_list');
    exit;
}

// Header for print page (minimal)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/print.css" media="print">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .voucher-grid { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-start; }
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Toolbar (hidden on print) -->
<div class="no-print mb-3 d-flex gap-2 align-items-center flex-wrap p-3 bg-white rounded shadow-sm">
    <img src="/assets/img/logo.png" height="36" alt="Logo">
    <div class="flex-fill">
        <div class="fw-700"><?= count($vouchers) ?> Voucher
            <?= $batch_id ? '— Batch: <span class="font-mono">' . htmlspecialchars($batch_id) . '</span>' : '' ?>
        </div>
        <div class="text-muted" style="font-size:.75rem;">Profil: <?= htmlspecialchars($vouchers[0]['profile_name'] ?? '-') ?></div>
    </div>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Cetak
    </button>
    <a href="/index.php?page=voucher_list<?= $batch_id ? '&batch_id='.urlencode($batch_id) : '' ?>"
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <a href="/index.php?page=generate_voucher" class="btn btn-outline-primary">
        <i class="bi bi-plus me-1"></i>Generate Lagi
    </a>
</div>

<!-- Voucher Cards Grid -->
<div class="voucher-grid">
<?php foreach ($vouchers as $v):
    $duration_str = $v['duration_value'] . ' ' . match($v['duration_unit']) {
        'minutes' => 'Menit', 'hours' => 'Jam', 'days' => 'Hari', default => $v['duration_unit']
    };
    $quota_str = $v['quota_mb'] > 0 ? format_bytes($v['quota_mb'] * 1048576) : 'Unlimited';
    $rate_str  = ($v['rate_up'] && $v['rate_down']) ? $v['rate_up'].'↑ / '.$v['rate_down'].'↓' : '-';
    $price_str = $v['price'] > 0 ? format_price((float)$v['price']) : '';
    $ssid      = htmlspecialchars($v['router_name'] ?? 'WiFi Hotspot');
?>
<div class="voucher-card">
    <div class="vc-header">
        <?= APP_COMPANY ?> — Voucher Internet
    </div>
    <div class="text-center mb-1">
        <img src="/assets/img/logo.png" class="vc-logo" alt="Logo" style="height:24px;">
    </div>

    <?php if ($v['status'] === 'unused'): ?>
    <div class="vc-user"><?= htmlspecialchars($v['username']) ?></div>
    <div class="text-center mb-1" style="font-size:.65rem;color:var(--gray-500);">PASSWORD</div>
    <div class="vc-pass"><?= htmlspecialchars($v['password']) ?></div>
    <?php else: ?>
    <div class="vc-user text-muted"><?= htmlspecialchars($v['username']) ?></div>
    <div class="text-center"><span class="badge bg-danger"><?= ucfirst($v['status']) ?></span></div>
    <?php endif; ?>

    <hr style="margin:8px 0;border-color:var(--gray-200);">
    <div class="vc-meta">
        <span><strong>Profil:</strong> <?= htmlspecialchars($v['display_name'] ?: $v['profile_name']) ?></span>
        <span><strong>Masa Aktif:</strong> <?= $duration_str ?></span>
        <span><strong>Kuota:</strong> <?= $quota_str ?></span>
        <span><strong>Kecepatan:</strong> <?= $rate_str ?></span>
        <?php if ($ssid !== 'WiFi Hotspot'): ?>
        <span><strong>Router:</strong> <?= $ssid ?></span>
        <?php endif; ?>
        <?php if ($price_str): ?>
        <span style="margin-top:4px;"><strong>Harga:</strong> <span style="color:var(--red);font-weight:700;"><?= $price_str ?></span></span>
        <?php endif; ?>
    </div>

    <div class="text-center mt-2" style="font-size:.62rem;color:var(--gray-500);">
        <?= date('d/m/Y H:i', strtotime($v['created_at'])) ?>
        <?= $batch_id ? ' • ' . htmlspecialchars($batch_id) : '' ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
// Auto print if print=1 in URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('autoprint') === '1') {
    setTimeout(() => window.print(), 500);
}
</script>
</body>
</html>
<?php
// Prevent footer from being added
exit;
?>
