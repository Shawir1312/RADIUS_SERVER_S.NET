<?php
/**
 * GenieACS Servers — Add / Edit
 */
$is_edit = ($page === 'genieacs_edit');
$page_title = $is_edit ? 'Edit Server GenieACS' : 'Tambah Server GenieACS';
$admin = current_admin();

if ($admin['role'] !== 'superadmin') {
    flash_set('error', 'Akses ditolak.');
    header('Location: /index.php?page=dashboard');
    exit;
}

$id = (int)get('id');
$server = [
    'id' => '',
    'name' => '',
    'url' => 'http://',
    'username' => '',
    'password' => '',
    'is_active' => 1
];

if ($is_edit) {
    $s = db_fetch_one("SELECT * FROM genie_config WHERE id = ?", 'i', [$id]);
    if (!$s) {
        flash_set('error', 'Server tidak ditemukan.');
        header('Location: /index.php?page=genieacs_servers');
        exit;
    }
    $server = $s;
}

include __DIR__ . '/../../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $page_title ?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php?page=genieacs_servers">Konfigurasi GenieACS</a></li>
            <li class="breadcrumb-item active"><?= $page_title ?></li>
        </ol></nav>
    </div>
</div>

<div class="row justify-content-center">
<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><i class="bi bi-hdd-rack"></i> <?= $page_title ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/process/save_genieacs.php">
            <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Nama Server <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" required
                           value="<?= htmlspecialchars($server['name']) ?>"
                           placeholder="Contoh: GenieACS Utama">
                </div>

                <div class="col-md-12">
                    <label class="form-label">NBI URL (API) <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" name="url" required
                           value="<?= htmlspecialchars($server['url']) ?>"
                           placeholder="Contoh: http://192.168.1.100:7557">
                    <div class="form-text">URL Northbound Interface (NBI) GenieACS, biasanya di port 7557.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username API (NBI)</label>
                    <input type="text" class="form-control" name="username"
                           value="<?= htmlspecialchars($server['username']) ?>"
                           placeholder="Opsional">
                    <div class="form-text">Biarkan kosong jika auth dimatikan di GenieACS.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Password API (NBI)</label>
                    <input type="password" class="form-control" name="password"
                           value="<?= htmlspecialchars($server['password']) ?>"
                           placeholder="<?= $is_edit ? '(Kosongkan jika tidak diubah)' : 'Opsional' ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= $server['is_active'] ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= !$server['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Server' ?>
                </button>
                <a href="/index.php?page=genieacs_servers" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../../include/footer.php'; ?>
