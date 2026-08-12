<?php
/**
 * Settings — Audit Log & Backup
 */
$is_audit  = ($page === 'audit_log');
$is_backup = ($page === 'backup');
$page_title = $is_audit ? 'Audit Log' : 'Backup / Restore';

auth_require_superadmin();

if ($is_audit) {
    $logs = db_fetch_all(
        "SELECT al.*, r.name AS router_name FROM audit_log al
         LEFT JOIN routers r ON al.router_id = r.id
         ORDER BY al.created_at DESC LIMIT 500"
    );
    include __DIR__ . '/../../include/header.php';
    ?>
<div class="page-header">
    <div><h1 class="page-title">Audit Log</h1><p class="page-subtitle">Aktivitas admin selama 500 entri terakhir</p></div>
</div>
<div class="card table-card">
    <div class="table-responsive">
        <table class="table" id="data-table">
            <thead><tr><th>Waktu</th><th>Admin</th><th>Aksi</th><th>Target</th><th>Router</th><th>IP</th><th>Detail</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada log</td></tr>
            <?php else: ?>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td style="font-size:.75rem;"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                <td class="fw-600"><?= htmlspecialchars($l['admin_name'] ?? '-') ?></td>
                <td><span class="badge bg-primary"><?= htmlspecialchars($l['action']) ?></span></td>
                <td class="font-mono" style="font-size:.78rem;"><?= htmlspecialchars($l['target'] ?? '-') ?></td>
                <td style="font-size:.75rem;"><?= htmlspecialchars($l['router_name'] ?? '-') ?></td>
                <td class="font-mono text-muted" style="font-size:.72rem;"><?= htmlspecialchars($l['ip_address'] ?? '-') ?></td>
                <td style="font-size:.72rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($l['detail'] ?? '-') ?></td>
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

// BACKUP PAGE
include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Backup / Restore</h1><p class="page-subtitle">Export dan import data aplikasi</p></div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-cloud-download"></i> Backup Data</h5></div>
            <div class="card-body">
                <p class="text-muted">Export tabel aplikasi ke format CSV (tidak termasuk radacct karena terlalu besar).</p>
                <div class="d-flex flex-column gap-2">
                    <a href="/index.php?page=report_export&type=vouchers" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i>Export Vouchers (CSV)
                    </a>
                    <a href="/index.php?page=report_export&type=sales" class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i>Export Sales Log (CSV)
                    </a>
                    <a href="/process/do_backup.php?csrf=<?= urlencode($_SESSION['csrf_token']) ?>" class="btn btn-primary">
                        <i class="bi bi-database-down me-2"></i>Export Full SQL Dump
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-cloud-upload"></i> Restore Data</h5></div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Perhatian:</strong> Restore akan menimpa data yang ada. Pastikan Anda memiliki backup sebelum melanjutkan.
                </div>
                <p class="text-muted">Upload file SQL yang dihasilkan oleh fitur backup di atas.</p>
                <form method="POST" action="/process/do_backup.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="restore">
                    <div class="mb-3">
                        <input type="file" class="form-control" name="sql_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-danger"
                            data-confirm="Restore akan menimpa data. Anda yakin?">
                        <i class="bi bi-cloud-upload me-2"></i>Restore dari File SQL
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-arrow-repeat"></i> Sinkronisasi RADIUS</h5></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Gunakan fitur ini <strong>HANYA</strong> jika Anda baru saja melakukan migrasi dari aplikasi versi lama atau saat voucher di dashboard tidak bisa digunakan untuk login.
                </div>
                <p class="text-muted">Fitur ini akan menghapus semua data password di mesin RADIUS dan membangunnya ulang dari nol berdasarkan daftar voucher yang ada di dashboard aplikasi ini.</p>
                <form method="POST" action="/process/sync_radius.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="btn btn-warning" data-confirm="Proses ini akan menimpa ulang semua data login di mesin RADIUS. Lanjutkan?">
                        <i class="bi bi-arrow-repeat me-2"></i>Mulai Sinkronisasi Ulang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../include/footer.php'; ?>
