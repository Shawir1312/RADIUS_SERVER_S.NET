<?php
/**
 * S.NET V1 -> V2 Migration Backup
 * Export data selektif yang aman untuk di-restore ke aplikasi V2
 */
$page_title = 'Backup & Migrasi ke V2';
auth_require_superadmin();

// Tabel yang aman di-export ke V2 (ada di KEDUA versi)
$SAFE_TABLES = [
    'radcheck'      => 'Username & Password pelanggan',
    'radreply'      => 'Reply attribute (rate limit, dll)',
    'radgroupcheck' => 'Group check attribute',
    'radgroupreply' => 'Group reply attribute',
    'radusergroup'  => 'Mapping user ke group',
    'radacct'       => 'Riwayat sesi (upload/download)',
    'radpostauth'   => 'Riwayat autentikasi',
    'nas'           => 'Daftar NAS/Router',
    'routers'       => 'Data router MikroTik',
    'profiles'      => 'Profil paket hotspot',
    'vouchers'      => 'Voucher hotspot',
    'admins'        => 'Akun admin',
    'audit_log'     => 'Log aktivitas admin',
    'sales_log'     => 'Log penjualan',
    'penagihan'     => 'Data tagihan',
];

// Handle download export
if (isset($_POST['action']) && in_array($_POST['action'], ['export_v2', 'export_v2_gz'])) {
    @set_time_limit(600);
    @ini_set('memory_limit', '512M');

    $isGz = ($_POST['action'] === 'export_v2_gz');

    $selected = $_POST['tables'] ?? [];
    $selected = array_intersect($selected, array_keys($SAFE_TABLES));
    if (empty($selected)) {
        $msg_error = 'Pilih minimal 1 tabel untuk di-export.';
    } else {
        $filename = 'snet_v1_to_v2_' . date('Ymd_His') . ($isGz ? '.sql.gz' : '.sql');
        header('Content-Type: ' . ($isGz ? 'application/gzip' : 'application/sql; charset=UTF-8'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store');

        $tmpFile = null;
        $gzOut   = null;
        if ($isGz) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'snet_v1_gz_');
            $gzOut   = gzopen($tmpFile, 'wb6');
        }

        $writeSql = function(string $text) use ($isGz, $gzOut) {
            if ($isGz && $gzOut) {
                gzwrite($gzOut, $text);
            } else {
                echo $text;
            }
        };

        $writeSql("-- ============================================================\n");
        $writeSql("-- S.NET V1 ke V2 Migration Export\n");
        $writeSql("-- Dibuat: " . date('Y-m-d H:i:s') . "\n");
        $writeSql("-- CARA RESTORE: Login V2 -> Pengaturan -> Backup & Restore\n");
        $writeSql("-- Upload file ini di bagian 'Restore dari V1'\n");
        $writeSql("-- ============================================================\n\n");
        $writeSql("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        $db = db();
        foreach ($selected as $table) {
            if (!isset($SAFE_TABLES[$table])) continue;
            $safeTable = $db->real_escape_string($table);
            $writeSql("-- Tabel: $table (" . $SAFE_TABLES[$table] . ")\n");
            $writeSql("DELETE FROM `" . $safeTable . "`;\n");
            $result = $db->query("SELECT * FROM `" . $safeTable . "`");
            if (!$result || $result->num_rows === 0) {
                $writeSql("-- (kosong)\n\n");
                if ($result) $result->free();
                continue;
            }
            $fields = [];
            while ($fi = $result->fetch_field()) {
                $fields[] = '`' . $fi->name . '`';
            }
            $fieldStr = implode(', ', $fields);
            $rows = [];
            while ($row = $result->fetch_row()) {
                $vals = array_map(function($v) use ($db) {
                    return $v === null ? 'NULL' : "'" . $db->real_escape_string($v) . "'";
                }, $row);
                $rows[] = '(' . implode(', ', $vals) . ')';
                if (count($rows) >= 200) {
                    $writeSql("INSERT INTO `" . $safeTable . "` ($fieldStr) VALUES\n" . implode(",\n", $rows) . ";\n");
                    $rows = [];
                }
            }
            if (!empty($rows)) {
                $writeSql("INSERT INTO `" . $safeTable . "` ($fieldStr) VALUES\n" . implode(",\n", $rows) . ";\n");
            }
            $result->free();
            $writeSql("\n");
        }
        $writeSql("SET FOREIGN_KEY_CHECKS = 1;\n-- Selesai\n");

        if ($isGz && $gzOut) {
            gzclose($gzOut);
            readfile($tmpFile);
            @unlink($tmpFile);
        }
        exit;
    }
}

// Hitung baris tiap tabel
$table_counts = [];
foreach ($SAFE_TABLES as $t => $label) {
    try {
        $safeT = db()->real_escape_string($t);
        $r = db()->query("SELECT COUNT(*) AS n FROM `" . $safeT . "`");
        if ($r) {
            $row = $r->fetch_assoc();
            $table_counts[$t] = (int)($row['n'] ?? 0);
            $r->free();
        } else {
            $table_counts[$t] = -1;
        }
    } catch (Throwable $e) {
        $table_counts[$t] = -1;
    }
}

include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-box-arrow-up me-2 text-primary"></i>Backup &amp; Migrasi ke V2</h1>
        <p class="page-subtitle">Export data dari V1 ini untuk di-restore ke S.NET Manager V2</p>
    </div>
</div>

<?php if (!empty($msg_error)): ?>
<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><?= htmlspecialchars($msg_error) ?></div>
<?php endif; ?>

<div class="row g-4">
<div class="col-12">
    <div class="alert alert-info d-flex gap-3 align-items-start">
        <i class="bi bi-info-circle-fill fs-4 mt-1 flex-shrink-0"></i>
        <div><strong>Cara pakai:</strong>
        <ol class="mb-0 mt-1">
            <li>Centang tabel yang ingin di-export</li>
            <li>Klik tombol <strong>"Download .SQL.GZ (Kecil)"</strong> (hemat kuota & upload cepat)</li>
            <li>Buka V2 &rarr; <strong>Pengaturan &rarr; Backup &amp; Restore</strong></li>
            <li>Upload file tersebut di bagian <strong>"Restore dari V1"</strong></li>
        </ol></div>
    </div>
</div>

<div class="col-12 col-lg-8">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bi bi-download me-2"></i>Export Data ke V2</h5>
        <span class="badge bg-primary"><?= count($SAFE_TABLES) ?> tabel tersedia</span>
    </div>
    <div class="card-body">
    <form method="POST">
        <div class="d-flex justify-content-between mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="checkAll" checked>
                <label class="form-check-label fw-bold" for="checkAll">Pilih Semua</label>
            </div>
            <small class="text-muted">Centang tabel yang ingin di-export</small>
        </div>
        <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-dark">
                <tr><th width="40">✓</th><th>Tabel</th><th>Deskripsi</th><th class="text-end">Data</th><th>Tipe</th></tr>
            </thead>
            <tbody>
            <?php foreach ($SAFE_TABLES as $table => $label):
                $cnt = $table_counts[$table];
                $exists = $cnt >= 0;
                $isCore = in_array($table, ['radcheck','radreply','radacct','radpostauth','radusergroup','radgroupcheck','radgroupreply','nas']);
            ?>
            <tr class="<?= !$exists ? 'table-secondary text-muted' : '' ?>">
                <td><input class="form-check-input tbl-check" type="checkbox" name="tables[]" value="<?= $table ?>" id="tbl_<?= $table ?>" <?= $exists ? 'checked' : 'disabled' ?>></td>
                <td><label for="tbl_<?= $table ?>" class="font-monospace mb-0" style="font-size:.82rem;cursor:pointer;"><?= $table ?></label></td>
                <td style="font-size:.82rem;"><?= $label ?></td>
                <td class="text-end">
                    <?php if (!$exists): ?><span class="badge bg-secondary">N/A</span>
                    <?php elseif ($cnt === 0): ?><span class="badge bg-warning text-dark">Kosong</span>
                    <?php else: ?><span class="badge bg-success"><?= number_format($cnt) ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge <?= $isCore ? 'bg-primary' : 'bg-info text-dark' ?>"><?= $isCore ? 'RADIUS' : 'App' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="alert alert-warning mt-3 mb-3" style="font-size:.83rem;">
            <i class="bi bi-shield-check me-2"></i><strong>Aman:</strong> Data PPPoE Rumahan, WireGuard, WhatsApp, dan konfigurasi V2 lainnya <strong>tidak akan terhapus</strong> saat restore di V2.
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <button type="submit" name="action" value="export_v2_gz" class="btn btn-primary btn-lg flex-fill">
                <i class="bi bi-file-earmark-zip me-2"></i>Download .SQL.GZ (Kecil ~5 MB)
            </button>
            <button type="submit" name="action" value="export_v2" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-filetype-sql me-1"></i>.SQL Biasa
            </button>
        </div>
        <div class="form-text mt-2 text-muted small">
            <i class="bi bi-info-circle me-1"></i>Format <strong>.SQL.GZ</strong> mengompresi data hingga 90% lebih kecil (51 MB jadi ~5 MB) sehingga download dan upload sangat cepat.
        </div>
    </form>
    </div>
</div>
</div>

<div class="col-12 col-lg-4">
    <div class="card border-success">
        <div class="card-header bg-success text-white"><h5 class="card-title mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Panduan Restore di V2</h5></div>
        <div class="card-body" style="font-size:.85rem;">
            <?php foreach (['Download file .sql.gz atau .sql dari form ini','Buka V2 → Pengaturan → Backup & Restore','Upload file di bagian "Restore dari V1"','Selesai! Data otomatis masuk ke V2'] as $i => $step): ?>
            <div class="d-flex gap-2 mb-3">
                <div class="badge <?= $i===3?'bg-success':'bg-primary' ?> rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;font-size:.8rem;"><?= $i===3?'✓':$i+1 ?></div>
                <div><?= $step ?></div>
            </div>
            <?php endforeach; ?>
            <hr>
            <h6 class="fw-bold">✅ Aman di-restore ke V2:</h6>
            <ul class="small text-success mb-2">
                <li>Voucher &amp; pelanggan hotspot</li>
                <li>Profil &amp; paket internet</li>
                <li>Riwayat sesi &amp; pemakaian</li>
                <li>Router, NAS, akun admin</li>
            </ul>
            <h6 class="fw-bold">🔒 Tidak akan hilang di V2:</h6>
            <ul class="small text-muted mb-0">
                <li>Pelanggan PPPoE Rumahan</li>
                <li>Konfigurasi WireGuard VPN</li>
                <li>WhatsApp Gateway</li>
                <li>Data ONT/GenieACS</li>
            </ul>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h6 class="card-title mb-0"><i class="bi bi-bar-chart me-1"></i>Statistik V1</h6></div>
        <div class="card-body p-2">
            <?php $total = array_sum(array_filter($table_counts, fn($c) => $c > 0)); ?>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>Total baris</span><strong><?= number_format($total) ?></strong></div>
            <div class="d-flex justify-content-between small py-1 border-bottom"><span>Tabel tersedia</span><strong><?= count(array_filter($table_counts, fn($c) => $c >= 0)) ?></strong></div>
            <div class="d-flex justify-content-between small py-1"><span>Est. ukuran export</span><strong>~<?= number_format(max(1, intdiv($total, 500))) ?> KB</strong></div>
        </div>
    </div>
</div>
</div>

<script>
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.tbl-check:not(:disabled)').forEach(cb => cb.checked = this.checked);
});
document.querySelectorAll('.tbl-check').forEach(cb => {
    cb.addEventListener('change', function() {
        const all = document.querySelectorAll('.tbl-check:not(:disabled)');
        const checked = document.querySelectorAll('.tbl-check:not(:disabled):checked');
        document.getElementById('checkAll').checked = (all.length === checked.length);
        document.getElementById('checkAll').indeterminate = (checked.length > 0 && checked.length < all.length);
    });
});
</script>

<?php include __DIR__ . '/../../include/footer.php'; ?>
