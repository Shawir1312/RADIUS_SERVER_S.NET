<?php
/**
 * PPPoE Customers — List
 */
$page_title = 'Pelanggan PPPoE';
$routers = get_all_routers();
$selRid = (int)get('router_id');
if (!$selRid && !empty($routers)) {
    $selRid = $routers[0]['id'];
}

$selRouter = null;
foreach ($routers as $r) {
    if ($r['id'] == $selRid) {
        $selRouter = $r;
        break;
    }
}

$search = get('q', '');
$filter_status = get('status', '');

$where_sql = "WHERE pc.router_id = ?";
$params = [$selRid];
$types = "i";

if ($search !== '') {
    $where_sql .= " AND (pc.pppoe_username LIKE ? OR pc.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($filter_status !== '') {
    $where_sql .= " AND pc.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$customers = db_fetch_all(
    "SELECT pc.*, 
            (SELECT COALESCE(SUM(amount),0) FROM pppoe_payments WHERE customer_id=pc.id AND period_year=YEAR(NOW()) AND period_month=MONTH(NOW())) as paid_this_month
     FROM pppoe_customers pc 
     $where_sql 
     ORDER BY pc.status ASC, pc.full_name ASC",
    $types, $params
);

$active_sessions = [];
$api_error = '';

if ($selRouter) {
    try {
        require_once __DIR__ . '/../../../lib/routeros_api.class.php';
        $api = new RouterosAPI();
        $api->debug = false;
        if ($api->connect($selRouter['ip_address'], $selRouter['api_user'], $selRouter['api_password'], (int)$selRouter['api_port'])) {
            $acts = $api->comm('/ppp/active/print');
            foreach ($acts as $a) {
                if (isset($a['name'])) {
                    $active_sessions[$a['name']] = $a;
                }
            }
            $api->disconnect();
        } else {
            $api_error = 'Gagal terhubung ke MikroTik untuk cek status online.';
        }
    } catch (Exception $e) {
        $api_error = $e->getMessage();
    }
}

include __DIR__ . '/../../../include/header.php';
?>
<style>
.sts-active { background:#DCFCE7; color:#15803D; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:600; }
.sts-isolated { background:#FEE2E2; color:#DC2626; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:600; }
.sts-suspended { background:#FEF3C7; color:#D97706; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:600; }
.online-dot { width:8px; height:8px; border-radius:50%; background:#22C55E; display:inline-block; box-shadow:0 0 0 3px rgba(34,197,94,.2); animation:dp 2s infinite; }
@keyframes dp { 0%{box-shadow:0 0 0 0 rgba(34,197,94,.4)} 70%{box-shadow:0 0 0 6px rgba(34,197,94,0)} 100%{box-shadow:0 0 0 0 rgba(34,197,94,0)} }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-people me-2 text-primary"></i>Pelanggan PPPoE</h1>
        <p class="page-subtitle">Kelola data pelanggan broadband (PPPoE).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?page=pppoe_add&router_id=<?= $selRid ?>" class="btn btn-primary <?= !$selRouter ? 'disabled' : '' ?>">
            <i class="bi bi-person-plus me-1"></i> Tambah Pelanggan
        </a>
    </div>
</div>

<?php if ($api_error): ?>
<div class="alert alert-warning py-2 mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($api_error) ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="table-toolbar flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-600"><?= count($customers) ?> Pelanggan</span>
            
            <form method="GET" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="page" value="pppoe_customers">
                <select name="router_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
                    <?php if (empty($routers)): ?>
                        <option value="">Belum ada router</option>
                    <?php endif; ?>
                    <?php foreach ($routers as $rt): ?>
                    <option value="<?= $rt['id'] ?>" <?= $selRid == $rt['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rt['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="isolated" <?= $filter_status === 'isolated' ? 'selected' : '' ?>>Isolir</option>
                </select>
            </form>
        </div>
        
        <form method="GET" class="d-flex m-0">
            <input type="hidden" name="page" value="pppoe_customers">
            <input type="hidden" name="router_id" value="<?= $selRid ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
            <div class="input-group input-group-sm" style="width:220px">
                <input type="text" name="q" class="form-control" placeholder="Cari nama/username..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Username PPPoE</th>
                    <th>Nama Pelanggan</th>
                    <th>Profil</th>
                    <th>Jatuh Tempo</th>
                    <th>Harga / Bln</th>
                    <th>Sesi Online</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pelanggan PPPoE di router ini.</td></tr>
            <?php else: ?>
            <?php foreach ($customers as $c): 
                $is_online = isset($active_sessions[$c['pppoe_username']]);
                $today = (int)date('j');
                $is_late = ($c['status'] === 'active' && $c['due_day'] <= $today && !$c['paid_this_month']);
            ?>
            <tr <?= $c['status'] === 'isolated' ? 'style="background-color: var(--red-pale);"' : '' ?>>
                <td>
                    <?php if ($c['status'] === 'active' && $is_online): ?>
                        <span class="sts-active">🟢 Online</span>
                    <?php elseif ($c['status'] === 'active' && $is_late): ?>
                        <span class="sts-suspended">⚠️ Jatuh Tempo</span>
                    <?php elseif ($c['status'] === 'active'): ?>
                        <span class="sts-active">✅ Aktif</span>
                    <?php else: ?>
                        <span class="sts-isolated">🔴 Isolir</span>
                    <?php endif; ?>
                </td>
                <td><strong class="font-mono text-primary"><?= htmlspecialchars($c['pppoe_username']) ?></strong></td>
                <td>
                    <div class="fw-bold"><?= htmlspecialchars($c['full_name']) ?></div>
                    <?php if ($c['phone']): ?>
                    <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($c['phone']) ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($c['profile'] ?: '-') ?></span></td>
                <td>
                    <strong>Tgl <?= $c['due_day'] ?></strong>
                    <?php if ($is_late): ?>
                        <br><small class="text-danger fw-bold">Belum Bayar</small>
                    <?php endif; ?>
                </td>
                <td><?= format_price((float)$c['monthly_price']) ?></td>
                <td>
                    <?php if ($is_online): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="online-dot"></span>
                            <span class="font-mono" style="font-size:12px"><?= htmlspecialchars($active_sessions[$c['pppoe_username']]['address'] ?? '') ?></span>
                        </div>
                        <div style="font-size:11px; color:var(--bs-success); margin-left:14px; margin-top:2px;">
                            Up: <?= htmlspecialchars($active_sessions[$c['pppoe_username']]['uptime'] ?? '') ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:12px">Offline</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <!-- Nanti ditambahkan route bayar dan isolir dll -->
                        <a href="/index.php?page=pppoe_edit&router_id=<?= $selRid ?>&id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/index.php?page=pppoe_delete&router_id=<?= $selRid ?>&id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-danger btn-icon"
                           data-confirm="Hapus pelanggan '<?= htmlspecialchars($c['full_name']) ?>' secara permanen dari Database dan MikroTik?"
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

<?php include __DIR__ . '/../../../include/footer.php'; ?>
