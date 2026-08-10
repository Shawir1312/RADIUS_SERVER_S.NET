<?php
/**
 * Dashboard — Summary of all routers & voucher statistics
 */
$page_title       = 'Dashboard';
$show_router_filter = false;
$all_routers      = get_all_routers();

// ── Stats ────────────────────────────────────────────────
$total_routers   = count($all_routers);
$total_vouchers  = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM vouchers WHERE status != 'deleted'")['n'] ?? 0);
$unused_vouchers = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM vouchers WHERE status = 'unused'")['n'] ?? 0);
$active_vouchers = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM vouchers WHERE status = 'active'")['n'] ?? 0);
$expired_vouchers= (int)(db_fetch_one("SELECT COUNT(*) AS n FROM vouchers WHERE status = 'expired'")['n'] ?? 0);
$total_profiles  = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM profiles WHERE is_active = 1")['n'] ?? 0);

// Active sessions from radacct
$active_sessions = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM radacct WHERE acctstoptime IS NULL")['n'] ?? 0);

// Today's sales
$today_sales = db_fetch_one(
    "SELECT COUNT(*) AS cnt, COALESCE(SUM(price),0) AS total FROM sales_log WHERE DATE(sold_at) = CURDATE()"
) ?? ['cnt' => 0, 'total' => 0];

// Recent voucher batches
$recent_batches = db_fetch_all(
    "SELECT v.batch_id, v.created_at, v.router_id, r.name AS router_name, p.name AS profile_name,
            COUNT(*) AS qty, a.username AS generated_by
     FROM vouchers v
     LEFT JOIN routers r ON v.router_id = r.id
     LEFT JOIN profiles p ON v.profile_id = p.id
     LEFT JOIN admins a ON v.generated_by = a.id
     WHERE v.batch_id IS NOT NULL
     GROUP BY v.batch_id
     ORDER BY v.created_at DESC LIMIT 8"
);

// Sales chart — last 7 days
$chart_data = db_fetch_all(
    "SELECT DATE(sold_at) AS day, COUNT(*) AS cnt, COALESCE(SUM(price),0) AS revenue
     FROM sales_log WHERE sold_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(sold_at) ORDER BY day ASC"
);

include __DIR__ . '/../include/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Ringkasan status semua router & voucher</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?page=generate_voucher" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Generate Voucher
        </a>
    </div>
</div>

<!-- Stat Cards Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card blue h-100">
            <div class="stat-icon"><i class="bi bi-router"></i></div>
            <div class="stat-value"><?= $total_routers ?></div>
            <div class="stat-label">Total Router</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card green h-100">
            <div class="stat-icon"><i class="bi bi-wifi"></i></div>
            <div class="stat-value"><?= $active_sessions ?></div>
            <div class="stat-label">User Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card teal h-100">
            <div class="stat-icon"><i class="bi bi-ticket-perforated"></i></div>
            <div class="stat-value"><?= $unused_vouchers ?></div>
            <div class="stat-label">Voucher Tersedia</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card orange h-100">
            <div class="stat-icon"><i class="bi bi-ticket-detailed"></i></div>
            <div class="stat-value"><?= $active_vouchers ?></div>
            <div class="stat-label">Voucher Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card red h-100">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-value"><?= $expired_vouchers ?></div>
            <div class="stat-label">Kadaluarsa</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card purple h-100">
            <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-value"><?= $today_sales['cnt'] ?></div>
            <div class="stat-label">Terjual Hari Ini</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Router Status Cards -->
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-router"></i> Status Router</h5>
                <a href="/index.php?page=router_list" class="btn btn-sm btn-outline-primary">Kelola Router</a>
            </div>
            <div class="card-body">
                <?php if (empty($all_routers)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-router display-4 d-block mb-2"></i>
                    <p>Belum ada router terdaftar.</p>
                    <a href="/index.php?page=router_add" class="btn btn-primary btn-sm">+ Tambah Router</a>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($all_routers as $router): ?>
                    <div class="col-sm-6 col-md-4">
                        <div class="router-card" data-router-id="<?= $router['id'] ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="router-name"><?= htmlspecialchars($router['name']) ?></div>
                                    <div class="router-ip"><?= htmlspecialchars($router['ip_address']) ?></div>
                                </div>
                                <span class="router-status-badge badge bg-secondary">
                                    <span class="status-dot"></span>Cek...
                                </span>
                            </div>
                            <?php if ($router['location']): ?>
                            <div class="text-muted" style="font-size:.72rem;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($router['location']) ?></div>
                            <?php endif; ?>
                            <div class="router-users mt-2">
                                <i class="bi bi-wifi me-1"></i>
                                <span class="router-users-count">-</span> user aktif
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Revenue -->
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-cash-stack"></i> Penjualan Hari Ini</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                <div class="mb-2" style="font-size:2.5rem;font-weight:800;color:var(--blue);">
                    <?= $today_sales['cnt'] ?>
                </div>
                <div class="text-muted mb-3">voucher terjual</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--red);">
                    <?= format_price((float)$today_sales['total']) ?>
                </div>
                <div class="text-muted">total pendapatan</div>
                <hr class="w-100 my-3">
                <a href="/index.php?page=report_sales" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-bar-chart me-1"></i>Lihat Laporan Lengkap
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sales Chart -->
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-graph-up"></i> Penjualan 7 Hari Terakhir</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Batches -->
    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-clock-history"></i> Generate Terakhir</h5>
                <a href="/index.php?page=voucher_list" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Batch</th><th>Profil</th><th>Qty</th><th>Router</th><th>Waktu</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($recent_batches)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php else: ?>
                        <?php foreach ($recent_batches as $b): ?>
                        <tr>
                            <td>
                                <a href="/index.php?page=voucher_list&batch_id=<?= urlencode($b['batch_id']) ?>"
                                   class="font-mono fw-600 text-blue" style="font-size:.73rem;">
                                    <?= htmlspecialchars($b['batch_id']) ?>
                                </a>
                            </td>
                            <td style="font-size:.78rem;"><?= htmlspecialchars($b['profile_name'] ?? '-') ?></td>
                            <td><span class="badge bg-primary"><?= $b['qty'] ?></span></td>
                            <td style="font-size:.75rem;"><?= htmlspecialchars($b['router_name'] ?? 'Semua') ?></td>
                            <td style="font-size:.72rem;color:var(--gray-500);">
                                <?= date('d/m H:i', strtotime($b['created_at'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Sales chart
const chartData = <?= json_encode($chart_data) ?>;
const labels    = chartData.map(d => d.day);
const cnts      = chartData.map(d => parseInt(d.cnt));
const revs      = chartData.map(d => parseFloat(d.revenue));

const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Voucher Terjual',
                data: cnts,
                backgroundColor: 'rgba(21,101,192,0.7)',
                borderColor: '#1565C0',
                borderWidth: 1,
                borderRadius: 6,
                yAxisID: 'y',
            },
            {
                label: 'Pendapatan (Rp)',
                data: revs,
                type: 'line',
                borderColor: '#C62828',
                backgroundColor: 'rgba(198,40,40,0.08)',
                borderWidth: 2,
                pointBackgroundColor: '#C62828',
                tension: 0.3,
                fill: true,
                yAxisID: 'y2',
            }
        ],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { family: 'Inter', size: 12 } } } },
        scales: {
            y:  { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#eee' } },
            y2: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { font: { size: 11 } } },
        },
    },
});
</script>

<?php include __DIR__ . '/../include/footer.php'; ?>
