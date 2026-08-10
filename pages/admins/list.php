<?php
/**
 * Admin Management Page (List + Add + Edit)
 */
auth_require_superadmin();

$is_add  = ($page === 'admin_add');
$is_edit = ($page === 'admin_edit');
$is_list = ($page === 'admin_list');
$page_title = $is_list ? 'Kelola Admin' : ($is_edit ? 'Edit Admin' : 'Tambah Admin');

if ($is_list) {
    $admins = db_fetch_all("SELECT id, username, full_name, role, is_active, last_login, created_at FROM admins ORDER BY created_at DESC");
    include __DIR__ . '/../../include/header.php';
    ?>
<div class="page-header">
    <div><h1 class="page-title">Kelola Admin</h1><p class="page-subtitle">Manajemen akun administrator</p></div>
    <a href="/index.php?page=admin_add" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Tambah Admin</a>
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table" id="data-table">
            <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($admins)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data</td></tr>
            <?php else: ?>
            <?php foreach ($admins as $a): ?>
            <tr>
                <td class="fw-600"><?= htmlspecialchars($a['username']) ?></td>
                <td><?= htmlspecialchars($a['full_name'] ?? '-') ?></td>
                <td><?= $a['role']==='superadmin' ? "<span class='badge bg-danger'>Super Admin</span>" : "<span class='badge bg-primary'>Operator</span>" ?></td>
                <td><?= $a['is_active'] ? "<span class='badge bg-success'>Aktif</span>" : "<span class='badge bg-secondary'>Nonaktif</span>" ?></td>
                <td style="font-size:.78rem;"><?= $a['last_login'] ? date('d/m/Y H:i', strtotime($a['last_login'])) : '-' ?></td>
                <td>
                    <a href="/index.php?page=admin_edit&id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="bi bi-pencil"></i></a>
                    <?php if ($a['id'] != current_admin()['id']): ?>
                    <a href="/process/save_admin.php?action=delete&id=<?= $a['id'] ?>&csrf=<?= urlencode($_SESSION['csrf_token']) ?>"
                       class="btn btn-sm btn-outline-danger btn-icon"
                       data-confirm="Hapus admin '<?= htmlspecialchars($a['username']) ?>'?">
                        <i class="bi bi-trash"></i>
                    </a>
                    <?php endif; ?>
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

// Add/Edit Form
$admin_data = null;
if ($is_edit) {
    $id = (int)get('id');
    $admin_data = db_fetch_one("SELECT id, username, full_name, role, router_access, is_active FROM admins WHERE id=?", 'i', [$id]);
    if (!$admin_data) { flash_set('error', 'Admin tidak ditemukan.'); header('Location: /index.php?page=admin_list'); exit; }
}
$all_routers = get_all_routers();
include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title"><?= $page_title ?></h1>
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/index.php?page=admin_list">Admin</a></li>
        <li class="breadcrumb-item active"><?= $page_title ?></li>
    </ol></nav></div>
</div>
<div class="row justify-content-center"><div class="col-12 col-lg-7">
<div class="card">
    <div class="card-header"><h5 class="card-title"><i class="bi bi-person-gear"></i> <?= $page_title ?></h5></div>
    <div class="card-body">
        <form method="POST" action="/process/save_admin.php">
            <?php if ($is_edit): ?><input type="hidden" name="id" value="<?= $admin_data['id'] ?>"><?php endif; ?>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" required
                           value="<?= htmlspecialchars($admin_data['username'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" name="full_name"
                           value="<?= htmlspecialchars($admin_data['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <?= $is_edit ? '(kosongkan jika tidak diubah)' : '<span class="text-danger">*</span>' ?></label>
                    <input type="password" class="form-control" name="password"
                           <?= $is_edit ? '' : 'required' ?> minlength="8" placeholder="Min. 8 karakter">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                        <option value="operator" <?= ($admin_data['role'] ?? 'operator')==='operator' ? 'selected':'' ?>>Operator</option>
                        <option value="superadmin" <?= ($admin_data['role'] ?? '')==='superadmin' ? 'selected':'' ?>>Super Admin</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Akses Router (kosongkan = semua router)</label>
                    <div class="row g-2">
                        <?php
                        $cur_access = $admin_data ? json_decode($admin_data['router_access'] ?? '[]', true) : [];
                        foreach ($all_routers as $r):
                        ?>
                        <div class="col-sm-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="router_access[]"
                                       value="<?= $r['id'] ?>" id="ra_<?= $r['id'] ?>"
                                       <?= in_array($r['id'], (array)$cur_access) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ra_<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['name']) ?>
                                    <span class="text-muted font-mono" style="font-size:.72rem;"><?= htmlspecialchars($r['ip_address']) ?></span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text">Centang router yang bisa diakses. Jika semua dicentang atau tidak ada yang dicentang, admin bisa akses semua router.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= ($admin_data['is_active'] ?? 1) ? 'selected':'' ?>>Aktif</option>
                        <option value="0" <?= ($admin_data['is_active'] ?? 1) ? '':'selected' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Admin' ?></button>
                <a href="/index.php?page=admin_list" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<?php include __DIR__ . '/../../include/footer.php'; ?>
