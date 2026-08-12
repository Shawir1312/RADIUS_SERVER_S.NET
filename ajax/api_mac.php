<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../lib/routeros_api.class.php';

header('Content-Type: application/json; charset=utf-8');

if (!auth_check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$router_id = (int)($_GET['router_id'] ?? $_POST['router_id'] ?? 0);

if (!$router_id) {
    echo json_encode(['success' => false, 'message' => 'Router belum dipilih']);
    exit;
}

$router = get_router($router_id);
if (!$router) {
    echo json_encode(['success' => false, 'message' => 'Router tidak ditemukan']);
    exit;
}

$api = new RouterosAPI();
$api->debug = false;

if (!$api->connect($router['ip_address'], $router['api_user'], $router['api_password'], (int)$router['api_port'])) {
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke router MikroTik']);
    exit;
}

function send_json($success, $data = []) {
    global $api;
    $api->disconnect();
    
    $response = ['success' => $success];
    if (is_string($data)) {
        $response['message'] = $data;
    } else if (is_array($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'list':
            $bindings = $api->comm('/ip/hotspot/ip-binding/print');
            $list = [];
            foreach ($bindings as $b) {
                $list[] = [
                    'id'       => $b['.id'] ?? '',
                    'mac'      => $b['mac-address'] ?? '',
                    'type'     => $b['type'] ?? '',
                    'comment'  => $b['comment'] ?? '',
                    'disabled' => ($b['disabled'] ?? 'false') === 'true',
                ];
            }
            send_json(true, ['data' => $list]);
            break;

        case 'add':
            $mac  = trim($_POST['mac'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if (!$mac || !$name) {
                send_json(false, 'MAC dan Nama wajib diisi');
            }

            $result = $api->comm('/ip/hotspot/ip-binding/add', [
                'mac-address' => $mac,
                'type' => 'bypassed',
                'comment' => $name
            ]);

            if (isset($result['!trap'])) {
                send_json(false, $result['!trap'][0]['message'] ?? 'Error dari MikroTik');
            }
            
            send_json(true, 'MAC berhasil ditambahkan');
            break;

        case 'update':
            $id   = trim($_POST['id'] ?? '');
            $mac  = trim($_POST['mac'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if (!$id) {
                send_json(false, 'ID tidak valid');
            }

            $params = ['.id' => $id];
            if ($mac) $params['mac-address'] = $mac;
            if ($name) $params['comment'] = $name;

            $result = $api->comm('/ip/hotspot/ip-binding/set', $params);

            if (isset($result['!trap'])) {
                send_json(false, $result['!trap'][0]['message'] ?? 'Error dari MikroTik');
            }
            
            send_json(true, 'Binding berhasil diperbarui');
            break;

        case 'delete':
            $id = trim($_POST['id'] ?? '');
            
            if (!$id) {
                send_json(false, 'ID tidak valid');
            }

            $result = $api->comm('/ip/hotspot/ip-binding/remove', ['.id' => $id]);

            if (isset($result['!trap'])) {
                send_json(false, $result['!trap'][0]['message'] ?? 'Error dari MikroTik');
            }
            
            send_json(true, 'Binding berhasil dihapus');
            break;

        default:
            send_json(false, 'Action tidak valid');
    }
} catch (Exception $e) {
    send_json(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
}
