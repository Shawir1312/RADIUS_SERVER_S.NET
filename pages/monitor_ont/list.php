<?php
/**
 * Monitor ONT — List
 */
$page_title = 'Monitor ONT (GenieACS)';
require_once __DIR__ . '/../../include/GenieACS.php';

// Ambil semua server GenieACS aktif
$servers = db_fetch_all("SELECT * FROM genie_config WHERE is_active = 1 ORDER BY name ASC");

$selServerId = (int)get('server_id');
if (!$selServerId && !empty($servers)) {
    $selServerId = $servers[0]['id'];
}

$selServer = null;
foreach ($servers as $s) {
    if ($s['id'] == $selServerId) {
        $selServer = $s;
        break;
    }
}

$devices = [];
$api_error = '';

if ($selServer) {
    try {
        $api = new GenieACS($selServer['url'], $selServer['username'], $selServer['password']);
        
        // Ambil semua device
        // Untuk query pencarian, kita bisa tambahkan filter nantinya.
        $raw_devices = $api->getDevices();
        
        if ($api->error) {
            throw new Exception($api->error);
        }
        
        foreach ($raw_devices as $dev) {
            $info = $api->getInfo($dev);
            $opt = $api->getOptical($dev);
            
            // Hitung status online/offline
            // LastInform time biasanya di properti '_lastInform' (format ISO datetime)
            $last_inform = strtotime($dev['_lastInform'] ?? '0');
            // Jika inform terakhir kurang dari 15 menit (900 detik), anggap online (tergantung interval CPE)
            // Atau cukup lihat apakah ada flag offline (biasanya GenieACS tidak menghapus device mati)
            $is_online = (time() - $last_inform) < 1800; // 30 mins margin
            
            $devices[] = [
                '_id' => $dev['_id'],
                'sn' => $info['sn'] ?: ($dev['InternetGatewayDevice']['DeviceInfo']['SerialNumber']['_value'] ?? $dev['_id']),
                'brand' => $api->detectBrandName($dev),
                'ip' => $info['ip'],
                'mac' => $info['mac'],
                'rx' => $opt['rx'] ?? '-',
                'tx' => $opt['tx'] ?? '-',
                'last_inform' => $dev['_lastInform'] ?? null,
                'is_online' => $is_online,
                'raw' => $dev
            ];
        }
    } catch (Exception $e) {
        $api_error = 'Koneksi ke GenieACS gagal: ' . $e->getMessage();
    }
}

// Ambil pelanggan PPPoE untuk pemetaan SN ke nama pelanggan
$customers = db_fetch_all("SELECT pppoe_username, full_name, notes FROM pppoe_customers");
$cust_map = [];
foreach ($customers as $c) {
    // Karena kita belum ada kolom SN, asumsikan teknisi menulis SN di kolom notes atau kita cari kecocokan lain
    // Idealnya di database S.NET kita punya kolom 'ont_sn'.
    // Sementara ini kita sediakan array untuk matching. Nanti kita update databasenya.
}

include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-modem me-2 text-primary"></i>Monitor ONT</h1>
        <p class="page-subtitle">Pantau status perangkat ONT pelanggan dan redaman optik secara real-time dari GenieACS.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/index.php?page=genieacs_servers" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-1"></i> Config Server
        </a>
    </div>
</div>

<?php if (empty($servers)): ?>
    <div class="alert alert-warning py-3">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Belum Ada Server GenieACS!</h5>
        <p class="mb-0">Anda perlu mengkonfigurasi server GenieACS terlebih dahulu di menu <strong>Administrasi > Config GenieACS</strong>.</p>
    </div>
<?php else: ?>

    <?php if ($api_error): ?>
        <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-octagon me-2"></i><?= htmlspecialchars($api_error) ?></div>
    <?php endif; ?>

    <div class="card table-card">
        <div class="table-toolbar flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-600"><?= count($devices) ?> Perangkat Terdeteksi</span>
                
                <form method="GET" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" name="page" value="monitor_ont">
                    <select name="server_id" class="form-select form-select-sm" style="width:200px" onchange="this.form.submit()">
                        <?php foreach ($servers as $srv): ?>
                        <option value="<?= $srv['id'] ?>" <?= $selServerId == $srv['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($srv['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <div class="d-flex m-0">
                <div class="input-group input-group-sm" style="width:220px">
                    <input type="text" id="searchOnt" class="form-control" placeholder="Cari SN / IP...">
                    <button class="btn btn-outline-secondary" type="button"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="ontTable">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Serial Number (ID)</th>
                        <th>Merek / Model</th>
                        <th>IP Address</th>
                        <th>Optical Power (RX / TX)</th>
                        <th>Last Inform</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($devices)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada perangkat ONT yang terdeteksi di server ini.</td></tr>
                <?php else: ?>
                <?php foreach ($devices as $d): ?>
                <tr class="ont-row">
                    <td>
                        <?php if ($d['is_online']): ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Online
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2">
                                <i class="bi bi-circle-fill me-1" style="font-size:8px;"></i> Offline
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong class="font-mono text-primary ont-sn"><?= htmlspecialchars($d['sn']) ?></strong>
                        <div class="small text-muted">ID: <?= htmlspecialchars($d['_id']) ?></div>
                    </td>
                    <td>
                        <span class="badge bg-dark"><?= htmlspecialchars($d['brand'] ?: 'Unknown') ?></span>
                    </td>
                    <td class="font-mono ont-ip"><?= htmlspecialchars($d['ip'] ?: '-') ?></td>
                    <td>
                        <?php 
                            $rx = (float)$d['rx']; 
                            $rx_color = ($rx < -27) ? 'text-danger fw-bold' : (($rx < -25) ? 'text-warning' : 'text-success');
                        ?>
                        <div class="font-mono <?= $rx_color ?>" style="font-size:14px;" title="Redaman (RX)">
                            <i class="bi bi-box-arrow-in-down me-1"></i><?= $d['rx'] ?> dBm
                        </div>
                        <div class="font-mono text-muted" style="font-size:12px;" title="Pancaran (TX)">
                            <i class="bi bi-box-arrow-up me-1"></i><?= $d['tx'] ?> dBm
                        </div>
                    </td>
                    <td>
                        <?php if ($d['last_inform']): ?>
                            <?= date('d/m/Y H:i', strtotime($d['last_inform'])) ?>
                            <div class="small text-muted"><?= time_elapsed_string($d['last_inform']) ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon" title="Refresh ONT (Kirim TR-069 Task)" onclick="refreshOnt('<?= urlencode($d['_id']) ?>')">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Reboot ONT" onclick="rebootOnt('<?= urlencode($d['_id']) ?>')">
                                <i class="bi bi-power"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.getElementById('searchOnt').addEventListener('input', function(e) {
        let val = e.target.value.toLowerCase();
        document.querySelectorAll('.ont-row').forEach(row => {
            let sn = row.querySelector('.ont-sn').textContent.toLowerCase();
            let ip = row.querySelector('.ont-ip').textContent.toLowerCase();
            if (sn.includes(val) || ip.includes(val)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    async function runTask(devId, action, confirmMsg) {
        if (!confirm(confirmMsg)) return;
        try {
            const formData = new URLSearchParams();
            formData.append('server_id', '<?= $selServerId ?>');
            formData.append('dev_id', devId);
            formData.append('action', action);
            formData.append('csrf', getCsrf());

            const req = await fetch('/ajax/genieacs_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            });
            const res = await req.json();
            
            if (res.success) {
                showToast(res.message, 'success');
            } else {
                showToast('Error: ' + res.error, 'error');
            }
        } catch (err) {
            showToast('Gagal mengirim perintah: ' + err.message, 'error');
        }
    }

    function refreshOnt(id) {
        runTask(id, 'refresh', 'Kirim perintah Refresh parameter ke perangkat ini?');
    }
    
    function rebootOnt(id) {
        runTask(id, 'reboot', 'Apakah Anda yakin ingin mereboot perangkat ONT ini dari jarak jauh?');
    }
    </script>
<?php endif; ?>

<?php include __DIR__ . '/../../include/footer.php'; ?>
