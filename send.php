<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/requestfunc.php';
global $db, $cloSettings;

$subid = get_subid();
if (empty($subid) && isset($_POST['subid']))
    $subid = $_POST['subid'];

//send to Aff Network only if it's not empty and not a duplicate
if (empty($_POST) || has_conversion_cookies($_POST)) {
    redirect(get_cloaker_path());
    return;
}

$fullpath = '';
$original_action = $_GET['original_action'];
//if the form action is an absolute URL, send the form data to that URL
if (str_starts_with($original_action, "http")) {
    $fullpath = $original_action;
} //else, compose the full address to the script
else {
    $url = get_cookie('landing') . '/' . $original_action;
    $fullpath = get_abs_from_rel($url);
}


if (DebugMethods::On()){
    $res = [];
    $res['info']=[];
    $res['info']['http_code'] = rand(0, 1) ? 200 : 302;
    $res['info']['redirect_url'] = "http://example.com";
    $res['content'] = "<html>This is debug send!</html>";
    $res['error'] = null;
}
else{
    $res = post($fullpath, $_POST);
}

$useUTP = $cloSettings['useUTP'];

switch ($res["info"]["http_code"]) {
    case 302:
        $db->add_lead($subid,$_POST);
        if ($useUTP) {
            redirect("/thankyou/index.php?" . http_build_query($_POST));
        } else {
            redirect($res["info"]["redirect_url"]);
        }
        break;
    case 200:
        $db->add_lead($subid, $_POST);
        if ($useUTP) {
            echo redirect("/thankyou/index.php?" . http_build_query($_POST),"js");
        } else {
            echo $res["content"];
        }
        break;
    default:
        echo $fullpath."<br/>";
        var_dump($res["content"]);
        echo '<br/>';
        var_dump($res["error"]);
        echo '<br/>';
        var_dump($res["info"]);
        echo '<br/>';
        var_dump($_POST);
        exit();
}