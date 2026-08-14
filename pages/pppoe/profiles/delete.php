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
        $api = MikrotikAPI::fromRouter($selRouter);
        if ($api->connect()) {
            $api->talk(['/ppp/profile/remove', '=.id=' . $id]);
            $api->close();
            flash_set('success', 'Profil PPPoE berhasil dihapus dari MikroTik.');
        }
    } catch (Exception $e) {
        flash_set('error', 'Gagal menghapus profil: ' . $e->getMessage());
    }
}

header('Location: /index.php?page=pppoe_profiles&router_id=' . $selRid);
exit;
