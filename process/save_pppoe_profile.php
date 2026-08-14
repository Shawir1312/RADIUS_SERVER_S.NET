<?php
/**
 * PPPoE Profiles — Save to Mikrotik
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_check();
csrf_verify();

$selRid = (int)post('router_id');
$id = post('id');
$name = trim(post('name'));
$local = trim(post('local_address'));
$remote = trim(post('remote_address'));
$rate = trim(post('rate_limit'));
$only_one = post('only_one');
$comment = trim(post('comment'));

if (!$name || !$selRid) {
    flash_set('error', 'Data tidak lengkap.');
    header('Location: /index.php?page=pppoe_profiles&router_id=' . $selRid);
    exit;
}

$routers = get_all_routers();
$selRouter = null;
foreach ($routers as $r) {
    if ($r['id'] == $selRid) {
        $selRouter = $r;
        break;
    }
}

if ($selRouter) {
    try {
        $api = MikrotikAPI::fromRouter($selRouter);
        if ($api->connect()) {
            $cmd = [];
            if ($id) {
                $cmd[] = '/ppp/profile/set';
                $cmd[] = '=.id=' . $id;
            } else {
                $cmd[] = '/ppp/profile/add';
                $cmd[] = '=name=' . $name;
            }

            if ($local) $cmd[] = '=local-address=' . $local;
            if ($remote) $cmd[] = '=remote-address=' . $remote;
            if ($rate) $cmd[] = '=rate-limit=' . $rate;
            if ($only_one) $cmd[] = '=only-one=' . $only_one;
            if ($comment) $cmd[] = '=comment=' . $comment;

            $api->talk($cmd);
            $api->close();

            flash_set('success', 'Profil PPPoE berhasil disimpan ke MikroTik.');
        }
    } catch (Exception $e) {
        flash_set('error', 'Gagal menyimpan profil: ' . $e->getMessage());
    }
}

header('Location: /index.php?page=pppoe_profiles&router_id=' . $selRid);
exit;
