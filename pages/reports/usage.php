<?php
/**
 * Reports — Data Usage Report (from radacct)
 */
$page_title = 'Laporan Pemakaian Data';
$all_routers = get_all_routers();
$filter_router = (int)get('router_id');
$filter_from   = get('from', date('Y-m-01'));
$filter_to     = get('to', date('Y-m-d'));

$where  = ['DATE(ra.acctstarttime) BETWEEN ? AND ?'];
$params = [$filter_from, $filter_to];
$types  = 'ss';
if ($filter_router) {
    $r = db_fetch_one("SELECT ip_address FROM routers WHERE id=?", 'i', [$filter_router]);
    if ($r) { $where[] = 'ra.nasipaddress = ?'; $params[] = $r['ip_address']; $types .= 's'; }
}
$where_sql = 'WHERE ' . implode(' AND ', $where);

$summary = db_fetch_one(
    "SELECT COUNT(*) AS sessions, COALESCE(SUM(ra.acctsessiontime),0) AS total_secs,
            COALESCE(SUM(ra.acctoutputoctets),0) AS total_dl, COALESCE(SUM(ra.acctinputoctets),0) AS total_ul
     FROM radacct ra {$where_sql}", $types, $params
) ?? ['sessions'=>0,'total_secs'=>0,'total_dl'=>0,'total_ul'=>0];

$top_users = db_fetch_all(
    "SELECT ra.username, COUNT(*) AS sessions,
            COALESCE(SUM(ra.acctsessiontime),0) AS total_secs,
            COALESCE(SUM(ra.acctoutputoctets),0) AS dl, COALESCE(SUM(ra.acctinputoctets),0) AS ul,
            ra.nasipaddress
     FROM radacct ra {$where_sql}
     GROUP BY ra.username, ra.nasipaddress ORDER BY dl DESC LIMIT 50",
    $types, $params
);

include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Pemakaian Data</h1>
        <p class="page-subtitle">Statistik penggunaan data dari radacct</p>
    </div>
    <a href="/index.php?page=report_export&type=radacct&from=<?= $filter_from ?>&to=<?= $filter_to ?>&router_id=<?= $filter_router ?>"
       class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Export CSV</a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="report_usage">
            <div class="col-sm-auto"><label class="form-label">Dari</label><input type="date" class="form-control form-control-sm" name="from" value="<?= $filter_from ?>"></div>
            <div class="col-sm-auto"><label class="form-label">Sampai</label><input type="date" class="form-control form-control-sm" name="to" value="<?= $filter_to ?>"></div>
            <div class="col-sm-auto">
                <label class="form-label">Router</label>
                <select class="form-select form-select-sm" name="router_id">
                    <option value="">Semua Router</option>
                    <?php foreach ($all_routers as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $filter_router==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card blue h-100">
        <div class="stat-icon"><i class="bi bi-wifi"></i></div>
        <div class="stat-value"><?= number_format($summary['sessions']) ?></div>
        <div class="stat-label">Total Sesi</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="stat-card green h-100">
        <div class="stat-icon"><i class="bi bi-clock"></i></div>
        <div class="stat-value" style="font-size:1.3rem;"><?= seconds_to_human($summary['total_secs']) ?></div>
        <div class="stat-label">Total Waktu Online</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="stat-card teal h-100">
        <div class="stat-icon"><i class="bi bi-download"></i></div>
        <div class="stat-value" style="font-size:1.3rem;"><?= format_bytes($summary['total_dl']) ?></div>
        <div class="stat-label">Total Download</div>
    </div></div>
    <div class="col-6 col-md-3"><div class="stat-card orange h-100">
        <div class="stat-icon"><i class="bi bi-upload"></i></div>
        <div class="stat-value" style="font-size:1.3rem;"><?= format_bytes($summary['total_ul']) ?></div>
        <div class="stat-label">Total Upload</div>
    </div></div>
</div>

<div class="card table-card">
    <div class="table-toolbar"><span class="fw-600">Top 50 Pengguna (berdasarkan download)</span></div>
    <div class="table-responsive">
        <table class="table" id="data-table">
            <thead><tr><th>#</th><th>Username</th><th>Router</th><th>Sesi</th><th>Waktu Online</th><th>Download</th><th>Upload</th></tr></thead>
            <tbody>
            <?php if (empty($top_users)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data</td></tr>
            <?php else: ?>
            <?php foreach ($top_users as $i => $u): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td class="font-mono fw-600"><?= htmlspecialchars($u['username']) ?></td>
                <td class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($u['nasipaddress']) ?></td>
                <td><span class="badge bg-primary"><?= $u['sessions'] ?></span></td>
                <td><?= seconds_to_human($u['total_secs']) ?></td>
                <td class="text-success fw-600">↓ <?= format_bytes($u['dl']) ?></td>
                <td class="text-primary">↑ <?= format_bytes($u['ul']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../include/footer.php'; ?>
