<?php
/**
 * Settings — General (timezone, app name etc.)
 */
$page_title = 'Pengaturan';
auth_require_superadmin();
include __DIR__ . '/../../include/header.php';
?>
<div class="page-header">
    <div><h1 class="page-title">Pengaturan Aplikasi</h1><p class="page-subtitle">Konfigurasi umum aplikasi</p></div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-info-circle"></i> Informasi Sistem</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Versi Aplikasi</th><td><?= APP_VERSION ?></td></tr>
                    <tr><th>PHP Version</th><td><?= phpversion() ?></td></tr>
                    <tr><th>Database</th><td><?= DB_NAME ?> @ <?= DB_HOST ?></td></tr>
                    <tr><th>Timezone</th><td><?= APP_TIMEZONE ?></td></tr>
                    <tr><th>Waktu Server</th><td><?= date('d M Y H:i:s') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-database"></i> Status Database</h5></div>
            <div class="card-body">
                <?php
                $tables = ['radcheck','radreply','radacct','nas','routers','profiles','vouchers','admins'];
                foreach ($tables as $t):
                    $cnt = (int)(db_fetch_one("SELECT COUNT(*) AS n FROM `{$t}`")['n'] ?? 0);
                ?>
                <div class="d-flex justify-content-between border-bottom py-1" style="font-size:.82rem;">
                    <span class="font-mono"><?= $t ?></span>
                    <span class="badge bg-secondary"><?= number_format($cnt) ?> baris</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-terminal"></i> Konfigurasi FreeRADIUS</h5></div>
            <div class="card-body">
                <p class="text-muted">Tambahkan konfigurasi berikut ke file FreeRADIUS Anda:</p>
                <div class="bg-dark text-light p-3 rounded" style="font-family:monospace;font-size:.8rem;">
<pre class="mb-0 text-light"># /etc/freeradius/3.0/mods-available/sql
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"
    server = "127.0.0.1"
    port = 3306
    login = "<?= DB_USER ?>"
    password = "***"
    radius_db = "<?= DB_NAME ?>"
    
    # FreeRADIUS standard table names (sudah dibuat oleh installer)
    acct_table1 = "radacct"
    acct_table2 = "radacct"
    postauth_table = "radpostauth"
    authcheck_table = "radcheck"
    authreply_table = "radreply"
    groupcheck_table = "radgroupcheck"
    groupreply_table = "radgroupreply"
    usergroup_table = "radusergroup"
    nas_table = "nas"
    
    read_clients = yes
    client_table = "nas"
}

# /etc/freeradius/3.0/clients.conf
# (Akan otomatis dibaca dari tabel 'nas' jika read_clients = yes)</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../include/footer.php'; ?>
