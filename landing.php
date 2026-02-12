<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/campaign.php';

$subid = get_subid();
if (empty($subid)) {
    die('NO SUBID FOUND!');
}

global $db;
$click = $db->get_clicks_by_subid($subid, true);
if (empty($click)) {
    die('NO CLICK FOUND FOR THIS SUBID!');
}

$campId = $click['campaign_id'];
$settings = $db->get_campaign_settings($campId);
$c = new Campaign($campId, $settings);

// Find flow by name from click record
$flow = null;
$flowIndex = null;
foreach ($c->black->flows as $i => $f) {
    if ($f->name === $click['flow']) {
        $flow = $f;
        $flowIndex = $i;
        break;
    }
}
if ($flow === null) {
    die('FLOW NOT FOUND!');
}

$ls = $flow->land;
$landName = $click['land'];


switch ($ls->action) {
    case 'folder':
        $db->add_lpctr($subid);
        echo load_landing($c, $flow->hasPrelanding(), $landName);
        break;
    case 'redirect':
        $redirectUrl = null;
        foreach ($ls->redirectUrls as $url) {
            if ($url === $landName) {
                $redirectUrl = $url;
                break;
            }
        }
        if ($redirectUrl === null) {
            die('LANDING NOT FOUND!');
        }
        $db->add_lpctr($subid);
        redirect($redirectUrl, $ls->redirectType, true);
        break;
}