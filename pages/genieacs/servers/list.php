<?php
/**
 * GenieACS Servers — List
 */
$page_title = 'Konfigurasi GenieACS';
$admin = current_admin();

if ($admin['role'] !== 'superadmin') {
    flash_set('error', 'Akses ditolak.');
    header('Location: /index.php?page=dashboard');
    exit;
}

$servers = db_fetch_all("SELECT * FROM genie_config ORDER BY name ASC");

include __DIR__ . '/../../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-hdd-rack me-2 text-primary"></i>Konfigurasi GenieACS</h1>
        <p class="page-subtitle">Kelola multi-server GenieACS (Auto-Configuration Server TR-069) untuk remote dan monitoring ONT.</p>
    </div>
    <a href="/index.php?page=genieacs_add" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Server
    </a>
</div>

<div class="card table-card">
    <div class="table-toolbar flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-600"><?= count($servers) ?> Server</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nama Server</th>
                    <th>NBI URL (Port 7557)</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($servers)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada server GenieACS yang dikonfigurasi.</td></tr>
            <?php else: ?>
            <?php foreach ($servers as $s): ?>
            <tr>
                <td><div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div></td>
                <td><a href="<?= htmlspecialchars($s['url']) ?>" target="_blank" class="font-mono"><?= htmlspecialchars($s['url']) ?></a></td>
                <td><?= htmlspecialchars($s['username']) ?: '<span class="text-muted">(Tanpa Auth)</span>' ?></td>
                <td>
                    <?php if ($s['is_active']): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/index.php?page=genieacs_edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/index.php?page=genieacs_delete&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger btn-icon" data-confirm="Hapus server '<?= htmlspecialchars($s['name']) ?>'?" title="Hapus">
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

<?php include __DIR__ . '/../../../include/footer.php'; ?>
