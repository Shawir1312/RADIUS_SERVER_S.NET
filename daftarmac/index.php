<?php
// ================================================================
// MAC Registration — Main Page (Mobile-First, Technician-Friendly)
// ================================================================
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
$routers = getRouters();
$selectedRouter = getSelectedRouter();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>MAC Registration</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════════════
   MOBILE-FIRST DESIGN — ISP Manager Theme (Biru-Putih-Merah)
   ══════════════════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --red:#D42B2B;--red-d:#A51C1C;--red-l:#F23535;
  --blue:#1B3FA6;--blue-d:#122B7A;--blue-m:#1E4DBF;--blue-l:#2555D4;
  --green:#16A34A;--green-d:#15803D;
  --orange:#D97706;
  --g50:#F8FAFF;--g100:#F0F3FA;--g200:#E0E6F5;--g300:#C8D3EC;
  --g400:#8A95B8;--g500:#6270A0;--g600:#5A6490;--g700:#3A4468;--g900:#1A2040;
  --card-bg:#fff;--card-border:var(--g200);--body-bg:var(--g50);
}
html{-webkit-tap-highlight-color:transparent}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--body-bg);color:var(--g700);min-height:100vh;min-height:100dvh;display:flex;flex-direction:column;font-size:15px;-webkit-font-smoothing:antialiased}

/* ── HEADER ── */
.hdr{position:sticky;top:0;z-index:100;background:linear-gradient(135deg,var(--blue-d),var(--blue) 60%,var(--blue-m));padding:14px 16px;box-shadow:0 2px 16px rgba(18,43,122,.4)}
.hdr::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--red) 0%,var(--red-l) 30%,#fff 50%,var(--blue-l) 70%,var(--blue) 100%)}
.hdr-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
.hdr-logo{color:#fff;font-size:1.1rem;font-weight:800;display:flex;align-items:center;gap:8px}
.hdr-logo span{font-size:1.3rem}
.hdr-user{display:flex;align-items:center;gap:8px}
.hdr-name{color:rgba(255,255,255,.9);font-size:.72rem;font-weight:600;text-align:right;line-height:1.3}
.hdr-role{background:var(--red);color:#fff;padding:1px 8px;border-radius:10px;font-size:.58rem;font-weight:700;display:inline-block;margin-top:1px}
.btn-logout{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:7px 12px;border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:.2s;white-space:nowrap}
.btn-logout:hover{background:rgba(255,255,255,.25)}

/* ── Router Selector (di bawah header) ── */
.router-bar{display:flex;align-items:center;gap:8px;padding:10px 16px;background:rgba(255,255,255,.06);border-top:1px solid rgba(255,255,255,.08)}
.router-bar label{color:rgba(255,255,255,.7);font-size:.72rem;font-weight:600;white-space:nowrap}
.router-select{flex:1;padding:7px 10px;border:1px solid rgba(255,255,255,.2);border-radius:8px;font-family:inherit;font-size:.8rem;color:#fff;background:rgba(255,255,255,.1);outline:none;cursor:pointer;-webkit-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1l4 4 4-4' stroke='white' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;padding-right:28px}
.router-select option{color:#333;background:#fff}
.router-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;background:var(--orange);animation:pulse 1.5s infinite}
.router-dot.on{background:#4ADE80;animation:none;box-shadow:0 0 6px rgba(74,222,128,.5)}
.router-dot.off{background:#EF4444;animation:none}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.router-identity{color:rgba(255,255,255,.6);font-size:.68rem;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ── MAIN CONTENT ── */
.main{flex:1;padding:16px;max-width:800px;width:100%;margin:0 auto}

/* ── BIG ACTION BUTTON — "Daftarkan MAC Baru" ── */
.action-hero{margin-bottom:18px}
.btn-daftar{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:18px 20px;border:none;border-radius:14px;
  background:linear-gradient(135deg,var(--blue),var(--blue-d));
  color:#fff;font-family:inherit;font-size:1.05rem;font-weight:800;
  cursor:pointer;transition:all .2s;
  box-shadow:0 4px 16px rgba(27,63,166,.35);
  position:relative;overflow:hidden;
  letter-spacing:-.01em;
}
.btn-daftar:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(27,63,166,.45)}
.btn-daftar:active{transform:scale(.98)}
.btn-daftar .ico{font-size:1.4rem}
.btn-daftar::after{content:'';position:absolute;top:0;right:0;bottom:0;width:6px;background:var(--red);border-radius:0 14px 14px 0}

/* ── STATS (compact row) ── */
.stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:18px}
.stat{background:var(--card-bg);border-radius:12px;padding:12px 10px;border:1px solid var(--card-border);text-align:center;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--c,var(--blue))}
.stat-n{font-size:1.6rem;font-weight:900;color:var(--g900);line-height:1}
.stat-l{font-size:.62rem;font-weight:700;color:var(--g400);text-transform:uppercase;letter-spacing:.5px;margin-top:3px}

/* ── SEARCH BAR ── */
.search-wrap{margin-bottom:14px}
.search-input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--g200);border-radius:12px;font-family:inherit;font-size:.92rem;color:var(--g700);background:var(--card-bg);outline:none;transition:.2s;
  background-image:url("data:image/svg+xml,%3Csvg width='18' height='18' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='11' cy='11' r='7' stroke='%238A95B8' stroke-width='2'/%3E%3Cpath d='M16 16l4 4' stroke='%238A95B8' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:12px center}
.search-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.search-input::placeholder{color:var(--g400)}

/* ── BINDING CARDS (Mobile-friendly card layout) ── */
.card-list{display:flex;flex-direction:column;gap:10px}

.bind-card{
  background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;
  overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:.2s;
  animation:cardIn .3s ease both;
}
.bind-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
@keyframes cardIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

.bind-card-body{padding:14px 16px;display:flex;align-items:center;gap:14px}
.bind-avatar{
  width:46px;height:46px;border-radius:12px;flex-shrink:0;
  background:linear-gradient(135deg,var(--blue),var(--blue-m));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:1.2rem;font-weight:800;
}
.bind-info{flex:1;min-width:0}
.bind-name{font-size:.92rem;font-weight:700;color:var(--g900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bind-mac{font-family:'SF Mono','JetBrains Mono','Fira Code',monospace;font-size:.78rem;color:var(--blue-d);font-weight:600;letter-spacing:.02em;margin-top:2px}
.bind-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-top:4px}
.bind-badge.aktif{background:#DCFCE7;color:#15803D}
.bind-badge.nonaktif{background:#FEE2E2;color:var(--red)}
.bind-badge.bypass{background:#DBEAFE;color:#1D4ED8}
.bind-badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor}

/* ── ACTION BUTTONS (Big, obvious for technicians) ── */
.bind-actions{display:flex;border-top:1px solid var(--g100)}
.bind-actions .act-btn{
  flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
  padding:12px 10px;border:none;background:none;
  font-family:inherit;font-size:.78rem;font-weight:700;
  cursor:pointer;transition:.15s;color:var(--g500);
}
.bind-actions .act-btn:first-child{border-right:1px solid var(--g100)}
.bind-actions .act-btn .act-ico{font-size:1rem}
.bind-actions .act-btn.edit-btn:active{background:#EFF6FF;color:var(--blue)}
.bind-actions .act-btn.del-btn:active{background:#FEF2F2;color:var(--red)}
.bind-actions .act-btn.edit-btn:hover{background:#EFF6FF;color:var(--blue)}
.bind-actions .act-btn.del-btn:hover{background:#FEF2F2;color:var(--red)}

/* ── LOADING / EMPTY / ERROR STATES ── */
.state-box{text-align:center;padding:48px 20px;color:var(--g400)}
.state-box .sico{font-size:3rem;margin-bottom:10px}
.state-box .stitle{font-size:1rem;font-weight:700;color:var(--g600);margin-bottom:4px}
.state-box .sdesc{font-size:.84rem;line-height:1.5;margin-bottom:14px}
.state-loading .sico{animation:pulse 1s infinite}

/* ── MODAL (Full-screen on mobile) ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(18,43,122,.55);z-index:200;align-items:flex-end;justify-content:center;backdrop-filter:blur(3px);padding:0}
.modal-bg.show{display:flex}
.modal-box{
  background:var(--card-bg);width:100%;max-width:480px;
  border-radius:20px 20px 0 0;
  box-shadow:0 -8px 40px rgba(18,43,122,.3);
  animation:slideUp .3s cubic-bezier(.4,0,.2,1);
  max-height:92vh;max-height:92dvh;overflow-y:auto;
  position:relative;
}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:none}}

/* Handle bar (mobile gesture indicator) */
.modal-handle{display:flex;justify-content:center;padding:10px 0 4px}
.modal-handle::before{content:'';width:36px;height:4px;border-radius:2px;background:var(--g300)}

.modal-head{padding:8px 20px 14px;text-align:center}
.modal-head h2{font-size:1.15rem;font-weight:800;color:var(--g900)}
.modal-head p{font-size:.78rem;color:var(--g400);margin-top:2px}

.modal-body{padding:0 20px 6px}
.modal-foot{padding:12px 20px 24px;display:flex;gap:10px}
.modal-foot .btn-cancel{flex:1;padding:14px;border:1.5px solid var(--g200);border-radius:12px;background:var(--card-bg);font-family:inherit;font-size:.9rem;font-weight:700;color:var(--g600);cursor:pointer;transition:.2s}
.modal-foot .btn-cancel:hover{background:var(--g50);border-color:var(--g300)}
.modal-foot .btn-save{flex:2;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--blue),var(--blue-d));font-family:inherit;font-size:.9rem;font-weight:800;color:#fff;cursor:pointer;transition:.2s;box-shadow:0 3px 12px rgba(27,63,166,.3)}
.modal-foot .btn-save:hover{box-shadow:0 5px 18px rgba(27,63,166,.4);transform:translateY(-1px)}
.modal-foot .btn-save:active{transform:scale(.98)}
.modal-foot .btn-save:disabled{opacity:.5;pointer-events:none}
.modal-foot .btn-delete{flex:2;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--red),var(--red-d));font-family:inherit;font-size:.9rem;font-weight:800;color:#fff;cursor:pointer;transition:.2s;box-shadow:0 3px 12px rgba(212,43,43,.3)}
.modal-foot .btn-delete:hover{box-shadow:0 5px 18px rgba(212,43,43,.4)}

/* ── FORM FIELDS (big touch targets) ── */
.field{margin-bottom:16px}
.field label{display:block;font-size:.72rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;padding-left:2px}
.field input{width:100%;padding:14px 16px;border:1.5px solid var(--g200);border-radius:12px;font-family:inherit;font-size:1rem;color:var(--g700);background:var(--g50);outline:none;transition:.2s}
.field input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.field input::placeholder{color:var(--g400);font-size:.9rem}
.field .hint{font-size:.7rem;color:var(--g400);margin-top:5px;padding-left:2px;line-height:1.4}

/* Delete modal specific */
.del-info{background:var(--g50);border:1px solid var(--g200);border-radius:12px;padding:14px;margin:10px 0 6px}
.del-info .del-mac{font-family:'SF Mono','JetBrains Mono',monospace;font-size:.88rem;font-weight:700;color:var(--blue-d)}
.del-info .del-name{font-size:.84rem;color:var(--g600);margin-top:2px}

/* ── SETTINGS BUTTON (admin) ── */
.btn-settings{
  position:fixed;bottom:20px;right:20px;z-index:50;
  width:52px;height:52px;border-radius:50%;border:none;
  background:linear-gradient(135deg,var(--blue),var(--blue-d));
  color:#fff;font-size:1.4rem;cursor:pointer;
  box-shadow:0 4px 16px rgba(27,63,166,.35);
  transition:.2s;display:flex;align-items:center;justify-content:center;
}
.btn-settings:hover{transform:scale(1.08);box-shadow:0 6px 24px rgba(27,63,166,.45)}

/* ── TOAST ── */
.toast-area{position:fixed;top:8px;left:50%;transform:translateX(-50%);z-index:999;display:flex;flex-direction:column;gap:6px;width:calc(100% - 24px);max-width:400px;pointer-events:none}
.toast-msg{
  pointer-events:auto;padding:12px 16px;border-radius:12px;font-size:.84rem;font-weight:600;
  box-shadow:0 4px 20px rgba(0,0,0,.15);animation:toastIn .3s ease;
  display:flex;align-items:center;gap:8px;
}
.toast-msg.ok{background:#DCFCE7;border:1px solid #BBF7D0;color:#15803D}
.toast-msg.err{background:#FEE2E2;border:1px solid #FECACA;color:var(--red-d)}
@keyframes toastIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:none}}

/* ── FOOTER ── */
.ftr{padding:12px 16px;border-top:1px solid var(--card-border);background:var(--card-bg);font-size:.68rem;color:var(--g400);text-align:center}

@media(min-width:600px){
  .modal-bg{align-items:center;padding:20px}
  .modal-box{border-radius:20px;max-height:85vh}
  .main{padding:20px 24px}
  .btn-daftar{padding:20px;font-size:1.1rem}
  .card-list{gap:12px}
  .bind-card-body{padding:16px 20px}
  .bind-actions .act-btn{padding:13px}
  .stats{gap:12px}
  .stat{padding:16px 14px}
  .stat-n{font-size:1.8rem}
}

@media(min-width:900px){
  .card-list{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .stats{grid-template-columns:repeat(3,1fr);gap:14px}
}


/* ── Settings modal styles (admin only) ── */
.settings-modal .modal-box{max-width:600px}
.stab-bar{display:flex;gap:2px;border-bottom:2px solid var(--g200);margin-bottom:14px;padding:0 2px}
.stab{padding:10px 16px;border:none;background:none;border-radius:8px 8px 0 0;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;color:var(--g400);border-bottom:3px solid transparent;margin-bottom:-2px;transition:.2s}
.stab.on{color:var(--blue);border-bottom-color:var(--blue);background:var(--g50)}
.slist{display:flex;flex-direction:column;gap:8px}
.sitem{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--g50);border:1px solid var(--g200);border-radius:10px;gap:10px}
.sitem-info{flex:1;min-width:0}
.sitem-name{font-size:.84rem;font-weight:700;color:var(--g900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sitem-detail{font-size:.72rem;color:var(--g400)}
.sitem-acts{display:flex;gap:4px}
.sbtn{padding:6px 10px;border-radius:6px;border:1px solid var(--g200);background:var(--card-bg);font-family:inherit;font-size:.7rem;font-weight:600;cursor:pointer;transition:.15s;color:var(--g600)}
.sbtn:hover{border-color:var(--blue);color:var(--blue)}
.sbtn.del:hover{border-color:var(--red);color:var(--red)}
.sbtn.add{background:linear-gradient(135deg,var(--blue),var(--blue-d));color:#fff;border:none;padding:8px 14px;font-size:.78rem}
.sform{margin-top:12px;padding:14px;background:var(--g50);border:1px solid var(--g200);border-radius:12px}
.sform-title{font-size:.82rem;font-weight:700;color:var(--g900);margin-bottom:10px}
.sfield{margin-bottom:10px}
.sfield label{display:block;font-size:.62rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px}
.sfield input,.sfield select{width:100%;padding:9px 12px;border:1.5px solid var(--g200);border-radius:8px;font-family:inherit;font-size:.84rem;color:var(--g700);background:var(--card-bg);outline:none;transition:.2s}
.sfield input:focus,.sfield select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.srow{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.sform-acts{display:flex;gap:8px;justify-content:flex-end;margin-top:10px}
</style>
</head>
<body>

<!-- ══════════ HEADER ══════════ -->
<header class="hdr">
  <div class="hdr-top">
    <div class="hdr-logo"><span>🔗</span> MAC Registration</div>
    <div class="hdr-user">
      <div class="hdr-name">
        <?=h($user['full_name'])?>
        <br><span class="hdr-role"><?=strtoupper($user['role'])?></span>
      </div>
      <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
  </div>
  <?php if(count($routers) > 0): ?>
  <div class="router-bar">
    <label>📡 Cabang:</label>
    <span class="router-dot" id="routerDot"></span>
    <select class="router-select" id="routerSelect" onchange="switchRouter(this.value)">
      <?php foreach($routers as $r): ?>
      <option value="<?=h($r['id'])?>" <?=($selectedRouter && $selectedRouter['id']===$r['id'])?'selected':''?>>
        <?=h($r['name'])?>
      </option>
      <?php endforeach; ?>
    </select>
    <span class="router-identity" id="routerIdent"></span>
  </div>
  <?php endif; ?>
</header>

<!-- ══════════ MAIN CONTENT ══════════ -->
<div class="main">

  <!-- BIG "Daftarkan" Button -->
  <div class="action-hero">
    <button class="btn-daftar" onclick="openAdd()">
      <span class="ico">➕</span>
      Daftarkan MAC Baru
    </button>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat" style="--c:var(--blue)">
      <div class="stat-n" id="sTotal">—</div>
      <div class="stat-l">Total</div>
    </div>
    <div class="stat" style="--c:var(--green)">
      <div class="stat-n" id="sActive">—</div>
      <div class="stat-l">Aktif</div>
    </div>
    <div class="stat" style="--c:var(--red)">
      <div class="stat-n" id="sDisabled">—</div>
      <div class="stat-l">Nonaktif</div>
    </div>
  </div>

  <!-- Search -->
  <div class="search-wrap">
    <input type="text" class="search-input" id="searchInput" placeholder="Cari nama atau MAC..." oninput="filterList()">
  </div>

  <!-- Loading State -->
  <div id="stateLoading" class="state-box state-loading">
    <div class="sico">📡</div>
    <div class="stitle">Mengambil data...</div>
    <div class="sdesc">Sedang menghubungi router MikroTik</div>
  </div>

  <!-- Empty State -->
  <div id="stateEmpty" class="state-box" style="display:none">
    <div class="sico">📭</div>
    <div class="stitle">Belum ada MAC terdaftar</div>
    <div class="sdesc">Tekan tombol <strong>"Daftarkan MAC Baru"</strong> di atas untuk memulai</div>
  </div>

  <!-- Error State -->
  <div id="stateError" class="state-box" style="display:none">
    <div class="sico">❌</div>
    <div class="stitle">Tidak bisa terhubung</div>
    <div class="sdesc" id="errorMsg">Periksa koneksi router MikroTik</div>
    <button class="btn-daftar" style="max-width:220px;margin:0 auto;font-size:.9rem;padding:12px" onclick="loadData()">🔄 Coba Lagi</button>
  </div>

  <!-- Card List -->
  <div class="card-list" id="cardList" style="display:none"></div>

</div>

<!-- Footer -->
<footer class="ftr">© <?=date('Y')?> MAC Registration — MikroTik Binding v1.0</footer>

<!-- ══════════ FAB: Settings (Admin only) ══════════ -->
<?php if(isAdmin()): ?>
<button class="btn-settings" onclick="openSettings()" title="Pengaturan">⚙️</button>
<?php endif; ?>

<!-- ══════════ MODAL: Daftarkan / Edit MAC ══════════ -->
<div class="modal-bg" id="mForm" onclick="if(event.target===this)closeModal('mForm')">
  <div class="modal-box">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <h2 id="formTitle">➕ Daftarkan MAC Baru</h2>
      <p id="formDesc">Masukkan MAC address dan nama perangkat</p>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="field">
        <label>MAC Address</label>
        <input type="text" id="macInput" placeholder="Contoh: AA:BB:CC:DD:EE:FF" maxlength="17" autocomplete="off" inputmode="text">
        <div class="hint">📌 Alamat MAC perangkat (bisa dilihat di stiker router/HP)</div>
      </div>
      <div class="field">
        <label>Nama</label>
        <input type="text" id="nameInput" placeholder="Contoh: Rumah Pak Budi" maxlength="100">
        <div class="hint">📝 Nama pelanggan atau keterangan perangkat</div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeModal('mForm')">Batal</button>
      <button class="btn-save" id="btnSave" onclick="submitForm()">💾 Simpan</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: Hapus ══════════ -->
<div class="modal-bg" id="mDel" onclick="if(event.target===this)closeModal('mDel')">
  <div class="modal-box">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <h2>🗑️ Hapus MAC?</h2>
      <p>MAC ini akan dihapus dari router</p>
    </div>
    <div class="modal-body">
      <div class="del-info">
        <div class="del-mac" id="delMac">—</div>
        <div class="del-name" id="delName">—</div>
      </div>
      <p style="font-size:.8rem;color:var(--red);font-weight:600;text-align:center;margin-top:8px">⚠️ Tindakan ini tidak bisa dibatalkan</p>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeModal('mDel')">Batal</button>
      <button class="btn-delete" id="btnDel" onclick="confirmDelete()">🗑️ Ya, Hapus</button>
    </div>
  </div>
</div>

<!-- ══════════ MODAL: Settings (Admin) ══════════ -->
<?php if(isAdmin()): ?>
<div class="modal-bg settings-modal" id="mSettings" onclick="if(event.target===this)closeModal('mSettings')">
  <div class="modal-box">
    <div class="modal-handle"></div>
    <div class="modal-head">
      <h2>⚙️ Pengaturan</h2>
      <p>Kelola router dan pengguna</p>
    </div>
    <div class="modal-body" style="padding-bottom:20px">
      <div class="stab-bar">
        <button class="stab on" onclick="switchSettingsTab('routers',this)">📡 Router</button>
        <button class="stab" onclick="switchSettingsTab('users',this)">👥 Pengguna</button>
      </div>

      <!-- Router Tab -->
      <div id="sTabRouters">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <span style="font-size:.82rem;font-weight:700;color:var(--g700)">Daftar Router</span>
          <button class="sbtn add" onclick="showRouterForm()">➕ Tambah</button>
        </div>
        <div class="slist" id="routerList"></div>
        <div id="routerFormWrap" style="display:none">
          <div class="sform">
            <div class="sform-title" id="rfTitle">Tambah Router</div>
            <input type="hidden" id="rId">
            <div class="srow"><div class="sfield"><label>Nama Cabang</label><input type="text" id="rName" placeholder="Router Pusat"></div>
            <div class="sfield"><label>Host / IP</label><input type="text" id="rHost" placeholder="192.168.1.1"></div></div>
            <div class="srow"><div class="sfield"><label>Port API</label><input type="number" id="rPort" value="8728"></div>
            <div class="sfield"><label>Username</label><input type="text" id="rUser" value="admin"></div></div>
            <div class="sfield"><label>Password</label><input type="password" id="rPass" placeholder="Password MikroTik"></div>
            <div class="sform-acts">
              <button class="sbtn" onclick="hideEl('routerFormWrap')">Batal</button>
              <button class="sbtn add" onclick="saveRouter()">💾 Simpan</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Users Tab -->
      <div id="sTabUsers" style="display:none">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
          <span style="font-size:.82rem;font-weight:700;color:var(--g700)">Daftar Pengguna</span>
          <button class="sbtn add" onclick="showUserForm()">➕ Tambah</button>
        </div>
        <div class="slist" id="userList"></div>
        <div id="userFormWrap" style="display:none">
          <div class="sform">
            <div class="sform-title" id="ufTitle">Tambah Pengguna</div>
            <div class="srow"><div class="sfield"><label>Username</label><input type="text" id="uUser" placeholder="username"></div>
            <div class="sfield"><label>Nama Lengkap</label><input type="text" id="uName" placeholder="Nama"></div></div>
            <div class="srow"><div class="sfield"><label>Password</label><input type="password" id="uPass" placeholder="Password"><div class="hint" style="font-size:.62rem;margin-top:3px">Kosongkan jika tidak ubah</div></div>
            <div class="sfield"><label>Peran</label><select id="uRole" style="padding:9px 12px;border:1.5px solid var(--g200);border-radius:8px;font-family:inherit;font-size:.84rem;background:var(--card-bg)"><option value="admin">Admin</option><option value="teknisi" selected>Teknisi</option></select></div></div>
            <input type="hidden" id="uEdit">
            <div class="sform-acts">
              <button class="sbtn" onclick="hideEl('userFormWrap')">Batal</button>
              <button class="sbtn add" onclick="saveUser()">💾 Simpan</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ══════════ TOAST ══════════ -->
<div class="toast-area" id="toastArea"></div>

<!-- ══════════ JAVASCRIPT ══════════ -->
<script>
const API = 'api.php';
let allData = [];
let deleteId = '';

// ── INIT ──
document.addEventListener('DOMContentLoaded', () => {
  loadData();
  checkRouter();

  // Auto-format MAC input
  document.getElementById('macInput').addEventListener('input', function(e) {
    let v = this.value.replace(/[^A-Fa-f0-9]/g, '').toUpperCase();
    let f = '';
    for (let i = 0; i < v.length && i < 12; i++) {
      if (i > 0 && i % 2 === 0) f += ':';
      f += v[i];
    }
    this.value = f;
  });
});

// ── CHECK ROUTER CONNECTION ──
async function checkRouter() {
  const dot = document.getElementById('routerDot');
  const ident = document.getElementById('routerIdent');
  if (!dot) return;
  try {
    const r = await fetch(`${API}?action=identity`);
    const d = await r.json();
    if (d.success) {
      dot.className = 'router-dot on';
      if (ident) ident.textContent = d.identity;
    } else throw new Error();
  } catch(e) {
    dot.className = 'router-dot off';
    if (ident) ident.textContent = '';
  }
}

// ── SWITCH ROUTER ──
async function switchRouter(id) {
  const fd = new FormData();
  fd.append('action', 'select-router');
  fd.append('router_id', id);
  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast(d.message, 'ok'); loadData(); checkRouter(); }
  } catch(e) { toast('Gagal ganti router', 'err'); }
}

// ── LOAD DATA ──
async function loadData() {
  showEl('stateLoading'); hideEl('cardList'); hideEl('stateEmpty'); hideEl('stateError');
  try {
    const r = await fetch(`${API}?action=list`);
    const d = await r.json();
    hideEl('stateLoading');
    if (!d.success) throw new Error(d.message);
    allData = d.data;
    updateStats();
    if (allData.length === 0) { showEl('stateEmpty'); }
    else { showEl('cardList'); renderCards(allData); }
    checkRouter();
  } catch(e) {
    hideEl('stateLoading');
    showEl('stateError');
    document.getElementById('errorMsg').textContent = e.message;
  }
}

// ── RENDER CARD LIST ──
function renderCards(data) {
  const el = document.getElementById('cardList');
  el.innerHTML = data.map((b, i) => {
    const initials = (b.comment || '?').substring(0,2).toUpperCase();
    return `
    <div class="bind-card" style="animation-delay:${i*0.04}s">
      <div class="bind-card-body">
        <div class="bind-avatar">${esc(initials)}</div>
        <div class="bind-info">
          <div class="bind-name">${esc(b.comment || '(Tanpa Nama)')}</div>
          <div class="bind-mac">${esc(b.mac)}</div>
          <div>
            <span class="bind-badge bypass"><span class="bind-badge-dot"></span> BYPASS</span>
            ${b.disabled
              ? '<span class="bind-badge nonaktif"><span class="bind-badge-dot"></span> NONAKTIF</span>'
              : '<span class="bind-badge aktif"><span class="bind-badge-dot"></span> AKTIF</span>'
            }
          </div>
        </div>
      </div>
      <div class="bind-actions">
        <button class="act-btn edit-btn" onclick="openEdit('${ea(b.id)}','${ea(b.mac)}','${ea(b.comment)}')">
          <span class="act-ico">✏️</span> Ubah
        </button>
        <button class="act-btn del-btn" onclick="openDel('${ea(b.id)}','${ea(b.mac)}','${ea(b.comment)}')">
          <span class="act-ico">🗑️</span> Hapus
        </button>
      </div>
    </div>`;
  }).join('');
}

// ── STATS ──
function updateStats() {
  anim('sTotal', allData.length);
  anim('sActive', allData.filter(b => !b.disabled).length);
  anim('sDisabled', allData.filter(b => b.disabled).length);
}
function anim(id, target) {
  const el = document.getElementById(id);
  const from = parseInt(el.textContent) || 0;
  const start = performance.now();
  (function step(ts) {
    const p = Math.min((ts - start) / 400, 1);
    el.textContent = Math.round(from + (target - from) * (1 - Math.pow(1 - p, 3)));
    if (p < 1) requestAnimationFrame(step);
  })(performance.now());
}

// ── FILTER / SEARCH ──
function filterList() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  const filtered = q ? allData.filter(b =>
    (b.mac||'').toLowerCase().includes(q) || (b.comment||'').toLowerCase().includes(q)
  ) : allData;
  if (filtered.length === 0 && allData.length > 0) {
    hideEl('cardList'); showEl('stateEmpty');
    document.querySelector('#stateEmpty .stitle').textContent = 'Tidak ditemukan';
    document.querySelector('#stateEmpty .sdesc').innerHTML = 'Tidak ada yang cocok dengan <strong>"' + esc(q) + '"</strong>';
  } else if (allData.length === 0) {
    hideEl('cardList'); showEl('stateEmpty');
    document.querySelector('#stateEmpty .stitle').textContent = 'Belum ada MAC terdaftar';
    document.querySelector('#stateEmpty .sdesc').innerHTML = 'Tekan tombol <strong>"Daftarkan MAC Baru"</strong> di atas untuk memulai';
  } else {
    hideEl('stateEmpty'); showEl('cardList');
    renderCards(filtered);
  }
}

// ── OPEN ADD FORM ──
function openAdd() {
  document.getElementById('editId').value = '';
  document.getElementById('macInput').value = '';
  document.getElementById('nameInput').value = '';
  document.getElementById('formTitle').textContent = '➕ Daftarkan MAC Baru';
  document.getElementById('formDesc').textContent = 'Masukkan MAC address dan nama perangkat';
  document.getElementById('btnSave').textContent = '💾 Daftarkan';
  openModal('mForm');
  setTimeout(() => document.getElementById('macInput').focus(), 350);
}

// ── OPEN EDIT FORM ──
function openEdit(id, mac, name) {
  document.getElementById('editId').value = id;
  document.getElementById('macInput').value = mac;
  document.getElementById('nameInput').value = name;
  document.getElementById('formTitle').textContent = '✏️ Ubah Data MAC';
  document.getElementById('formDesc').textContent = 'Perbarui MAC address atau nama';
  document.getElementById('btnSave').textContent = '💾 Simpan Perubahan';
  openModal('mForm');
}

// ── SUBMIT FORM (Add / Edit) ──
async function submitForm() {
  const id = document.getElementById('editId').value;
  const mac = document.getElementById('macInput').value.trim();
  const name = document.getElementById('nameInput').value.trim();

  if (!mac) { toast('MAC address harus diisi', 'err'); document.getElementById('macInput').focus(); return; }
  if (mac.replace(/:/g,'').length !== 12) { toast('MAC address belum lengkap (harus 12 karakter)', 'err'); return; }
  if (!name) { toast('Nama harus diisi', 'err'); document.getElementById('nameInput').focus(); return; }

  const btn = document.getElementById('btnSave');
  btn.disabled = true;
  const origText = btn.textContent;
  btn.textContent = '⏳ Menyimpan...';

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'add');
  fd.append('mac', mac);
  fd.append('name', name);
  if (id) fd.append('id', id);

  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) {
      toast(id ? 'Data berhasil diubah ✅' : 'MAC berhasil didaftarkan ✅', 'ok');
      closeModal('mForm');
      loadData();
    } else {
      toast(d.message, 'err');
    }
  } catch(e) { toast('Gagal menyimpan: ' + e.message, 'err'); }
  finally { btn.disabled = false; btn.textContent = origText; }
}

// ── OPEN DELETE CONFIRM ──
function openDel(id, mac, name) {
  deleteId = id;
  document.getElementById('delMac').textContent = mac;
  document.getElementById('delName').textContent = name || '(tanpa nama)';
  openModal('mDel');
}

// ── CONFIRM DELETE ──
async function confirmDelete() {
  if (!deleteId) return;
  const btn = document.getElementById('btnDel');
  btn.disabled = true;
  btn.textContent = '⏳ Menghapus...';

  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', deleteId);

  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast('MAC berhasil dihapus ✅', 'ok'); closeModal('mDel'); loadData(); }
    else toast(d.message, 'err');
  } catch(e) { toast('Gagal menghapus', 'err'); }
  finally { btn.disabled = false; btn.textContent = '🗑️ Ya, Hapus'; deleteId = ''; }
}

// ── MODAL HELPERS ──
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

// ── TOAST ──
function toast(msg, type='ok') {
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.textContent = msg;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(-10px)'; el.style.transition = '.3s'; setTimeout(() => el.remove(), 300); }, 3500);
}

// ── SETTINGS (Admin) ──
function openSettings() {
  openModal('mSettings');
  loadRouterList();
}
function switchSettingsTab(tab, btn) {
  document.querySelectorAll('.stab').forEach(t => t.classList.remove('on'));
  btn.classList.add('on');
  document.getElementById('sTabRouters').style.display = tab === 'routers' ? '' : 'none';
  document.getElementById('sTabUsers').style.display = tab === 'users' ? '' : 'none';
  if (tab === 'routers') loadRouterList();
  if (tab === 'users') loadUserList();
}

async function loadRouterList() {
  const el = document.getElementById('routerList');
  if (!el) return;
  try {
    const r = await fetch(`${API}?action=routers`);
    const d = await r.json();
    if (!d.data || d.data.length === 0) { el.innerHTML = '<div style="text-align:center;padding:20px;color:var(--g400)">Belum ada router</div>'; return; }
    el.innerHTML = d.data.map(r => `
      <div class="sitem">
        <div class="sitem-info">
          <div class="sitem-name">${esc(r.name)} ${r.selected?'<span style="background:#DCFCE7;color:#15803D;font-size:.58rem;padding:1px 6px;border-radius:8px;font-weight:700">AKTIF</span>':''}</div>
          <div class="sitem-detail">${esc(r.host)}</div>
        </div>
        <div class="sitem-acts">
          <button class="sbtn" onclick="editRouter('${ea(r.id)}')">✏️</button>
          <button class="sbtn del" onclick="deleteRouter('${ea(r.id)}')">🗑️</button>
        </div>
      </div>`).join('');
  } catch(e) { el.innerHTML = '<div style="color:var(--red);font-size:.82rem">Gagal memuat</div>'; }
}

function showRouterForm(edit) {
  showEl('routerFormWrap');
  document.getElementById('rfTitle').textContent = edit ? 'Ubah Router' : 'Tambah Router';
  if (!edit) { ['rId','rName','rHost','rPass'].forEach(id => document.getElementById(id).value = ''); document.getElementById('rPort').value = '8728'; document.getElementById('rUser').value = 'admin'; }
}
async function editRouter(id) {
  showRouterForm(true);
  document.getElementById('rId').value = id;
  try {
    const r = await fetch(`${API}?action=routers`);
    const d = await r.json();
    const rt = d.data?.find(x => x.id === id);
    if (rt) { document.getElementById('rName').value = rt.name; document.getElementById('rHost').value = rt.host; }
  } catch(e) {}
}
async function saveRouter() {
  const fd = new FormData();
  fd.append('action', 'save-router');
  fd.append('router_id', document.getElementById('rId').value);
  fd.append('router_name', document.getElementById('rName').value);
  fd.append('router_host', document.getElementById('rHost').value);
  fd.append('router_port', document.getElementById('rPort').value);
  fd.append('router_user', document.getElementById('rUser').value);
  fd.append('router_pass', document.getElementById('rPass').value);
  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast(d.message, 'ok'); hideEl('routerFormWrap'); loadRouterList(); setTimeout(() => location.reload(), 800); }
    else toast(d.message, 'err');
  } catch(e) { toast('Gagal simpan', 'err'); }
}
async function deleteRouter(id) {
  if (!confirm('Hapus router ini?')) return;
  const fd = new FormData();
  fd.append('action', 'delete-router');
  fd.append('router_id', id);
  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast(d.message, 'ok'); loadRouterList(); setTimeout(() => location.reload(), 800); }
    else toast(d.message, 'err');
  } catch(e) { toast('Gagal', 'err'); }
}

async function loadUserList() {
  const el = document.getElementById('userList');
  if (!el) return;
  try {
    const r = await fetch(`${API}?action=users`);
    const d = await r.json();
    if (!d.data) { el.innerHTML = ''; return; }
    el.innerHTML = d.data.map(u => `
      <div class="sitem">
        <div class="sitem-info">
          <div class="sitem-name">${esc(u.full_name)}</div>
          <div class="sitem-detail">${esc(u.username)} · <span style="font-weight:700;color:${u.role==='admin'?'var(--blue)':'var(--green)'}">${u.role.toUpperCase()}</span></div>
        </div>
        <div class="sitem-acts">
          <button class="sbtn" onclick="editUser('${ea(u.username)}','${ea(u.full_name)}','${ea(u.role)}')">✏️</button>
          <button class="sbtn del" onclick="deleteUser('${ea(u.username)}')">🗑️</button>
        </div>
      </div>`).join('');
  } catch(e) { el.innerHTML = '<div style="color:var(--red);font-size:.82rem">Gagal memuat</div>'; }
}

function showUserForm(edit) {
  showEl('userFormWrap');
  document.getElementById('ufTitle').textContent = edit ? 'Ubah Pengguna' : 'Tambah Pengguna';
  if (!edit) { ['uUser','uName','uPass'].forEach(id => document.getElementById(id).value = ''); document.getElementById('uRole').value = 'teknisi'; document.getElementById('uEdit').value = ''; document.getElementById('uUser').readOnly = false; }
}
function editUser(username, name, role) {
  showUserForm(true);
  document.getElementById('uUser').value = username;
  document.getElementById('uUser').readOnly = true;
  document.getElementById('uName').value = name;
  document.getElementById('uRole').value = role;
  document.getElementById('uPass').value = '';
  document.getElementById('uEdit').value = '1';
}
async function saveUser() {
  const fd = new FormData();
  fd.append('action', 'save-user');
  fd.append('user_username', document.getElementById('uUser').value);
  fd.append('user_fullname', document.getElementById('uName').value);
  fd.append('user_role', document.getElementById('uRole').value);
  fd.append('user_password', document.getElementById('uPass').value);
  fd.append('user_edit', document.getElementById('uEdit').value);
  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast(d.message, 'ok'); hideEl('userFormWrap'); loadUserList(); }
    else toast(d.message, 'err');
  } catch(e) { toast('Gagal simpan', 'err'); }
}
async function deleteUser(username) {
  if (!confirm('Hapus pengguna "' + username + '"?')) return;
  const fd = new FormData();
  fd.append('action', 'delete-user');
  fd.append('username', username);
  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { toast(d.message, 'ok'); loadUserList(); }
    else toast(d.message, 'err');
  } catch(e) { toast('Gagal', 'err'); }
}

// ── UTILS ──
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function ea(s) { return (s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function showEl(id) { document.getElementById(id).style.display = ''; }
function hideEl(id) { document.getElementById(id).style.display = 'none'; }
</script>

</body>
</html>
