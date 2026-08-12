<?php
// ================================================================
// MAC Registration — API Endpoint
// ================================================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/includes/config.php';

// Auth check
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {

        // ============================================================
        // SELECT ROUTER — Switch active router
        // ============================================================
        case 'select-router':
            $routerId = trim($_POST['router_id'] ?? '');
            $router = getRouterById($routerId);
            if (!$router) {
                jsonResponse(['success' => false, 'message' => 'Router tidak ditemukan'], 404);
            }
            $_SESSION['selected_router'] = $routerId;
            jsonResponse(['success' => true, 'message' => 'Router dipilih: ' . $router['name']]);
            break;

        // ============================================================
        // LIST — Get all IP bindings from selected router
        // ============================================================
        case 'list':
            $mt = getMikrotik();
            if (!$mt) {
                jsonResponse(['success' => false, 'message' => 'Gagal koneksi ke MikroTik. Periksa konfigurasi router.'], 500);
            }

            $bindings = $mt->getIpBindings();
            $mt->close();

            $list = [];
            foreach ($bindings as $b) {
                $list[] = [
                    'id'       => $b['.id'] ?? '',
                    'mac'      => $b['mac-address'] ?? '',
                    'type'     => $b['type'] ?? '',
                    'comment'  => $b['comment'] ?? '',
                    'disabled' => ($b['disabled'] ?? 'false') === 'true' || ($b['disabled'] ?? 'false') === 'yes',
                    'address'  => $b['address'] ?? '',
                    'server'   => $b['server'] ?? '',
                ];
            }

            jsonResponse(['success' => true, 'data' => $list]);
            break;

        // ============================================================
        // ADD — Add MAC binding (type=bypassed, no IP needed)
        // ============================================================
        case 'add':
            $mac  = trim($_POST['mac'] ?? '');
            $name = trim($_POST['name'] ?? '');

            $mac = sanitizeMac($mac);
            if (!$mac || !isValidMac($mac)) {
                jsonResponse(['success' => false, 'message' => 'Format MAC address tidak valid. Gunakan format XX:XX:XX:XX:XX:XX'], 400);
            }
            if (empty($name)) {
                jsonResponse(['success' => false, 'message' => 'Nama tidak boleh kosong'], 400);
            }

            $mt = getMikrotik();
            if (!$mt) {
                jsonResponse(['success' => false, 'message' => 'Gagal koneksi ke MikroTik'], 500);
            }

            $result = $mt->addIpBinding($mac, $name);
            $error = $mt->error;
            $mt->close();

            if ($result) {
                jsonResponse(['success' => true, 'message' => "MAC $mac berhasil ditambahkan (bypass)"]);
            } else {
                jsonResponse(['success' => false, 'message' => 'Gagal menambahkan: ' . ($error ?: 'Unknown error')], 500);
            }
            break;

        // ============================================================
        // UPDATE — Update MAC binding
        // ============================================================
        case 'update':
            $id   = trim($_POST['id'] ?? '');
            $mac  = trim($_POST['mac'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if (empty($id)) {
                jsonResponse(['success' => false, 'message' => 'ID binding tidak ditemukan'], 400);
            }

            if ($mac) {
                $mac = sanitizeMac($mac);
                if (!isValidMac($mac)) {
                    jsonResponse(['success' => false, 'message' => 'Format MAC address tidak valid'], 400);
                }
            }

            $mt = getMikrotik();
            if (!$mt) {
                jsonResponse(['success' => false, 'message' => 'Gagal koneksi ke MikroTik'], 500);
            }

            $result = $mt->updateIpBinding($id, $mac ?: null, $name !== '' ? $name : null);
            $error = $mt->error;
            $mt->close();

            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Binding berhasil diperbarui']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Gagal memperbarui: ' . ($error ?: 'Unknown error')], 500);
            }
            break;

        // ============================================================
        // DELETE — Remove MAC binding
        // ============================================================
        case 'delete':
            $id = trim($_POST['id'] ?? '');

            if (empty($id)) {
                jsonResponse(['success' => false, 'message' => 'ID binding tidak ditemukan'], 400);
            }

            $mt = getMikrotik();
            if (!$mt) {
                jsonResponse(['success' => false, 'message' => 'Gagal koneksi ke MikroTik'], 500);
            }

            $result = $mt->removeIpBinding($id);
            $error = $mt->error;
            $mt->close();

            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Binding berhasil dihapus']);
            } else {
                jsonResponse(['success' => false, 'message' => 'Gagal menghapus: ' . ($error ?: 'Unknown error')], 500);
            }
            break;

        // ============================================================
        // IDENTITY — Get router identity
        // ============================================================
        case 'identity':
            $mt = getMikrotik();
            if (!$mt) {
                jsonResponse(['success' => false, 'message' => 'Gagal koneksi ke MikroTik'], 500);
            }
            $identity = $mt->getIdentity();
            $mt->close();
            jsonResponse(['success' => true, 'identity' => $identity]);
            break;

        // ============================================================
        // ROUTERS — Get list of routers
        // ============================================================
        case 'routers':
            $routers = getRouters();
            $selected = $_SESSION['selected_router'] ?? '';
            $list = [];
            foreach ($routers as $r) {
                $list[] = [
                    'id'       => $r['id'],
                    'name'     => $r['name'],
                    'host'     => $r['host'],
                    'selected' => $r['id'] === $selected,
                ];
            }
            jsonResponse(['success' => true, 'data' => $list]);
            break;

        // ============================================================
        // ADMIN: SAVE ROUTER
        // ============================================================
        case 'save-router':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
            }

            $data = loadData();
            $routerId = trim($_POST['router_id'] ?? '');
            $routerData = [
                'id'       => $routerId ?: 'router_' . time(),
                'name'     => trim($_POST['router_name'] ?? ''),
                'host'     => trim($_POST['router_host'] ?? ''),
                'port'     => (int)($_POST['router_port'] ?? 8728),
                'username' => trim($_POST['router_user'] ?? 'admin'),
                'password' => $_POST['router_pass'] ?? '',
            ];

            if (empty($routerData['name']) || empty($routerData['host'])) {
                jsonResponse(['success' => false, 'message' => 'Nama dan host wajib diisi'], 400);
            }

            // Update existing or add new
            $found = false;
            foreach ($data['routers'] as $i => $r) {
                if ($r['id'] === $routerId) {
                    $data['routers'][$i] = $routerData;
                    $found = true;
                    break;
                }
            }
            if (!$found) $data['routers'][] = $routerData;

            saveData($data);
            jsonResponse(['success' => true, 'message' => 'Router berhasil disimpan']);
            break;

        // ============================================================
        // ADMIN: DELETE ROUTER
        // ============================================================
        case 'delete-router':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
            }

            $routerId = trim($_POST['router_id'] ?? '');
            $data = loadData();
            $data['routers'] = array_values(array_filter($data['routers'], function($r) use ($routerId) {
                return $r['id'] !== $routerId;
            }));
            saveData($data);
            jsonResponse(['success' => true, 'message' => 'Router berhasil dihapus']);
            break;

        // ============================================================
        // ADMIN: SAVE USER
        // ============================================================
        case 'save-user':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
            }

            $data = loadData();
            $username = trim($_POST['user_username'] ?? '');
            $fullName = trim($_POST['user_fullname'] ?? '');
            $role     = trim($_POST['user_role'] ?? 'teknisi');
            $password = $_POST['user_password'] ?? '';
            $isEdit   = !empty($_POST['user_edit']);

            if (empty($username) || empty($fullName)) {
                jsonResponse(['success' => false, 'message' => 'Username dan nama lengkap wajib diisi'], 400);
            }
            if (!$isEdit && empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Password wajib diisi untuk user baru'], 400);
            }
            if (!in_array($role, ['admin', 'teknisi'])) {
                jsonResponse(['success' => false, 'message' => 'Role tidak valid'], 400);
            }

            $found = false;
            foreach ($data['users'] as $i => $u) {
                if ($u['username'] === $username) {
                    $data['users'][$i]['full_name'] = $fullName;
                    $data['users'][$i]['role'] = $role;
                    if ($password) {
                        $data['users'][$i]['password'] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['users'][] = [
                    'username'  => $username,
                    'password'  => password_hash($password, PASSWORD_BCRYPT),
                    'full_name' => $fullName,
                    'role'      => $role,
                ];
            }

            saveData($data);
            jsonResponse(['success' => true, 'message' => 'User berhasil disimpan']);
            break;

        // ============================================================
        // ADMIN: DELETE USER
        // ============================================================
        case 'delete-user':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
            }

            $username = trim($_POST['username'] ?? '');
            if ($username === currentUser()['username']) {
                jsonResponse(['success' => false, 'message' => 'Tidak bisa menghapus diri sendiri'], 400);
            }

            $data = loadData();
            $data['users'] = array_values(array_filter($data['users'], function($u) use ($username) {
                return $u['username'] !== $username;
            }));
            saveData($data);
            jsonResponse(['success' => true, 'message' => 'User berhasil dihapus']);
            break;

        // ============================================================
        // ADMIN: GET USERS
        // ============================================================
        case 'users':
            if (!isAdmin()) {
                jsonResponse(['success' => false, 'message' => 'Akses ditolak'], 403);
            }

            $data = loadData();
            $list = [];
            foreach ($data['users'] as $u) {
                $list[] = [
                    'username'  => $u['username'],
                    'full_name' => $u['full_name'],
                    'role'      => $u['role'],
                ];
            }
            jsonResponse(['success' => true, 'data' => $list]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Action tidak valid'], 400);
    }

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
