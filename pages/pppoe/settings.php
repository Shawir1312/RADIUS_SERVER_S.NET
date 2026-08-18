<?php
/**
 * PPPoE Settings — Configuration for Isolir, Billing & Midtrans Payment Gateway
 */
$page_title = 'Pengaturan PPPoE & Billing';
auth_require_superadmin();

// Load current settings from database
$raw_settings = db_fetch_all("SELECT setting_key, setting_value FROM pppoe_settings");
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Pengaturan PPPoE &amp; Billing</h1>
        <p class="page-subtitle">Kelola konfigurasi isolir otomatis, kontak bantuan, dan payment gateway Midtrans</p>
    </div>
</div>

<form method="POST" action="/process/save_pppoe_settings.php">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div class="row g-4">
        <!-- Pengaturan Isolir -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="bi bi-shield-slash text-danger me-2"></i>Konfigurasi Auto-Isolir</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Profil Isolir di MikroTik <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="isolir_profile" required
                               value="<?= htmlspecialchars($settings['isolir_profile'] ?? 'isolir') ?>"
                               placeholder="isolir">
                        <div class="form-text">Nama profile PPP di MikroTik yang digunakan untuk mengarahkan pelanggan menunggak ke web isolir.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Masa Tenggang / Grace Period (Hari) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="isolir_grace_days" required min="0" max="30"
                                   value="<?= htmlspecialchars($settings['isolir_grace_days'] ?? '3') ?>">
                            <span class="input-group-text">Hari</span>
                        </div>
                        <div class="form-text">Jumlah hari toleransi setelah tanggal jatuh tempo sebelum cron melakukan isolir otomatis.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">URL Halaman Isolir Pelanggan</label>
                        <input type="text" class="form-control" name="isolir_redirect_url"
                               value="<?= htmlspecialchars($settings['isolir_redirect_url'] ?? '/portal/isolir.php') ?>"
                               placeholder="/portal/isolir.php">
                        <div class="form-text">Halaman tujuan di mana pelanggan terisolir dapat melihat rincian tagihan &amp; bayar online.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Perusahaan & Kontak CS -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title"><i class="bi bi-building text-primary me-2"></i>Kontak &amp; Identitas Layanan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Layanan / Perusahaan</label>
                        <input type="text" class="form-control" name="company_name"
                               value="<?= htmlspecialchars($settings['company_name'] ?? 'S.NET Internet') ?>"
                               placeholder="S.NET Internet">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor WhatsApp / CS Bantuan</label>
                        <input type="text" class="form-control" name="company_phone"
                               value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"
                               placeholder="081234567890">
                        <div class="form-text">Ditampilkan di halaman portal pelanggan dan tombol bantuan saat isolir.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Kantor / Keterangan</label>
                        <textarea class="form-control" name="company_address" rows="3"
                                  placeholder="Alamat kantor layanan..."><?= htmlspecialchars($settings['company_address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Midtrans Payment Gateway -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-credit-card text-success me-2"></i>Payment Gateway Midtrans Snap</h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Otomatisasi Pembayaran Online</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Environment / Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="midtrans_mode">
                                <option value="sandbox" <?= ($settings['midtrans_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Mode Testing)</option>
                                <option value="production" <?= ($settings['midtrans_mode'] ?? '') === 'production' ? 'selected' : '' ?>>Production (Live Transaksi Nyata)</option>
                            </select>
                            <div class="form-text">Gunakan <b>Sandbox</b> untuk uji coba dan <b>Production</b> untuk menerima pembayaran asli.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Midtrans Client Key</label>
                            <input type="text" class="form-control font-mono" name="midtrans_client_key"
                                   value="<?= htmlspecialchars($settings['midtrans_client_key'] ?? '') ?>"
                                   placeholder="SB-Mid-client-...">
                            <div class="form-text">Client Key dari dashboard Midtrans (Snap JS).</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Midtrans Server Key</label>
                            <input type="password" class="form-control font-mono" name="midtrans_server_key"
                                   value="<?= htmlspecialchars($settings['midtrans_server_key'] ?? '') ?>"
                                   placeholder="SB-Mid-server-...">
                            <div class="form-text">Server Key untuk otentikasi request token &amp; signature webhook.</div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0 d-flex gap-3 align-items-start">
                        <i class="bi bi-info-circle-fill fs-5 mt-1 text-primary"></i>
                        <div style="font-size: .88rem;">
                            <strong>Webhook / Notification URL Midtrans:</strong><br>
                            Salin URL berikut ke menu <em>Settings &gt; Configuration &gt; Payment Notification URL</em> di Dashboard Midtrans Anda:
                            <code class="d-block mt-1 p-2 bg-light border rounded text-dark">
                                https://<?= $_SERVER['HTTP_HOST'] ?? 'dash.snetwifi.com' ?>/portal/isolir.php
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cron Auto-Isolir Info -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><i class="bi bi-clock-history text-warning me-2"></i>Jadwal Cron Auto-Isolir</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2" style="font-size:.88rem;">
                        Agar sistem dapat memeriksa jatuh tempo dan mengisolir pelanggan yang menunggak secara otomatis, pasang baris cron berikut di server/aaPanel Anda:
                    </p>
                    <div class="p-3 bg-dark text-light rounded font-mono" style="font-size:.82rem;">
                        0 1 * * * php <?= realpath(__DIR__ . '/../../process/cron_pppoe.php') ?: '/www/wwwroot/dash.snetwifi.com/process/cron_pppoe.php' ?> &gt;&gt; /tmp/cron_pppoe.log 2&gt;&amp;1
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i> Simpan Pengaturan
        </button>
        <a href="/index.php?page=pppoe_customers" class="btn btn-outline-secondary">Kembali ke Pelanggan</a>
    </div>
</form>

<?php include __DIR__ . '/../../include/footer.php'; ?>
