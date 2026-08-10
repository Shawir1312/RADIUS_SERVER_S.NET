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

                        <div class="col-md-6">
                            <label class="form-label">Mode Voucher <span class="text-danger">*</span></label>
                            <select class="form-select" name="user_mode" id="user_mode" required>
                                <option value="vc">Username = Password (voucher card)</option>
                                <option value="up">Username ≠ Password (user/pass terpisah)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Panjang Karakter <span class="text-danger">*</span></label>
                            <select class="form-select" name="char_length" id="char_length">
                                <?php foreach ([4,5,6,7,8,10,12] as $l): ?>
                                <option value="<?= $l ?>" <?= $l === 8 ? 'selected' : '' ?>><?= $l ?> karakter</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tipe Karakter</label>
                            <select class="form-select" name="char_type">
                                <option value="mix">Huruf kecil + angka (abc123)</option>
                                <option value="mix1">Huruf besar + angka (ABC123)</option>
                                <option value="mix2">Huruf campur + angka (aBc123)</option>
                                <option value="lower">Huruf kecil (abcdef)</option>
                                <option value="upper">Huruf besar (ABCDEF)</option>
                                <option value="num">Angka saja (123456)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prefix (opsional)</label>
                            <input type="text" class="form-control" name="prefix"
                                   maxlength="8" placeholder="Contoh: HTS">
                            <div class="form-text">Ditambahkan di depan username</div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="mt-3 p-3 rounded bg-light border">
                        <div class="fw-600 mb-1" style="font-size:.8rem;">Preview username yang akan dibuat:</div>
                        <code id="preview-user" class="text-blue" style="font-size:.88rem;">—</code>
                        <span class="text-muted ms-2" style="font-size:.75rem;">(contoh acak)</span>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="genBtn" onclick="showLoader()">
                            <i class="bi bi-magic me-1"></i> Generate <?= '<span id="qty-label">10</span>' ?> Voucher
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-12 col-lg-5">
        <!-- Last Batch -->
        <?php if ($last_batch): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-clock-history"></i> Generate Terakhir Anda</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tr><th>Batch ID</th><td class="font-mono fw-600"><?= htmlspecialchars($last_batch['batch_id']) ?></td></tr>
                    <tr><th>Profil</th><td><?= htmlspecialchars($last_batch['profile_name']) ?></td></tr>
                    <tr><th>Router</th><td><?= htmlspecialchars($last_batch['router_name'] ?? 'Semua Router') ?></td></tr>
                    <tr><th>Jumlah</th><td><span class="badge bg-primary"><?= $last_batch['qty'] ?></span></td></tr>
                    <tr><th>Waktu</th><td><?= date('d M Y H:i', strtotime($last_batch['created_at'])) ?></td></tr>
                </table>
                <div class="d-flex gap-2">
                    <a href="/index.php?page=voucher_print&batch_id=<?= urlencode($last_batch['batch_id']) ?>"
                       class="btn btn-primary btn-sm" target="_blank">
                        <i class="bi bi-printer me-1"></i>Cetak Batch
                    </a>
                    <a href="/index.php?page=voucher_list&batch_id=<?= urlencode($last_batch['batch_id']) ?>"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list me-1"></i>Lihat Voucher
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tips -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-lightbulb"></i> Cara Kerja</h5>
            </div>
            <div class="card-body" style="font-size:.82rem;">
                <ol class="mb-0">
                    <li class="mb-2">Voucher di-generate secara lokal — <strong>tidak perlu koneksi ke router</strong></li>
                    <li class="mb-2">Username & password disimpan ke <code>radcheck</code> (autentikasi) dan <code>radreply</code> (atribut: rate limit, timeout, quota)</li>
                    <li class="mb-2">Saat pengguna konek ke hotspot MikroTik, router meneruskan autentikasi ke FreeRADIUS</li>
                    <li class="mb-2">FreeRADIUS mengecek tabel <code>radcheck</code> lalu balasan dari <code>radreply</code></li>
                    <li>Session dicatat di <code>radacct</code> untuk monitoring & laporan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
// Update qty label
const qtyInput = document.getElementById('qty');
const qtyLabel = document.getElementById('qty-label');
if (qtyInput && qtyLabel) {
    qtyInput.addEventListener('input', () => qtyLabel.textContent = qtyInput.value);
}

// Profile info box
const profileSel  = document.getElementById('profile_id');
const profileInfo = document.getElementById('profile-info');
if (profileSel && profileInfo) {
    function updateProfileInfo() {
        const opt = profileSel.options[profileSel.selectedIndex];
        if (!opt || !opt.value) { profileInfo.innerHTML = ''; return; }
        profileInfo.innerHTML = `
            <div class="p-3 rounded" style="background:var(--blue-pale);border-left:3px solid var(--blue);">
                <div class="row g-2" style="font-size:.8rem;">
                    <div class="col-3"><strong>Durasi</strong><br>${opt.dataset.duration || '-'}</div>
                    <div class="col-3"><strong>Kuota</strong><br>${opt.dataset.quota || '-'}</div>
                    <div class="col-3"><strong>Rate Limit</strong><br><code>${opt.dataset.rate || '-'}</code></div>
                    <div class="col-3"><strong>Harga</strong><br><span class="fw-600 text-red">${opt.dataset.price || '-'}</span></div>
                </div>
            </div>`;
    }
    profileSel.addEventListener('change', updateProfileInfo);
    updateProfileInfo();
}

// Preview username
const previewEl = document.getElementById('preview-user');
if (previewEl) {
    function generatePreview() {
        const lenEl  = document.getElementById('char_length');
        const typeEl = document.querySelector('[name=char_type]');
        const prefEl = document.querySelector('[name=prefix]');
        if (!lenEl || !typeEl || !prefEl) return;
        const len  = parseInt(lenEl.value) || 6;
        const type = typeEl.value;
        const pref = prefEl.value;
        const chars = {
            mix:   'abcdefghjkmnpqrstuvwxyz23456789',
            mix1:  'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
            mix2:  'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789',
            lower: 'abcdefghjkmnpqrstuvwxyz',
            upper: 'ABCDEFGHJKMNPQRSTUVWXYZ',
            num:   '234567890123456789',
        }[type] || 'abcdefghjkmnpqrstuvwxyz23456789';
        let preview = pref;
        for (let i = 0; i < len; i++) preview += chars[Math.floor(Math.random() * chars.length)];
        previewEl.textContent = preview;
    }
    document.getElementById('char_length')?.addEventListener('change', generatePreview);
    document.querySelector('[name=char_type]')?.addEventListener('change', generatePreview);
    document.querySelector('[name=prefix]')?.addEventListener('input', generatePreview);
    generatePreview();
}
})();
</script>

<?php include __DIR__ . '/../../include/footer.php'; ?>
