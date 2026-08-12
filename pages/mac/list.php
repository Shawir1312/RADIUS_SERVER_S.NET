<?php
require_once __DIR__ . '/../../include/config.php';
auth_require();

$show_router_filter = true;
$page_title = 'Daftar MAC (Bypass)';
$router_id = (int)get('router_id');

include __DIR__ . '/../../include/header.php';
?>

<style>
/* ══════════════════════════════════════════════════════════════════
   MAC REGISTRATION THEME (Adapted for Dashboard)
   ══════════════════════════════════════════════════════════════════ */
:root{
  --mac-red:#D42B2B;--mac-red-d:#A51C1C;
  --mac-blue:#1B3FA6;--mac-blue-d:#122B7A;--mac-blue-m:#1E4DBF;
  --mac-green:#16A34A;
  --mac-g50:#F8FAFF;--mac-g100:#F0F3FA;--mac-g200:#E0E6F5;--mac-g300:#C8D3EC;
  --mac-g400:#8A95B8;--mac-g500:#6270A0;--mac-g600:#5A6490;--mac-g700:#3A4468;--mac-g900:#1A2040;
  --mac-card-bg:#fff;--mac-card-border:var(--mac-g200);
}

.mac-main {
    max-width: 900px;
    margin: 0 auto;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--mac-g700);
}

/* ── BIG ACTION BUTTON — "Daftarkan MAC Baru" ── */
.btn-daftar{
  display:flex;align-items:center;justify-content:center;gap:10px;
  width:100%;padding:18px 20px;border:none;border-radius:14px;
  background:linear-gradient(135deg,var(--mac-blue),var(--mac-blue-d));
  color:#fff;font-family:inherit;font-size:1.05rem;font-weight:800;
  cursor:pointer;transition:all .2s;
  box-shadow:0 4px 16px rgba(27,63,166,.35);
  position:relative;overflow:hidden;
  letter-spacing:-.01em;
  margin-bottom: 20px;
}
.btn-daftar:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(27,63,166,.45);color:#fff;}
.btn-daftar:active{transform:scale(.98)}
.btn-daftar .ico{font-size:1.4rem}
.btn-daftar::after{content:'';position:absolute;top:0;right:0;bottom:0;width:6px;background:var(--mac-red);border-radius:0 14px 14px 0}

/* ── STATS (compact row) ── */
.mac-stats{display:grid;grid-template-columns:repeat(3, 1fr);gap:12px;margin-bottom:20px}
.mac-stat{background:var(--mac-card-bg);border-radius:12px;padding:16px 14px;border:1px solid var(--mac-card-border);text-align:center;position:relative;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.mac-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--c,var(--mac-blue))}
.mac-stat-n{font-size:1.8rem;font-weight:900;color:var(--mac-g900);line-height:1}
.mac-stat-l{font-size:.65rem;font-weight:700;color:var(--mac-g400);text-transform:uppercase;letter-spacing:.5px;margin-top:5px}

/* ── SEARCH BAR ── */
.mac-search-wrap{margin-bottom:16px}
.mac-search-input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--mac-g200);border-radius:12px;font-family:inherit;font-size:.95rem;color:var(--mac-g700);background:var(--mac-card-bg);outline:none;transition:.2s;
  background-image:url("data:image/svg+xml,%3Csvg width='18' height='18' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='11' cy='11' r='7' stroke='%238A95B8' stroke-width='2'/%3E%3Cpath d='M16 16l4 4' stroke='%238A95B8' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:14px center}
.mac-search-input:focus{border-color:var(--mac-blue);box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.mac-search-input::placeholder{color:var(--mac-g400)}

/* ── BINDING CARDS ── */
.mac-card-list{display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:14px}
.bind-card{
  background:var(--mac-card-bg);border:1px solid var(--mac-card-border);border-radius:14px;
  overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);transition:.2s;
  animation:cardIn .3s ease both;
}
.bind-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
@keyframes cardIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}

.bind-card-body{padding:16px 20px;display:flex;align-items:center;gap:14px}
.bind-avatar{
  width:46px;height:46px;border-radius:12px;flex-shrink:0;
  background:linear-gradient(135deg,var(--mac-blue),var(--mac-blue-m));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:1.2rem;font-weight:800;
}
.bind-info{flex:1;min-width:0}
.bind-name{font-size:.95rem;font-weight:700;color:var(--mac-g900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bind-mac{font-family:'SF Mono','JetBrains Mono','Fira Code',monospace;font-size:.8rem;color:var(--mac-blue-d);font-weight:600;letter-spacing:.02em;margin-top:2px}
.bind-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-top:6px}
.bind-badge.aktif{background:#DCFCE7;color:#15803D}
.bind-badge.nonaktif{background:#FEE2E2;color:var(--mac-red)}
.bind-badge.bypass{background:#DBEAFE;color:#1D4ED8}
.bind-badge-dot{width:5px;height:5px;border-radius:50%;background:currentColor}

/* ── ACTION BUTTONS ── */
.bind-actions{display:flex;border-top:1px solid var(--mac-g100)}
.bind-actions .act-btn{
  flex:1;display:flex;align-items:center;justify-content:center;gap:6px;
  padding:14px 10px;border:none;background:none;
  font-family:inherit;font-size:.8rem;font-weight:700;
  cursor:pointer;transition:.15s;color:var(--mac-g500);
}
.bind-actions .act-btn:first-child{border-right:1px solid var(--mac-g100)}
.bind-actions .act-btn.edit-btn:hover{background:#EFF6FF;color:var(--mac-blue)}
.bind-actions .act-btn.del-btn:hover{background:#FEF2F2;color:var(--mac-red)}

/* ── STATES ── */
.mac-state-box{text-align:center;padding:60px 20px;color:var(--mac-g400);background:var(--mac-card-bg);border-radius:14px;border:1px dashed var(--mac-card-border)}
.mac-state-box .sico{font-size:3rem;margin-bottom:12px}
.mac-state-box .stitle{font-size:1.1rem;font-weight:700;color:var(--mac-g600);margin-bottom:6px}
.mac-state-box .sdesc{font-size:.9rem;line-height:1.5;margin-bottom:16px}
.state-loading .sico{animation:macpulse 1s infinite}
@keyframes macpulse{0%,100%{opacity:1}50%{opacity:.4}}

/* ── MODAL ── */
.mac-modal-bg{display:none;position:fixed;inset:0;background:rgba(18,43,122,.55);z-index:2000;align-items:center;justify-content:center;backdrop-filter:blur(3px);padding:20px}
.mac-modal-bg.show{display:flex}
.mac-modal-box{
  background:var(--mac-card-bg);width:100%;max-width:500px;
  border-radius:20px;
  box-shadow:0 10px 40px rgba(18,43,122,.3);
  animation:slideUp .3s cubic-bezier(.4,0,.2,1);
  position:relative;
}
.mac-modal-head{padding:20px 24px 14px;text-align:center;border-bottom:1px solid var(--mac-g100)}
.mac-modal-head h2{font-size:1.25rem;font-weight:800;color:var(--mac-g900);margin:0}
.mac-modal-head p{font-size:.85rem;color:var(--mac-g400);margin:4px 0 0}
.mac-modal-body{padding:20px 24px}
.mac-modal-foot{padding:16px 24px 24px;display:flex;gap:12px;background:var(--mac-g50);border-radius:0 0 20px 20px}

.mac-btn-cancel{flex:1;padding:14px;border:1.5px solid var(--mac-g200);border-radius:12px;background:var(--mac-card-bg);font-family:inherit;font-size:.95rem;font-weight:700;color:var(--mac-g600);cursor:pointer;transition:.2s}
.mac-btn-cancel:hover{background:var(--mac-g50);border-color:var(--mac-g300)}
.mac-btn-save{flex:2;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--mac-blue),var(--mac-blue-d));font-family:inherit;font-size:.95rem;font-weight:800;color:#fff;cursor:pointer;transition:.2s;box-shadow:0 3px 12px rgba(27,63,166,.3)}
.mac-btn-save:hover{box-shadow:0 5px 18px rgba(27,63,166,.4);transform:translateY(-1px)}
.mac-btn-save:disabled{opacity:.5;pointer-events:none}
.mac-btn-delete{flex:2;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,var(--mac-red),var(--mac-red-d));font-family:inherit;font-size:.95rem;font-weight:800;color:#fff;cursor:pointer;transition:.2s;box-shadow:0 3px 12px rgba(212,43,43,.3)}
.mac-btn-delete:hover{box-shadow:0 5px 18px rgba(212,43,43,.4)}

.mac-field{margin-bottom:16px}
.mac-field label{display:block;font-size:.75rem;font-weight:700;color:var(--mac-g600);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px}
.mac-field input{width:100%;padding:14px 16px;border:1.5px solid var(--mac-g200);border-radius:12px;font-family:inherit;font-size:1.05rem;color:var(--mac-g700);background:var(--mac-g50);outline:none;transition:.2s}
.mac-field input:focus{border-color:var(--mac-blue);background:#fff;box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.mac-field .hint{font-size:.75rem;color:var(--mac-g400);margin-top:6px;line-height:1.4}

.del-info{background:var(--mac-g50);border:1px solid var(--mac-g200);border-radius:12px;padding:16px;margin:10px 0}
.del-mac{font-family:'SF Mono','JetBrains Mono',monospace;font-size:1rem;font-weight:700;color:var(--mac-blue-d)}
.del-name{font-size:.9rem;color:var(--mac-g600);margin-top:4px}
</style>

<div class="mac-main">
  <?php if (empty($router_id)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i> Silakan pilih Router dari opsi dropdown di bagian atas.
    </div>
  <?php else: ?>

    <!-- Action Hero -->
    <button class="btn-daftar" onclick="openAdd()">
      <span class="ico">➕</span>
      Daftarkan MAC Baru
    </button>

    <!-- Stats -->
    <div class="mac-stats">
      <div class="mac-stat" style="--c:var(--mac-blue)">
        <div class="mac-stat-n" id="sTotal">—</div>
        <div class="mac-stat-l">Total</div>
      </div>
      <div class="mac-stat" style="--c:var(--mac-green)">
        <div class="mac-stat-n" id="sActive">—</div>
        <div class="mac-stat-l">Aktif</div>
      </div>
      <div class="mac-stat" style="--c:var(--mac-red)">
        <div class="mac-stat-n" id="sDisabled">—</div>
        <div class="mac-stat-l">Nonaktif</div>
      </div>
    </div>

    <!-- Search -->
    <div class="mac-search-wrap">
      <input type="text" class="mac-search-input" id="searchInput" placeholder="Cari nama atau MAC..." oninput="filterList()">
    </div>

    <!-- States -->
    <div id="stateLoading" class="mac-state-box state-loading">
      <div class="sico">📡</div>
      <div class="stitle">Mengambil data...</div>
      <div class="sdesc">Sedang menghubungi router MikroTik</div>
    </div>

    <div id="stateEmpty" class="mac-state-box" style="display:none">
      <div class="sico">📭</div>
      <div class="stitle">Belum ada MAC terdaftar</div>
      <div class="sdesc">Tekan tombol <strong>"Daftarkan MAC Baru"</strong> di atas untuk memulai</div>
    </div>

    <div id="stateError" class="mac-state-box" style="display:none">
      <div class="sico">❌</div>
      <div class="stitle">Tidak bisa terhubung</div>
      <div class="sdesc" id="errorMsg">Periksa koneksi router MikroTik</div>
      <button class="btn btn-primary mt-3" onclick="loadData()">🔄 Coba Lagi</button>
    </div>

    <!-- Card List -->
    <div class="mac-card-list" id="cardList" style="display:none"></div>

  <?php endif; ?>
</div>

<!-- ══════════ MODALS ══════════ -->
<?php if (!empty($router_id)): ?>
<!-- Add/Edit Modal -->
<div class="mac-modal-bg" id="mForm" onclick="if(event.target===this)closeModal('mForm')">
  <div class="mac-modal-box">
    <div class="mac-modal-head">
      <h2 id="formTitle">➕ Daftarkan MAC Baru</h2>
      <p id="formDesc">Masukkan MAC address dan nama perangkat</p>
    </div>
    <div class="mac-modal-body">
      <input type="hidden" id="editId">
      <div class="mac-field">
        <label>MAC Address</label>
        <input type="text" id="macInput" placeholder="Contoh: AA:BB:CC:DD:EE:FF" maxlength="17" autocomplete="off" inputmode="text">
        <div class="hint">📌 Alamat MAC perangkat (bisa dilihat di stiker router/HP)</div>
      </div>
      <div class="mac-field">
        <label>Nama / Keterangan</label>
        <input type="text" id="nameInput" placeholder="Contoh: Rumah Pak Budi" maxlength="100">
        <div class="hint">📝 Nama pelanggan atau keterangan perangkat</div>
      </div>
    </div>
    <div class="mac-modal-foot">
      <button class="mac-btn-cancel" onclick="closeModal('mForm')">Batal</button>
      <button class="mac-btn-save" id="btnSave" onclick="submitForm()">💾 Simpan</button>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="mac-modal-bg" id="mDel" onclick="if(event.target===this)closeModal('mDel')">
  <div class="mac-modal-box">
    <div class="mac-modal-head">
      <h2>🗑️ Hapus MAC?</h2>
      <p>MAC ini akan dihapus dari router</p>
    </div>
    <div class="mac-modal-body">
      <div class="del-info">
        <div class="del-mac" id="delMac">—</div>
        <div class="del-name" id="delName">—</div>
      </div>
      <p style="font-size:.85rem;color:var(--mac-red);font-weight:600;text-align:center;margin-top:12px">⚠️ Tindakan ini tidak bisa dibatalkan</p>
    </div>
    <div class="mac-modal-foot">
      <button class="mac-btn-cancel" onclick="closeModal('mDel')">Batal</button>
      <button class="mac-btn-delete" id="btnDel" onclick="confirmDelete()">🗑️ Ya, Hapus</button>
    </div>
  </div>
</div>

<!-- Toast Container using Bootstrap Toasts (integrated with Radius theme) -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999" id="toastArea"></div>

<script>
const ROUTER_ID = <?= $router_id ?>;
const API = '/ajax/api_mac.php';
let allData = [];
let deleteId = '';

document.addEventListener('DOMContentLoaded', () => {
  loadData();
  
  const macInput = document.getElementById('macInput');
  if (macInput) {
      macInput.addEventListener('input', function(e) {
        let v = this.value.replace(/[^A-Fa-f0-9]/g, '').toUpperCase();
        let f = '';
        for (let i = 0; i < v.length && i < 12; i++) {
          if (i > 0 && i % 2 === 0) f += ':';
          f += v[i];
        }
        this.value = f;
      });
  }
});

async function loadData() {
  showEl('stateLoading'); hideEl('cardList'); hideEl('stateEmpty'); hideEl('stateError');
  try {
    const r = await fetch(`${API}?action=list&router_id=${ROUTER_ID}`);
    const d = await r.json();
    hideEl('stateLoading');
    if (!d.success) throw new Error(d.message);
    allData = d.data;
    updateStats();
    if (allData.length === 0) { showEl('stateEmpty'); }
    else { showEl('cardList'); renderCards(allData); }
  } catch(e) {
    hideEl('stateLoading');
    showEl('stateError');
    document.getElementById('errorMsg').textContent = e.message;
  }
}

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
          <span>✏️</span> Ubah
        </button>
        <button class="act-btn del-btn" onclick="openDel('${ea(b.id)}','${ea(b.mac)}','${ea(b.comment)}')">
          <span>🗑️</span> Hapus
        </button>
      </div>
    </div>`;
  }).join('');
}

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

function openEdit(id, mac, name) {
  document.getElementById('editId').value = id;
  document.getElementById('macInput').value = mac;
  document.getElementById('nameInput').value = name;
  document.getElementById('formTitle').textContent = '✏️ Ubah Data MAC';
  document.getElementById('formDesc').textContent = 'Perbarui MAC address atau nama';
  document.getElementById('btnSave').textContent = '💾 Simpan Perubahan';
  openModal('mForm');
}

async function submitForm() {
  const id = document.getElementById('editId').value;
  const mac = document.getElementById('macInput').value.trim();
  const name = document.getElementById('nameInput').value.trim();

  if (!mac) { btoast('MAC address harus diisi', 'danger'); document.getElementById('macInput').focus(); return; }
  if (mac.replace(/:/g,'').length !== 12) { btoast('MAC address belum lengkap (harus 12 karakter)', 'warning'); return; }
  if (!name) { btoast('Nama harus diisi', 'danger'); document.getElementById('nameInput').focus(); return; }

  const btn = document.getElementById('btnSave');
  btn.disabled = true;
  const origText = btn.textContent;
  btn.textContent = '⏳ Menyimpan...';

  const fd = new FormData();
  fd.append('action', id ? 'update' : 'add');
  fd.append('router_id', ROUTER_ID);
  fd.append('mac', mac);
  fd.append('name', name);
  if (id) fd.append('id', id);

  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) {
      btoast(id ? 'Data berhasil diubah' : 'MAC berhasil didaftarkan', 'success');
      closeModal('mForm');
      loadData();
    } else {
      btoast(d.message, 'danger');
    }
  } catch(e) { btoast('Gagal menyimpan: ' + e.message, 'danger'); }
  finally { btn.disabled = false; btn.textContent = origText; }
}

function openDel(id, mac, name) {
  deleteId = id;
  document.getElementById('delMac').textContent = mac;
  document.getElementById('delName').textContent = name || '(tanpa nama)';
  openModal('mDel');
}

async function confirmDelete() {
  if (!deleteId) return;
  const btn = document.getElementById('btnDel');
  btn.disabled = true;
  btn.textContent = '⏳ Menghapus...';

  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('router_id', ROUTER_ID);
  fd.append('id', deleteId);

  try {
    const r = await fetch(API, {method:'POST', body:fd});
    const d = await r.json();
    if (d.success) { btoast('MAC berhasil dihapus', 'success'); closeModal('mDel'); loadData(); }
    else btoast(d.message, 'danger');
  } catch(e) { btoast('Gagal menghapus', 'danger'); }
  finally { btn.disabled = false; btn.textContent = '🗑️ Ya, Hapus'; deleteId = ''; }
}

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('show'); }

// Bootstrap Toast wrapper
function btoast(msg, type='success') {
  const bg = type === 'success' ? 'text-bg-success' : (type === 'danger' ? 'text-bg-danger' : 'text-bg-warning');
  const html = `
    <div class="toast align-items-center ${bg} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body text-white">
          ${msg}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  `;
  const tArea = document.getElementById('toastArea');
  const div = document.createElement('div');
  div.innerHTML = html;
  const tEl = div.firstElementChild;
  tArea.appendChild(tEl);
  setTimeout(() => {
      tEl.classList.remove('show');
      setTimeout(() => tEl.remove(), 300);
  }, 4000);
}

function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function ea(s) { return (s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function showEl(id) { document.getElementById(id).style.display = ''; }
function hideEl(id) { document.getElementById(id).style.display = 'none'; }
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../include/footer.php'; ?>
