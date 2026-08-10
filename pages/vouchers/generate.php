<?php
/**
 * Voucher — Generate Page
 * Core feature: batch generate vouchers → insert to radcheck/radreply/vouchers
 */
$page_title  = 'Generate Voucher';
$all_routers = get_all_routers();
$profiles    = db_fetch_all("SELECT * FROM profiles WHERE is_active = 1 ORDER BY name ASC");

// Pre-select profile if coming from profile list
$preselect_profile = (int)get('profile_id');

// Last batch info (for repeat print)
$last_batch = db_fetch_one(
    "SELECT v.batch_id, COUNT(*) AS qty, p.name AS profile_name, r.name AS router_name, v.created_at
     FROM vouchers v
     LEFT JOIN profiles p ON v.profile_id = p.id
     LEFT JOIN routers r ON v.router_id = r.id
     WHERE v.generated_by = ?
     GROUP BY v.batch_id ORDER BY v.created_at DESC LIMIT 1",
    'i', [current_admin()['id']]
);

include __DIR__ . '/../../include/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Generate Voucher</h1>
        <p class="page-subtitle">Buat voucher hotspot secara massal — langsung disimpan ke database RADIUS</p>
    </div>
    <a href="/index.php?page=voucher_list" class="btn btn-outline-primary">
        <i class="bi bi-list me-1"></i>Daftar Voucher
    </a>
</div>

<div class="row g-4">
    <!-- Form -->
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-plus-circle"></i> Form Generate Voucher</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/process/generate_voucher.php" id="genForm" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Voucher <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="qty" id="qty"
                                   min="1" max="<?= VOUCHER_MAX_BATCH ?>" value="10" required>
                            <div class="form-text">Maksimal <?= VOUCHER_MAX_BATCH ?> per generate</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Profil / Paket (Reseller) <span class="text-danger">*</span></label>
                            <select class="form-select" name="profile_id" id="profile_id" required>
                                <option value="">— Pilih Profil —</option>
                                <?php foreach ($profiles as $p): ?>
                                <?php 
                                    $label = htmlspecialchars($p['name']);
                                    if ($p['price'] > 0) $label .= ' — ' . format_price((float)$p['price']);
                                    
                                    // Append router name
                                    if ($p['router_id']) {
                                        $router_name = '';
                                        foreach ($all_routers as $r) {
                                            if ($r['id'] == $p['router_id']) {
                                                $router_name = $r['name'];
                                                break;
                                            }
                                        }
                                        $label .= " [Cabang: " . htmlspecialchars($router_name) . "]";
                                    } else {
                                        $label .= " [Global / Semua Router]";
                                    }
                                ?>
                                <option value="<?= $p['id'] ?>"
                                    data-duration="<?= $p['duration_value'].' '.$p['duration_unit'] ?>"
                                    data-quota="<?= $p['quota_mb'] > 0 ? format_bytes($p['quota_mb']*1048576) : 'Unlimited' ?>"
                                    data-rate="<?= htmlspecialchars($p['rate_up'].'/'.$p['rate_down']) ?>"
                                    data-price="<?= format_price((float)$p['price']) ?>"
                                    <?= $p['id'] === $preselect_profile ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Profile Info Box -->
                        <div class="col-12" id="profile-info"></div>

profileSel.addEventListener('change', updateProfileInfo);

// Preview username
function generatePreview() {
    const len  = parseInt(document.getElementById('char_length').value);
    const type = document.querySelector('[name=char_type]').value;
    const pref = document.querySelector('[name=prefix]').value;
    const chars = {
        mix: 'abcdefghjkmnpqrstuvwxyz23456789',
        mix1: 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
        mix2: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789',
        lower: 'abcdefghjkmnpqrstuvwxyz',
        upper: 'ABCDEFGHJKMNPQRSTUVWXYZ',
        num: '234567890123456789',
    }[type] || 'abcdefghjkmnpqrstuvwxyz23456789';
    let preview = pref;
    for (let i = 0; i < len; i++) preview += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('preview-user').textContent = preview;
}
document.getElementById('char_length')?.addEventListener('change', generatePreview);
document.querySelector('[name=char_type]')?.addEventListener('change', generatePreview);
document.querySelector('[name=prefix]')?.addEventListener('input', generatePreview);
generatePreview();
</script>

<?php include __DIR__ . '/../../include/footer.php'; ?>
