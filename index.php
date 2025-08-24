<?php
require_once __DIR__ . '/debug.php';
DebugMethods::start("YWBMainCycle");

//fix for Apache Multiviews and/or PHP Development Server
if ($_SERVER['SCRIPT_NAME'] !== $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit("Not Found");
}
//we always need a slash at the end of the url, otherwise links will not work properly
$url = $_SERVER['REQUEST_URI'];
if ($url==='/admin'){
    header("Location: " . $url . "/");
    exit();
}

//handle robots.txt requests
if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] == '/robots.txt') {
    header('Content-Type: text/plain');
    echo "User-agent: *\nDisallow: /\n";
    exit();
}

require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/main.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/redirect.php';

global $db;
$dbCamp = $db->get_campaign_by_domain();
if ($dbCamp===false){
    $action = traficback(Cloaker::get_click_params());
} else {
    $c = new Campaign($dbCamp['id'],$dbCamp['settings']);
    $cloaker = new Cloaker($c->filters);

    if ($c->white->jsChecks->enabled) {
        $action = jscheck();
    } else if ($cloaker->is_bad_click()) { 
        $db->add_white_click($cloaker->click_params, $cloaker->block_reason, $c->campaignId);
        $action = white();
    } else
        $action = black($cloaker->click_params);
}

if ($action->type!=='redirect'){
    DebugMethods::stop("YWBMainCycle");
    takeAction($action);
}
else{
    takeAction($action);
    DebugMethods::stop("YWBMainCycle");
}