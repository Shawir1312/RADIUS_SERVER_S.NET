<?php
/**
 * PPPoE Profiles — Delete from Mikrotik
 */
$selRid = (int)get('router_id');
$id = get('id');

$routers = get_all_routers();
$selRouter = null;
foreach ($routers as $r) {
    if ($r['id'] == $selRid) {
        $selRouter = $r;
        break;
    }
}

if ($selRouter && $id) {
    try {
        require_once __DIR__ . '/../../../lib/routeros_api.class.php';
        $api = new RouterosAPI();
        $api->debug = false;
        if ($api->connect($selRouter['ip_address'], $selRouter['api_user'], $selRouter['api_password'], (int)$selRouter['api_port'])) {
            $api->comm('/ppp/profile/remove', ['.id' => $id]);
            $api->disconnect();
            flash_set('success', 'Profil PPPoE berhasil dihapus dari MikroTik.');
        }
    } catch (Exception $e) {
        flash_set('error', 'Gagal menghapus profil: ' . $e->getMessage());
    }
}

header('Location: /index.php?page=pppoe_profiles&router_id=' . $selRid);
exit;
