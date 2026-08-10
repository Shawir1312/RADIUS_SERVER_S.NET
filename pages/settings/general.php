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
    
    # Nama user untuk query
    sql_user_name = "%{%{Stripped-User-Name}:-%{%{User-Name}:-DEFAULT}}"
    default_user_profile = ""

    # Simultaneous-Use check (cegah 1 voucher dipakai banyak orang)
    simul_count_query = "SELECT COUNT(*) FROM ${acct_table1} WHERE username = '%{SQL-User-Name}' AND acctstoptime IS NULL"
    simul_verify_query = "SELECT radacctid, acctsessionid, username, nasipaddress, nasportid, framedipaddress, callingstationid, framedprotocol FROM ${acct_table1} WHERE username = '%{SQL-User-Name}' AND acctstoptime IS NULL"

    read_clients = yes
    client_table = "nas"
    group_attribute = "SQL-Group"

    # Karakter aman untuk query
    safe_characters = "@abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-_: /"

    # Muat query standar FreeRADIUS untuk MySQL
    $INCLUDE ${modconfdir}/${.:name}/main/${dialect}/queries.conf
}

# /etc/freeradius/3.0/clients.conf
# (Akan otomatis dibaca dari tabel 'nas' jika read_clients = yes)</pre>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-clock-history"></i> Konfigurasi Cron Job (Otomatisasi)</h5></div>
            <div class="card-body">
                <p class="text-muted mb-2">Agar sistem dapat secara otomatis <strong>memutuskan koneksi voucher yang habis waktu (expired)</strong> dan <strong>menghapus voucher kadaluarsa</strong>, Anda wajib menambahkan perintah Cron Job di server hosting atau VPS Anda.</p>
                
                <h6 class="fw-bold mt-3">Langkah-langkah:</h6>
                <ol class="text-muted" style="font-size: 0.9rem;">
                    <li>Buka terminal server / VPS Anda.</li>
                    <li>Ketik perintah <code>crontab -e</code> untuk mengedit jadwal cron.</li>
                    <li>Tambahkan baris kode di bawah ini pada baris paling bawah.</li>
                    <li>Simpan konfigurasi (jika menggunakan nano: tekan <code>Ctrl+X</code>, lalu <code>Y</code>, lalu <code>Enter</code>).</li>
                </ol>

                <div class="bg-dark text-light p-3 rounded" style="font-family:monospace;font-size:.85rem;">
<pre class="mb-0 text-warning"># Jalankan pengecekan voucher expired setiap 5 menit
*/5 * * * * /www/server/php/81/bin/php <?= realpath(__DIR__ . '/../../cron/expire_vouchers.php') ?></pre>
                </div>
                
                <div class="alert alert-info mt-3 mb-0" style="font-size:0.85rem;">
                    <i class="bi bi-info-circle-fill me-2"></i> <strong>Catatan:</strong> Path PHP di atas (<code>/www/server/php/81/bin/php</code>) adalah contoh untuk pengguna <strong>aaPanel (PHP 8.1)</strong>. Sesuaikan dengan path PHP CLI di server Anda (contoh lain: <code>/usr/bin/php</code>).
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../include/footer.php'; ?>
