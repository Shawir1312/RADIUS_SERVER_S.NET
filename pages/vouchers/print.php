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
                p.validity_value, p.validity_unit,
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
                p.validity_value, p.validity_unit,
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
        body { background: #f5f5f5; padding: 20px; font-family: Arial, sans-serif; }
        
        /* Layar monitor: flexbox agar rapi */
        .voucher-grid { display: grid; grid-template-columns: repeat(auto-fill, 27mm); gap: 1mm; justify-content: center; background: white; padding: 5mm; max-width: 210mm; margin: 0 auto; }
        
        .voucher-card { 
            border: 1px dashed #000; 
            padding: 2px; 
            width: 28mm; 
            /* height: 31mm; dihapus agar fit content */
            box-sizing: border-box; 
            display: flex; 
            flex-direction: column; 
            background: #fff; 
            overflow: hidden; 
            position: relative;
            page-break-inside: avoid;
        }

        @media print {
            @page { margin: 5mm; size: A4 portrait; }
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            
            /* Saat print, paksa grid jadi 7 kolom */
            .voucher-grid { 
                padding: 0; 
                margin: 0; 
                max-width: none; 
                display: grid !important; 
                grid-template-columns: repeat(7, 28mm) !important; 
                /* grid-auto-rows dihapus agar tidak memaksa tinggi ke bawah */
                gap: 1mm !important; 
                justify-content: start !important;
            }
            .voucher-card {
                width: 100% !important;
                /* height: 100% !important; dihapus */
                margin: 0 !important;
            }
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
<?php foreach ($vouchers as $index => $v):
    $dur_unit_id = match($v['duration_unit']) { 'minutes'=>'Mnt', 'hours'=>'Jam', 'days'=>'Hari', default=>$v['duration_unit'] };
    $val_unit_id = match($v['validity_unit'] ?? '') { 'minutes'=>'Mnt', 'hours'=>'Jam', 'days'=>'Hari', default=>($v['validity_unit'] ?? '') };
    
    $duration_str = $v['duration_value'] == 0 ? 'Unlimited' : $v['duration_value'] . ' ' . $dur_unit_id;
    $validity_str = ($v['validity_value'] ?? 0) == 0 ? 'Unlimited' : ($v['validity_value'] ?? 0) . ' ' . $val_unit_id;
    
    $price_str = $v['price'] > 0 ? format_price((float)$v['price']) : 'Gratis';
?>
<div class="voucher-card">
    
    <!-- Atas: Reseller (Kiri), Logo (Tengah), No (Kanan) -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #000; padding-bottom: 1px; margin-bottom: 1px; height: 10px;">
        <div style="font-size: 3.5pt; font-weight: 800; width: 35%; line-height: 1; word-wrap: break-word; overflow: hidden; max-height: 10px;">
            <?= htmlspecialchars($v['display_name'] ?: $v['profile_name']) ?>
        </div>
        <div style="width: 30%; text-align: center;">
            <img src="/assets/img/logo.png" style="max-height: 9px; max-width: 100%; display: inline-block;" alt="Logo">
        </div>
        <div style="font-size: 4pt; font-family: monospace; font-weight: bold; line-height: 1; width: 35%; text-align: right;">
            No: <?= $index + 1 ?>
        </div>
    </div>
    
    <!-- Username / Password Area -->
    <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1px 0;">
        <?php if ($v['username'] === $v['password']): ?>
            <div style="font-size: 4pt; color: #333; margin-bottom: 1px;">KODE VOUCHER</div>
            <div style="font-weight: 900; font-size: 9.5pt; letter-spacing: 0.5px;"><?= htmlspecialchars($v['username']) ?></div>
        <?php else: ?>
            <div style="font-size: 4pt; color: #333;">USER</div>
            <div style="font-weight: 900; font-size: 8pt; letter-spacing: 0.5px;"><?= htmlspecialchars($v['username']) ?></div>
            <div style="font-size: 4pt; color: #333; margin-top: 1px;">PASS</div>
            <div style="font-weight: 900; font-size: 8pt; letter-spacing: 0.5px;"><?= htmlspecialchars($v['password']) ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Meta Data Area -->
    <div style="font-size: 4.5pt; border-top: 1px dashed #000; padding-top: 1px; line-height: 1.1;">
        <div style="display: flex; justify-content: space-between;">
            <span>Masa Aktif:</span><span style="font-weight: bold;"><?= $validity_str ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Durasi:</span><span style="font-weight: bold;"><?= $duration_str ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 1px;">
            <span style="font-weight: bold; font-size: 5.5pt;">Harga:</span><span style="font-weight: 900; font-size: 6pt; color: #000;"><?= $price_str ?></span>
        </div>
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
