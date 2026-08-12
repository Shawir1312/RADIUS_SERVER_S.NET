<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../lib/routeros_api.class.php';

header('Content-Type: application/json; charset=utf-8');

auth_start();
if (empty($_SESSION['admin_id'])) {
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
        case 'interfaces':
            $interfaces = $api->comm('/interface/print');
            $list = [];
            foreach ($interfaces as $iface) {
                if (($iface['disabled'] ?? 'false') === 'false') {
                    $list[] = [
                        'name' => $iface['name'],
                        'type' => $iface['type'] ?? '',
                        'running' => ($iface['running'] ?? 'false') === 'true'
                    ];
                }
            }
            send_json(true, ['data' => $list]);
            break;

        case 'traffic':
            $iface = trim($_GET['interface'] ?? $_POST['interface'] ?? '');
            if (!$iface) {
                send_json(false, 'Interface tidak valid');
            }
            
            $traffic = $api->comm('/interface/monitor-traffic', [
                'interface' => $iface,
                'once' => 'true'
            ]);
            
            if (isset($traffic['!trap'])) {
                send_json(false, $traffic['!trap'][0]['message'] ?? 'Error dari MikroTik');
            }
            
            if (isset($traffic[0])) {
                $rx = (int)($traffic[0]['rx-bits-per-second'] ?? 0);
                $tx = (int)($traffic[0]['tx-bits-per-second'] ?? 0);
                
                send_json(true, [
                    'rx' => $rx,
                    'tx' => $tx
                ]);
            } else {
                send_json(false, 'Data traffic kosong');
            }
            break;

        default:
            send_json(false, 'Action tidak valid');
    }
} catch (Exception $e) {
    send_json(false, 'Terjadi kesalahan sistem: ' . $e->getMessage());
}
