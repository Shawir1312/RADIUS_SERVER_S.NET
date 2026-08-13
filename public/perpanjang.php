<?php
/**
 * Public — Perpanjang Voucher (Self-Service)
 * Halaman ini dapat diakses tanpa login, ditampilkan via iframe di halaman hotspot Mikrotik.
 * User memasukkan kode voucher lama dan kode voucher baru untuk perpanjang.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

// Izinkan iframe dari mana saja (Mikrotik hotspot)
header('X-Frame-Options: ALLOWALL');

$success_msg = '';
$error       = '';
$step        = 1; // 1=input, 2=confirm, 3=done

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_lama = trim($_POST['kode_lama'] ?? '');
    $kode_baru = trim($_POST['kode_baru'] ?? '');
    $aksi      = $_POST['aksi'] ?? '';

    if ($aksi === 'cek') {
        // ── Step 1: Cek kedua voucher ────────────────────────────────────
        if (!$kode_lama || !$kode_baru) {
            $error = 'Kedua kode voucher wajib diisi.';
        } elseif ($kode_lama === $kode_baru) {
            $error = 'Kode lama dan kode baru tidak boleh sama.';
        } else {
            $lama = db_fetch_one("SELECT v.*, p.name AS profile_name FROM vouchers v LEFT JOIN profiles p ON v.profile_id = p.id WHERE v.username = ?", 's', [$kode_lama]);
            $baru = db_fetch_one("SELECT v.*, p.name AS profile_name FROM vouchers v LEFT JOIN profiles p ON v.profile_id = p.id WHERE v.username = ?", 's', [$kode_baru]);

            if (!$lama) {
                $error = "Voucher lama <b>{$kode_lama}</b> tidak ditemukan.";
            } elseif (!in_array($lama['status'], ['active','expired'])) {
                $error = "Voucher lama harus berstatus aktif atau expired, bukan <b>{$lama['status']}</b>.";
            } elseif (!$baru) {
                $error = "Voucher baru <b>{$kode_baru}</b> tidak ditemukan.";
            } elseif ($baru['status'] !== 'unused') {
                $error = "Voucher baru <b>{$kode_baru}</b> bukan voucher baru (status: {$baru['status']}).";
            } elseif ($lama['profile_id'] !== $baru['profile_id']) {
                $error = "Paket voucher baru (<b>{$baru['profile_name']}</b>) berbeda dengan voucher lama (<b>{$lama['profile_name']}</b>). Harus paket yang sama.";
            } else {
                // Lanjut ke konfirmasi
                $step = 2;
                $_SESSION['perpanjang_lama'] = $kode_lama;
                $_SESSION['perpanjang_baru'] = $kode_baru;
            }
        }

    } elseif ($aksi === 'konfirmasi') {
        // ── Step 2: Eksekusi perpanjang ──────────────────────────────────
        $kode_lama = $_SESSION['perpanjang_lama'] ?? '';
        $kode_baru = $_SESSION['perpanjang_baru'] ?? '';
        unset($_SESSION['perpanjang_lama'], $_SESSION['perpanjang_baru']);

        if (!$kode_lama || !$kode_baru) {
            $error = 'Sesi tidak valid. Silakan ulangi dari awal.';
        } else {
            $lama = db_fetch_one("SELECT v.*, p.duration_value, p.duration_unit FROM vouchers v LEFT JOIN profiles p ON v.profile_id = p.id WHERE v.username = ?", 's', [$kode_lama]);
            $baru = db_fetch_one("SELECT * FROM vouchers WHERE username = ? AND status = 'unused'", 's', [$kode_baru]);

            if ($lama && $baru) {
                db_begin();
                try {
                    $now = date('Y-m-d H:i:s');
                    
                    // Hitung expired baru: dari expired_lama atau sekarang, tambah durasi
                    $base = ($lama['status'] === 'active' && $lama['expired_at'])
                        ? strtotime($lama['expired_at'])
                        : time();
                    $dur_s = duration_to_seconds($lama['duration_value'], $lama['duration_unit']);
                    $new_expired = date('Y-m-d H:i:s', $base + $dur_s);

                    // Tandai voucher lama sebagai expired
                    db_execute("UPDATE vouchers SET status='expired', expired_at=? WHERE username=?", 'ss', [$now, $kode_lama]);

                    // Aktifkan voucher baru dengan durasi dari awal (mulai sekarang)
                    $expired_baru = date('Y-m-d H:i:s', time() + $dur_s);
                    db_execute("UPDATE vouchers SET status='active', used_at=?, expired_at=? WHERE username=?", 'sss', [$now, $expired_baru, $kode_baru]);

                    // Update radcheck — ganti username lama dengan baru di RADIUS
                    // Hapus entry lama dari radcheck & radreply
                    db_execute("DELETE FROM radcheck WHERE username = ?", 's', [$kode_lama]);
                    db_execute("DELETE FROM radreply  WHERE username = ?", 's', [$kode_lama]);

                    db_commit();
                    $step = 3;
                    $success_msg = "Voucher berhasil diperpanjang! Kode baru <b>{$kode_baru}</b> sekarang aktif.";
                } catch (Throwable $e) {
                    db_rollback();
                    $error = 'Gagal memproses perpanjangan: ' . $e->getMessage();
                }
            } else {
                $error = 'Data tidak valid. Silakan ulangi dari awal.';
            }
        }

    } elseif ($aksi === 'batal') {
        unset($_SESSION['perpanjang_lama'], $_SESSION['perpanjang_baru']);
        $step = 1;
    }
}

// Untuk tampilan konfirmasi (step 2)
$lama_info = null;
$baru_info = null;
if ($step === 2) {
    $lama_info = db_fetch_one("SELECT v.*, p.name AS profile_name FROM vouchers v LEFT JOIN profiles p ON v.profile_id = p.id WHERE v.username = ?", 's', [$_SESSION['perpanjang_lama'] ?? '']);
    $baru_info = db_fetch_one("SELECT v.*, p.name AS profile_name FROM vouchers v LEFT JOIN profiles p ON v.profile_id = p.id WHERE v.username = ?", 's', [$_SESSION['perpanjang_baru'] ?? '']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpanjang Voucher — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            padding: 16px;
            font-size: 14px;
        }
        .card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        .card-header { background: #1565C0; color: #fff; border-radius: 12px 12px 0 0 !important; padding: 12px 16px; }
        .info-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #dee2e6; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #6c757d; font-size: .82rem; }
        .info-value { font-weight: 600; }
    </style>
</head>
<body>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat"></i>
            <strong>Perpanjang Voucher</strong>
        </div>
        <div class="card-body p-3">

            <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i><?= $error ?>
            </div>
            <?php endif; ?>

            <?php if ($step === 3): ?>
            <!-- ── SELESAI ── -->
            <div class="text-center py-2">
                <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0"><?= $success_msg ?></p>
                <small class="text-muted">Silakan login kembali menggunakan kode voucher baru.</small>
            </div>

            <?php elseif ($step === 2 && $lama_info && $baru_info): ?>
            <!-- ── KONFIRMASI ── -->
            <p class="mb-2 fw-semibold">Konfirmasi Perpanjangan:</p>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="bg-light rounded p-2">
                        <div class="text-muted" style="font-size:.75rem;">Voucher LAMA</div>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($lama_info['username']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($lama_info['profile_name']) ?></small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light rounded p-2">
                        <div class="text-muted" style="font-size:.75rem;">Voucher BARU</div>
                        <div class="fw-bold font-monospace"><?= htmlspecialchars($baru_info['username']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($baru_info['profile_name']) ?></small>
                    </div>
                </div>
            </div>
            <p class="text-muted mb-3" style="font-size:.82rem;">
                <i class="bi bi-info-circle me-1"></i>
                Voucher lama akan dinonaktifkan dan voucher baru langsung aktif dari sekarang.
            </p>
            <form method="POST" class="d-flex gap-2">
                <button type="submit" name="aksi" value="konfirmasi" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-check-lg me-1"></i>Ya, Perpanjang Sekarang
                </button>
                <button type="submit" name="aksi" value="batal" class="btn btn-outline-secondary btn-sm">
                    Batal
                </button>
            </form>

            <?php else: ?>
            <!-- ── STEP 1: INPUT ── -->
            <form method="POST">
                <input type="hidden" name="aksi" value="cek">
                <div class="mb-2">
                    <label class="form-label mb-1 small fw-semibold">Kode Voucher LAMA (yang sekarang)</label>
                    <input type="text" class="form-control form-control-sm font-monospace"
                           name="kode_lama" placeholder="Masukkan kode lama…"
                           value="<?= htmlspecialchars($_POST['kode_lama'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label mb-1 small fw-semibold">Kode Voucher BARU (untuk perpanjang)</label>
                    <input type="text" class="form-control form-control-sm font-monospace"
                           name="kode_baru" placeholder="Masukkan kode baru…"
                           value="<?= htmlspecialchars($_POST['kode_baru'] ?? '') ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Cek & Perpanjang
                </button>
            </form>
            <p class="text-muted text-center mt-3 mb-0" style="font-size:.78rem;">
                <i class="bi bi-info-circle me-1"></i>Paket voucher baru harus sama dengan voucher lama
            </p>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
