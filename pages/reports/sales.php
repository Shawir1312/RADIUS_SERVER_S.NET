<?php
/**
 * Reports — Sales Report
 */
$page_title       = 'Laporan Penjualan';
$show_router_filter = true;
$all_routers      = get_all_routers();

$filter_router  = (int)get('router_id');
$filter_from    = get('from', date('Y-m-01'));
$filter_to      = get('to',   date('Y-m-d'));
$filter_profile = (int)get('profile_id');

$profiles = db_fetch_all("SELECT id, name FROM profiles ORDER BY name ASC");

// WHERE clause
$where  = ['DATE(sold_at) BETWEEN ? AND ?'];
$params = [$filter_from, $filter_to];
$types  = 'ss';

if ($filter_router) {
    $where[] = 'router_id = ?'; $params[] = $filter_router; $types .= 'i';
}
if ($filter_profile) {
    $where[] = 'profile_id = ?'; $params[] = $filter_profile; $types .= 'i';
}

// Restrict by access
$access = accessible_router_ids();
if ($access !== null && !empty($access)) {
    $pls = implode(',', array_fill(0, count($access), '?'));
    $where[] = "router_id IN ({$pls})";
    foreach ($access as $rid) { $params[] = $rid; $types .= 'i'; }
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Summary
$summary = db_fetch_one(
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(price),0) AS total FROM sales_log {$where_sql}",
    $types, $params
) ?? ['cnt' => 0, 'total' => 0];

// By day chart
$by_day = db_fetch_all(
    "SELECT DATE(sold_at) AS day, COUNT(*) AS cnt, SUM(price) AS revenue
     FROM sales_log {$where_sql} GROUP BY DATE(sold_at) ORDER BY day ASC",
    $types, $params
);

// By profile (Reseller)
$by_profile = db_fetch_all(
    "SELECT profile_name, COUNT(*) AS cnt, COALESCE(SUM(price),0) AS revenue
     FROM sales_log {$where_sql} 
     GROUP BY profile_name 
     ORDER BY revenue DESC",
    $types, $params
);

// Detail rows
$details = db_fetch_all(
    "SELECT sl.*, r.name AS router_name, a.username AS sold_by_name
     FROM sales_log sl
     LEFT JOIN routers r ON sl.router_id = r.id
     LEFT JOIN admins a ON sl.sold_by = a.id
     {$where_sql}
     ORDER BY sl.sold_at DESC LIMIT 500",
    $types, $params
);

include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Penjualan</h1>
        <p class="page-subtitle">Histori penjualan voucher per periode dan router</p>
    </div>
    <a href="/index.php?page=report_export&type=sales&from=<?= urlencode($filter_from) ?>&to=<?= urlencode($filter_to) ?>&router_id=<?= $filter_router ?>"
       class="btn btn-outline-primary">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="report_sales">
            <div class="col-sm-auto">
                <label class="form-label">Dari</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= $filter_from ?>">
            </div>
            <div class="col-sm-auto">
                <label class="form-label">Sampai</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= $filter_to ?>">
            </div>
            <div class="col-sm-auto">
                <label class="form-label">Router</label>
                <select class="form-select form-select-sm" name="router_id">
                    <option value="">Semua Router</option>
                    <?php foreach ($all_routers as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $filter_router==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto">
                <label class="form-label">Profil</label>
                <select class="form-select form-select-sm" name="profile_id">
                    <option value="">Semua Profil</option>
                    <?php foreach ($profiles as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filter_profile==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="/index.php?page=report_sales" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card blue h-100">
            <div class="stat-icon"><i class="bi bi-ticket-perforated"></i></div>
            <div class="stat-value"><?= number_format($summary['cnt']) ?></div>
            <div class="stat-label">Voucher Terjual</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card red h-100">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" style="font-size:1.3rem;"><?= format_price((float)$summary['total']) ?></div>
            <div class="stat-label">Total Pendapatan</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card green h-100">
            <div class="stat-icon"><i class="bi bi-calendar-range"></i></div>
            <div class="stat-value"><?= count($by_day) ?></div>
            <div class="stat-label">Hari Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card orange h-100">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <div class="stat-value" style="font-size:1.3rem;">
                <?= $summary['cnt'] > 0 ? format_price((float)$summary['total'] / $summary['cnt']) : 'Rp 0' ?>
            </div>
            <div class="stat-label">Rata-rata per Voucher</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header"><h5 class="card-title"><i class="bi bi-bar-chart"></i> Penjualan per Hari</h5></div>
    <div class="card-body"><canvas id="salesChart" height="120"></canvas></div>
</div>

<!-- Profile Breakdown & Detail -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-pie-chart"></i> Penjualan per Profil (Reseller)</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Profil / Reseller</th>
                                <th class="text-center">Terjual</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($by_profile)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data penjualan</td></tr>
                            <?php else: ?>
                            <?php foreach ($by_profile as $bp): ?>
                            <tr>
                                <td class="fw-600"><?= htmlspecialchars($bp['profile_name'] ?: 'Tanpa Profil') ?></td>
                                <td class="text-center"><span class="badge bg-secondary"><?= number_format($bp['cnt']) ?></span></td>
                                <td class="text-end text-success fw-bold"><?= format_price((float)$bp['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-7">
        <div class="card table-card h-100">
    <div class="table-toolbar">
        <span class="fw-600">Detail Transaksi</span>
        <small class="text-muted">(maks. 500 baris)</small>
    </div>
    <div class="table-responsive">
        <table class="table" id="data-table">
            <thead><tr>
                <th>Waktu</th><th>Username Voucher</th><th>Profil</th><th>Router</th><th>Harga</th><th>Oleh</th>
            </tr></thead>
            <tbody>
            <?php if (empty($details)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data</td></tr>
            <?php else: ?>
            <?php foreach ($details as $d): ?>
            <tr>
                <td style="font-size:.78rem;"><?= date('d/m/Y H:i', strtotime($d['sold_at'])) ?></td>
                <td class="font-mono fw-600"><?= htmlspecialchars($d['voucher_username']) ?></td>
                <td><?= htmlspecialchars($d['profile_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($d['router_name'] ?? 'Semua') ?></td>
                <td class="fw-600 text-red"><?= format_price((float)$d['price']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($d['sold_by_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const bd = <?= json_encode($by_day) ?>;
new Chart(document.getElementById('salesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: bd.map(d => d.day),
        datasets: [{
            label: 'Terjual',
            data: bd.map(d => parseInt(d.cnt)),
            backgroundColor: 'rgba(21,101,192,.7)',
            borderRadius: 5,
        }, {
            label: 'Pendapatan',
            type: 'line',
            data: bd.map(d => parseFloat(d.revenue)),
            borderColor: '#C62828',
            borderWidth: 2,
            tension: .3,
            yAxisID: 'y2',
            fill: false,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            y2: { beginAtZero: true, position: 'right', grid: { display: false } }
        }
    }
});
</script>

<?php include __DIR__ . '/../../include/footer.php'; ?>
