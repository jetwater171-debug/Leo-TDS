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

$campId = $_GET['campId'] ?? null;
if (is_null($campId)) {
    die('NO CAMPAIGN ID FOUND!');
}

global $db;
$settings = $db->get_campaign_settings($campId);
$c = new Campaign($campId, $settings);

$f = $_GET['f'] ?? null;
if (is_null($f)) {
    die('NO FLOW INDEX FOUND!');
}

if (count($c->black->flows) < $f + 1) {
    die('FLOW INDEX IS OUT OF BOUNDS!');
}

$l = $_GET['l'] ?? null;
if (is_null($l)) {
    die('NO LANDING INDEX FOUND!');
}

$flow = $c->black->flows[$f];
$ls = $flow->land;

$abtest = new AbTest($c);
switch ($ls->action) {
    case 'folder':
        if (count($ls->folderNames) < $l + 1) {
            die('FOLDER LANDING INDEX IS OUT OF BOUNDS!');
        }
        $db->add_lpctr($subid);
        $landing = $ls->folderNames[$l];
        echo load_landing($c, $flow, $landing);
        break;
    case 'redirect':
        if (count($ls->redirectUrls) < $l + 1) {
            die('REDIRECT LANDING INDEX IS OUT OF BOUNDS!');
        }
        $db->add_lpctr($subid);
        $redirectUrl = $ls->redirectUrls[$l];
        redirect($redirectUrl, $ls->redirectType, true);
        break;
}