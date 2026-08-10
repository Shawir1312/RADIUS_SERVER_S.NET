/**
 * S.NET RADIUS Manager — Main JavaScript
 */
'use strict';

// ── Sidebar Toggle ─────────────────────────────────────
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

function toggleSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
    document.body.classList.toggle('sidebar-open');
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
}
if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', () => {
        document.body.classList.remove('sidebar-open');
        document.body.classList.add('sidebar-collapsed');
    });
}

// ── Toast notifications ────────────────────────────────
const toastContainer = document.getElementById('toast-container');

function showToast(message, type = 'info', duration = 4000) {
    if (!toastContainer) return;
    const el = document.createElement('div');
    el.className = `toast-item ${type}`;
    const icon = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' }[type] || 'ℹ';
    el.innerHTML = `<strong>${icon}</strong> ${message}`;
    toastContainer.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(40px)';
        el.style.transition = 'all .3s';
        setTimeout(() => el.remove(), 300);
    }, duration);
}

// ── Page Loader ────────────────────────────────────────
const loader = document.getElementById('page-loader');
function showLoader()  { if (loader) loader.classList.add('active'); }
function hideLoader()  { if (loader) loader.classList.remove('active'); }

// ── Confirm delete ──────────────────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    const msg = btn.dataset.confirm || 'Apakah Anda yakin?';
    if (!confirm(msg)) e.preventDefault();
});

// ── Router Status Polling ──────────────────────────────
function pollRouterStatus() {
    document.querySelectorAll('[data-router-id]').forEach(el => {
        const id = el.dataset.routerId;
        fetch(`/ajax/router_status.php?id=${id}`)
            .then(r => r.json())
            .then(data => {
                const badge = el.querySelector('.router-status-badge');
                const dot   = el.querySelector('.status-dot');
                if (badge) {
                    badge.className = data.online
                        ? 'badge bg-success router-status-badge'
                        : 'badge bg-danger router-status-badge';
                    badge.textContent = data.online ? 'Online' : 'Offline';
                }
                if (dot) {
                    dot.className = data.online
                        ? 'status-dot online'
                        : 'status-dot offline';
                }
                if (el.classList.contains('router-card')) {
                    el.classList.toggle('online', data.online);
                    el.classList.toggle('offline', !data.online);
                }
                const usersEl = el.querySelector('.router-users-count');
                if (usersEl && data.active_users !== undefined) {
                    usersEl.textContent = data.active_users;
                }
            })
            .catch(() => {});
    });
}

// Start polling if router status elements exist
if (document.querySelector('[data-router-id]')) {
    pollRouterStatus();
    setInterval(pollRouterStatus, 30000);
}

// ── Active Users Auto-Refresh ──────────────────────────
const activeUsersTable = document.getElementById('active-users-table');
if (activeUsersTable) {
    function refreshActiveUsers() {
        const router = document.getElementById('filter-router')?.value || '';
        fetch(`/ajax/active_users.php?router_id=${router}`)
            .then(r => r.json())
            .then(data => {
                const tbody = activeUsersTable.querySelector('tbody');
                if (!tbody) return;
                if (!data.users || data.users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">
                        <i class="bi bi-wifi-off fs-3 d-block mb-2"></i>Tidak ada user aktif</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.users.map(u => `
                    <tr>
                        <td class="font-mono fw-600">${escHtml(u.username)}</td>
                        <td>${escHtml(u.router_name || u.nasipaddress)}</td>
                        <td>${escHtml(u.callingstationid || '-')}</td>
                        <td>${escHtml(u.framedipaddress || '-')}</td>
                        <td>${escHtml(u.duration)}</td>
                        <td>${escHtml(u.dl)}</td>
                        <td>${escHtml(u.ul)}</td>
                        <td>${escHtml(u.profile || '-')}</td>
                        <td>
                            <button class="btn btn-danger btn-sm btn-icon"
                                title="Disconnect"
                                data-confirm="Disconnect ${escHtml(u.username)}?"
                                onclick="disconnectUser('${escHtml(u.username)}', '${escHtml(u.radacctid)}', this)">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </td>
                    </tr>`).join('');
                document.getElementById('active-count').textContent = data.users.length;
            })
            .catch(() => {});
    }
    refreshActiveUsers();
    setInterval(refreshActiveUsers, 30000);
}

// ── Disconnect User ────────────────────────────────────
function disconnectUser(username, sessionId, btn) {
    if (btn) btn.disabled = true;
    fetch('/process/disconnect_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `username=${encodeURIComponent(username)}&session_id=${encodeURIComponent(sessionId)}&csrf=${getCsrf()}`,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`User ${username} berhasil di-disconnect.`, 'success');
            if (typeof refreshActiveUsers === 'function') refreshActiveUsers();
        } else {
            showToast(`Gagal disconnect: ${data.error}`, 'error');
            if (btn) btn.disabled = false;
        }
    })
    .catch(() => { if (btn) btn.disabled = false; });
}

// ── Test Router API ────────────────────────────────────
function testRouterApi(routerId, resultEl) {
    if (resultEl) {
        resultEl.textContent = 'Testing...';
        resultEl.className = 'router-status-badge badge bg-secondary';
    }
    fetch(`/ajax/test_api.php?id=${routerId}`)
        .then(r => r.json())
        .then(data => {
            if (resultEl) {
                if (data.success) {
                    resultEl.className = 'router-status-badge badge bg-success';
                    resultEl.innerHTML = `<i class="bi bi-check-circle"></i> Connected — ${escHtml(data.identity || '')}`;
                } else {
                    resultEl.className = 'router-status-badge badge bg-danger';
                    resultEl.innerHTML = `<i class="bi bi-x-circle"></i> Failed — ${escHtml(data.error || '')}`;
                }
            }
        })
        .catch(() => {
            if (resultEl) {
                resultEl.className = 'router-status-badge badge bg-danger';
                resultEl.innerHTML = `<i class="bi bi-x-circle"></i> Request failed`;
            }
        });
}

// ── Voucher Generate: Preview profile info ─────────────
const profileSelect = document.getElementById('profile_id');
const profileInfo   = document.getElementById('profile-info');
if (profileSelect && profileInfo) {
    profileSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt.value) { profileInfo.innerHTML = ''; return; }
        fetch(`/ajax/profile_info.php?id=${opt.value}`)
            .then(r => r.json())
            .then(d => {
                profileInfo.innerHTML = d.html || '';
            })
            .catch(() => {});
    });
    profileSelect.dispatchEvent(new Event('change'));
}

// ── Select All checkbox ────────────────────────────────
const selectAll = document.getElementById('select-all');
if (selectAll) {
    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });
}

function updateBulkActions() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    const bulkBar = document.getElementById('bulk-bar');
    const countEl = document.getElementById('selected-count');
    if (bulkBar) bulkBar.style.display = checked > 0 ? 'flex' : 'none';
    if (countEl) countEl.textContent = checked;
}

// ── CSRF Token ─────────────────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ── Utility ────────────────────────────────────────────
function escHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Table search filter ────────────────────────────────
const tableSearch = document.getElementById('table-search');
if (tableSearch) {
    tableSearch.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#data-table tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });
}

// ── Dismiss flash after 5s ─────────────────────────────
document.querySelectorAll('.alert.auto-dismiss').forEach(el => {
    setTimeout(() => {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
        if (bsAlert) bsAlert.close();
    }, 5000);
});
