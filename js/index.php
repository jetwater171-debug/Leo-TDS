<?php
//fix for Apache Multiviews and/or PHP Development Server
if ($_SERVER['SCRIPT_NAME'] !== $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit("Not Found");
}

//if requested not from a script then return jquery
require_once __DIR__.'/../debug.php';
if (!DebugMethods::on() && isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'script') {
    require_once __DIR__.'/../requestfunc.php';
    $jq = get("https://code.jquery.com/jquery-3.6.1.min.js");
    echo $jq['content'];
    exit;
}

require_once __DIR__.'/../tds.php';
require_once __DIR__.'/../actions.php';
require_once __DIR__.'/../cookies.php';

if (!is_null(session_read('jscheck_pending')))
    $action = Tds::processJsCheck();
else
{
    $prefill = [];
    if (isset($_GET['tds_qs']))
        $prefill['tds_qs'] = base64_decode($_GET['tds_qs']);
    $prefill['tds_ref'] = $_GET['tds_ref']??'';
    $action = Tds::getJsAction($prefill);
}
$action->perform();