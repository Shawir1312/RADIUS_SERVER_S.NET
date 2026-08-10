<?php
/**
 * Laporan Penagihan
 */
$page_title = 'Laporan Penagihan';
$admin = current_admin();

// Fetch routers for dropdown
$all_routers = get_all_routers();

// Filter date range
$filter_start = get('start_date', date('Y-m-01'));
$filter_end   = get('end_date', date('Y-m-d'));
$router_filter = (int)get('router_id');

// Build query for history
$sql_history = "SELECT p.*, r.name as router_name, pr.name as profile_name, a.full_name as admin_name 
                FROM penagihan p 
                JOIN routers r ON p.router_id = r.id 
                JOIN profiles pr ON p.profile_id = pr.id 
                LEFT JOIN admins a ON p.ditagih_oleh = a.id 
                WHERE p.tanggal BETWEEN ? AND ?";
$types_history = 'ss';
$params_history = [$filter_start, $filter_end];

if ($router_filter > 0) {
    $sql_history .= " AND p.router_id = ?";
    $types_history .= 'i';
    $params_history[] = $router_filter;
}
$sql_history .= " ORDER BY p.created_at DESC";

$history = db_fetch_all($sql_history, $types_history, $params_history);

// Calculate totals
$total_kotor = 0;
$total_bersih = 0;
$total_transaksi = count($history);

foreach ($history as $h) {
    $total_kotor += (float)$h['total_pendapatan'];
    $total_bersih += (float)$h['pendapatan_bersih'];
}

include __DIR__ . '/../../include/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-wallet2 text-primary me-2"></i> Laporan Penagihan</h1>
        <p class="page-subtitle">Input pendapatan tagihan reseller per cabang — sistem hitung otomatis</p>
    </div>
</div>

<!-- Header Statistik -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="d-flex align-items-end gap-3 flex-wrap">
            <input type="hidden" name="page" value="penagihan_report">
            <div>
                <label class="form-label" style="font-size:.8rem;font-weight:600">Statistik Per Cabang: Dari</label>
                <input type="date" class="form-control form-control-sm" name="start_date" value="<?= htmlspecialchars($filter_start) ?>">
            </div>
            <div>
                <label class="form-label" style="font-size:.8rem;font-weight:600">s/d</label>
                <input type="date" class="form-control form-control-sm" name="end_date" value="<?= htmlspecialchars($filter_end) ?>">
            </div>
            <div>
                <label class="form-label" style="font-size:.8rem;font-weight:600">Cabang</label>
                <select class="form-select form-select-sm" name="router_id">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($all_routers as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $router_filter == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i> Tampilkan</button>
            </div>
        </form>
        
        <div class="row g-3 mt-2">
            <div class="col-md-6 col-lg-4">
                <div class="p-3 rounded" style="background-color:var(--blue-pale); border-left:4px solid var(--blue);">
                    <div class="text-uppercase fw-bold text-muted mb-1" style="font-size:0.75rem;"><i class="bi bi-cash-stack me-1"></i> TOTAL SEMUA CABANG (KOTOR)</div>
                    <div class="fs-4 fw-bold text-dark"><?= format_price($total_kotor) ?></div>
                    <div class="text-muted" style="font-size:0.85rem;"><?= $total_transaksi ?> transaksi penagihan</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="p-3 rounded" style="background-color:var(--green-pale); border-left:4px solid var(--green);">
                    <div class="text-uppercase fw-bold text-muted mb-1" style="font-size:0.75rem;"><i class="bi bi-piggy-bank me-1"></i> TOTAL BERSIH PERUSAHAAN</div>
                    <div class="fs-4 fw-bold text-success"><?= format_price($total_bersih) ?></div>
                    <div class="text-muted" style="font-size:0.85rem;">Setelah dipotong bagian reseller</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Penagihan Baru -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Input Penagihan Baru</h5>
    </div>
    <div class="card-body">
        <form id="formPenagihan" method="POST" action="/process/save_penagihan.php">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <!-- Step 1: Cabang -->
            <div class="p-3 mb-3 rounded" style="background-color:#f8fdf8; border:1px solid #c3e6cb;">
                <h6 class="fw-bold mb-3"><span class="badge bg-success rounded-circle me-2">1</span> Pilih Cabang</h6>
                <div class="mb-2">
                    <label class="form-label" style="font-size:.8rem;font-weight:600">NAMA CABANG *</label>
                    <select class="form-select" name="router_id" id="selectRouter" required>
                        <option value="">— Pilih Cabang —</option>
                        <?php foreach ($all_routers as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Step 2: Reseller -->
            <div class="p-3 mb-3 rounded" style="background-color:#f8f9fa; border:1px solid #dee2e6;">
                <h6 class="fw-bold mb-3"><span class="badge bg-primary rounded-circle me-2">2</span> Pilih Reseller</h6>
                <div class="mb-2">
                    <label class="form-label" style="font-size:.8rem;font-weight:600">NAMA RESELLER *</label>
                    <select class="form-select" name="profile_id" id="selectProfile" required disabled>
                        <option value="">— Pilih Cabang Terlebih Dahulu —</option>
                    </select>
                </div>
                <div id="resellerInfo" class="alert alert-success py-2 px-3 mt-2 mb-0 d-none" style="font-size:.85rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> <span id="resellerName" class="fw-bold"></span> — Keuntungan: <span id="resellerPercent" class="fw-bold"></span>% · <span id="resellerPrice" class="fw-bold"></span>/voucher
                </div>
            </div>

            <!-- Step 3: Input & Calc -->
            <div class="p-3 mb-3 rounded" style="background-color:#fcf8ff; border:1px solid #e1c4ff;">
                <h6 class="fw-bold mb-3"><span class="badge bg-purple rounded-circle me-2" style="background-color:#6f42c1">3</span> Isi Total Pendapatan</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:.8rem;font-weight:600">TOTAL PENDAPATAN (RP) *</label>
                        <input type="number" class="form-control fw-bold fs-5" name="total_pendapatan" id="inputTotal" placeholder="Contoh: 500000" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:.8rem;font-weight:600">CATATAN (opsional)</label>
                        <input type="text" class="form-control" name="catatan" placeholder="Keterangan penagihan...">
                    </div>
                </div>
            </div>

            <!-- Result Box -->
            <div class="p-3 mb-3 rounded text-white shadow" style="background-color:#1e3a8a;">
                <h6 class="fw-bold mb-3 text-uppercase" style="font-size:.75rem; color:#93c5fd;"><i class="bi bi-calculator me-1"></i> Hasil Perhitungan Otomatis</h6>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary">
                    <span><i class="bi bi-cash me-2"></i>Total Pendapatan</span>
                    <span class="fw-bold" id="resTotal">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary">
                    <span><i class="bi bi-arrow-down-right text-warning me-2"></i>Bagian Reseller (<span id="resLabelPercent">0</span>%)</span>
                    <span class="fw-bold text-warning" id="resBagian">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-secondary fs-5 text-success">
                    <span><i class="bi bi-check-circle-fill me-2"></i>Pendapatan Bersih</span>
                    <span class="fw-bold" id="resBersih">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-1" style="font-size:.9rem; color:#93c5fd;">
                    <span><i class="bi bi-ticket-perforated me-2"></i>Estimasi Voucher Terjual</span>
                    <span class="fw-bold" id="resVoucher">0 voucher</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" id="btnSubmit" disabled>
                <i class="bi bi-send-fill me-2"></i> KIRIM LAPORAN
            </button>
        </form>
    </div>
</div>

<!-- Riwayat Penagihan -->
<div class="card table-card">
    <div class="card-header bg-white pt-3 pb-2 border-bottom-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0"><i class="bi bi-clock-history me-2 text-secondary"></i> Semua Riwayat Penagihan</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr style="font-size:.75rem; text-transform:uppercase;">
                    <th>Waktu</th>
                    <th>Cabang</th>
                    <th>Reseller</th>
                    <th>Total</th>
                    <th>Bagian Reseller</th>
                    <th>Bersih</th>
                    <th>Voucher</th>
                    <th>Status</th>
                    <th>Ditagih Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($history)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data penagihan di periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach($history as $h): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($h['tanggal'])) ?></div>
                            <small class="text-muted"><?= date('H:i', strtotime($h['created_at'])) ?></small>
                        </td>
                        <td><span class="badge bg-purple text-purple bg-opacity-10 border border-purple"><?= htmlspecialchars($h['router_name']) ?></span></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($h['profile_name']) ?></div>
                        </td>
                        <td class="fw-bold"><?= format_price((float)$h['total_pendapatan']) ?></td>
                        <td class="text-danger fw-600"><?= format_price((float)$h['bagian_reseller']) ?></td>
                        <td class="text-success fw-bold"><?= format_price((float)$h['pendapatan_bersih']) ?></td>
                        <td>
                            <div class="fw-bold text-primary"><?= $h['estimasi_voucher'] ?> vs <?= $h['voucher_aktual'] ?></div>
                        </td>
                        <td>
                            <?php if ($h['status_kecocokan'] === 'sesuai'): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Sesuai</span>
                            <?php elseif ($h['status_kecocokan'] === 'tekor'): ?>
                                <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Tekor</span>
                            <?php else: ?>
                                <span class="badge bg-info"><i class="bi bi-info-circle"></i> Lebih</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-600"><?= htmlspecialchars($h['admin_name']) ?></div>
                            <?php if ($h['ditagih_oleh'] == $admin['id']): ?>
                                <span class="badge bg-success" style="font-size:0.6rem;">SAYA</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let currentProfiles = [];
let selectedProfile = null;

const selectRouter = document.getElementById('selectRouter');
const selectProfile = document.getElementById('selectProfile');
const inputTotal = document.getElementById('inputTotal');
const btnSubmit = document.getElementById('btnSubmit');

const elResellerInfo = document.getElementById('resellerInfo');
const elResellerName = document.getElementById('resellerName');
const elResellerPercent = document.getElementById('resellerPercent');
const elResellerPrice = document.getElementById('resellerPrice');

const resTotal = document.getElementById('resTotal');
const resBagian = document.getElementById('resBagian');
const resBersih = document.getElementById('resBersih');
const resVoucher = document.getElementById('resVoucher');
const resLabelPercent = document.getElementById('resLabelPercent');

// Format Rupiah function
const formatRp = (num) => {
    return 'Rp ' + parseFloat(num).toLocaleString('id-ID', {minimumFractionDigits:0});
};

selectRouter.addEventListener('change', function() {
    const rid = this.value;
    selectProfile.innerHTML = '<option value="">— Loading... —</option>';
    selectProfile.disabled = true;
    selectedProfile = null;
    calculate();
    
    if(!rid) {
        selectProfile.innerHTML = '<option value="">— Pilih Cabang Terlebih Dahulu —</option>';
        return;
    }
    
    fetch('/process/get_resellers.php?router_id=' + rid)
        .then(r => r.json())
        .then(data => {
            currentProfiles = data;
            selectProfile.innerHTML = '<option value="">— Pilih Reseller —</option>';
            data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = `${p.name} (${parseFloat(p.reseller_percent)}%)`;
                selectProfile.appendChild(opt);
            });
            selectProfile.disabled = false;
        })
        .catch(e => {
            selectProfile.innerHTML = '<option value="">— Gagal memuat data —</option>';
        });
});

selectProfile.addEventListener('change', function() {
    const pid = this.value;
    if(!pid) {
        selectedProfile = null;
        elResellerInfo.classList.add('d-none');
    } else {
        selectedProfile = currentProfiles.find(p => p.id == pid);
        if(selectedProfile) {
            elResellerName.textContent = selectedProfile.name;
            elResellerPercent.textContent = parseFloat(selectedProfile.reseller_percent);
            elResellerPrice.textContent = formatRp(selectedProfile.price);
            elResellerInfo.classList.remove('d-none');
        }
    }
    calculate();
});

inputTotal.addEventListener('input', calculate);

function calculate() {
    if(!selectedProfile || !inputTotal.value) {
        resTotal.textContent = 'Rp 0';
        resBagian.textContent = 'Rp 0';
        resBersih.textContent = 'Rp 0';
        resVoucher.textContent = '0 voucher';
        resLabelPercent.textContent = '0';
        btnSubmit.disabled = true;
        return;
    }
    
    const total = parseFloat(inputTotal.value) || 0;
    const percent = parseFloat(selectedProfile.reseller_percent) || 0;
    const price = parseFloat(selectedProfile.price) || 0;
    
    const bagian = total * (percent / 100);
    const bersih = total - bagian;
    const estimasi = price > 0 ? Math.floor(total / price) : 0;
    
    resTotal.textContent = formatRp(total);
    resBagian.textContent = formatRp(bagian);
    resBersih.textContent = formatRp(bersih);
    resVoucher.textContent = estimasi + ' voucher';
    resLabelPercent.textContent = percent;
    
    btnSubmit.disabled = total <= 0;
}
</script>

<?php include __DIR__ . '/../../include/footer.php'; ?>
