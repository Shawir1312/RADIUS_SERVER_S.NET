<?php
/**
 * Profiles — List & Add/Edit
 */
$is_edit    = ($page === 'profile_edit');
$is_list    = ($page === 'profile_list');
$page_title = $is_list ? 'Profil / Paket' : ($is_edit ? 'Edit Profil' : 'Tambah Profil');

if ($is_list) {
    // ── LIST ──
    $profiles = db_fetch_all(
        "SELECT p.*, r.name AS router_name,
                (SELECT COUNT(*) FROM vouchers v WHERE v.profile_id = p.id AND v.status != 'deleted') AS voucher_count
         FROM profiles p
         LEFT JOIN routers r ON p.router_id = r.id
         ORDER BY p.name ASC"
    );
    include __DIR__ . '/../../include/header.php';
    ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Profil / Paket</h1>
        <p class="page-subtitle">Kelola paket internet (mapping ke atribut RADIUS)</p>
    </div>
    <a href="/index.php?page=profile_add" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Profil
    </a>
</div>

<div class="card table-card">
    <div class="table-toolbar">
        <span class="fw-600"><?= count($profiles) ?> Profil</span>
        <input type="text" id="table-search" class="form-control form-control-sm" style="width:220px" placeholder="Cari profil...">
    </div>
    <div class="table-responsive">
        <table class="table" id="data-table">
            <thead><tr>
                <th>Nama Profil</th><th>Durasi</th><th>Kuota</th><th>Rate Limit</th>
                <th>Harga</th><th>Router</th><th>Status</th><th>Voucher</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (empty($profiles)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Belum ada profil.</td></tr>
            <?php else: ?>
            <?php foreach ($profiles as $p): ?>
            <tr>
                <td>
                    <div class="fw-600"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if ($p['description']): ?>
                    <small class="text-muted"><?= htmlspecialchars($p['description']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $p['duration_value'] . ' ' . $p['duration_unit'] ?></td>
                <td><?= $p['quota_mb'] > 0 ? format_bytes($p['quota_mb'] * 1048576) : '<span class="text-muted">Unlimited</span>' ?></td>
                <td class="font-mono">
                    <span class="text-primary">↑<?= htmlspecialchars($p['rate_up'] ?: '0') ?></span> /
                    <span class="text-success">↓<?= htmlspecialchars($p['rate_down'] ?: '0') ?></span>
                </td>
                <td><?= format_price((float)$p['price']) ?></td>
                <td><?= $p['router_id'] ? htmlspecialchars($p['router_name'] ?? '') : '<span class="text-muted">Semua Router</span>' ?></td>
                <td><?= $p['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
                <td><span class="badge bg-primary"><?= $p['voucher_count'] ?></span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/index.php?page=profile_edit&id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/index.php?page=generate_voucher&profile_id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-success btn-icon" title="Generate Voucher">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                        <a href="/index.php?page=profile_delete&id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-outline-danger btn-icon"
                           data-confirm="Hapus profil '<?= htmlspecialchars($p['name']) ?>'?"
                           title="Hapus">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
    include __DIR__ . '/../../include/footer.php';
    return;
}

// ── ADD / EDIT FORM ──────────────────────────────────────
$profile = null;
if ($is_edit) {
    $id = (int)get('id');
    $profile = db_fetch_one("SELECT * FROM profiles WHERE id = ?", 'i', [$id]);
    if (!$profile) { flash_set('error', 'Profil tidak ditemukan.'); header('Location: /index.php?page=profile_list'); exit; }
}
$all_routers = get_all_routers();
include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $page_title ?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=profile_list">Profil</a></li>
            <li class="breadcrumb-item active"><?= $page_title ?></li>
        </ol></nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><i class="bi bi-collection"></i> <?= $page_title ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/process/save_profile.php">
            <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= $profile['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Profil <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required
                           value="<?= htmlspecialchars($profile['name'] ?? '') ?>"
                           placeholder="Contoh: Paket 1 Jam">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Tampilan (pada voucher)</label>
                    <input type="text" class="form-control" name="display_name"
                           value="<?= htmlspecialchars($profile['display_name'] ?? '') ?>"
                           placeholder="Contoh: 1 JAM - 5MB">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Durasi <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="duration_value" min="1" required
                           value="<?= $profile['duration_value'] ?? 1 ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Satuan Durasi</label>
                    <select class="form-select" name="duration_unit">
                        <option value="minutes" <?= ($profile['duration_unit'] ?? '') === 'minutes' ? 'selected' : '' ?>>Menit</option>
                        <option value="hours"   <?= ($profile['duration_unit'] ?? 'hours') === 'hours' ? 'selected' : '' ?>>Jam</option>
                        <option value="days"    <?= ($profile['duration_unit'] ?? '') === 'days' ? 'selected' : '' ?>>Hari</option>
                    </select>
                    <div class="form-text">→ Atribut RADIUS: <code>Session-Timeout</code></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" class="form-control" name="price" min="0" step="500"
                           value="<?= $profile['price'] ?? 0 ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kuota Data (MB) — 0 = Unlimited</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="quota_mb" min="0"
                               value="<?= $profile['quota_mb'] ?? 0 ?>">
                        <span class="input-group-text">MB</span>
                    </div>
                    <div class="form-text">→ Atribut RADIUS: <code>Mikrotik-Total-Limit</code></div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Rate Upload</label>
                    <input type="text" class="form-control" name="rate_up"
                           value="<?= htmlspecialchars($profile['rate_up'] ?? '10M') ?>"
                           placeholder="10M">
                    <div class="form-text">Contoh: <code>512k</code>, <code>2M</code>, <code>10M</code></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rate Download</label>
                    <input type="text" class="form-control" name="rate_down"
                           value="<?= htmlspecialchars($profile['rate_down'] ?? '10M') ?>"
                           placeholder="10M">
                    <div class="form-text">→ <code>Mikrotik-Rate-Limit</code></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Berlaku untuk Router</label>
                    <select class="form-select" name="router_id">
                        <option value="">Semua Router</option>
                        <?php foreach ($all_routers as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= ($profile['router_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['ip_address']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= ($profile['is_active'] ?? 1) ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ($profile['is_active'] ?? 1) ? '' : 'selected' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($profile['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- RADIUS Preview -->
            <div class="mt-3 p-3 rounded" style="background:var(--blue-pale); border-left:3px solid var(--blue);">
                <div class="fw-600 mb-2" style="font-size:.8rem;"><i class="bi bi-code me-1"></i>Preview Atribut RADIUS yang akan dikirim:</div>
                <code style="font-size:.78rem;">
                    Session-Timeout = <em>[durasi dalam detik]</em><br>
                    Mikrotik-Rate-Limit = "<em>[upload]</em>/<em>[download]</em>"<br>
                    <?= 'Mikrotik-Total-Limit = <em>[quota_mb × 1048576 bytes]</em>' ?>
                </code>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Profil' ?>
                </button>
                <a href="/index.php?page=profile_list" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../include/footer.php'; ?>
