<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';
require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/actions.php';

function traficback(array $clickParams):CloakerAction
{
    global $db;
    $db->add_trafficback_click($clickParams);
    $cs = $db->get_common_settings();
    $mp = new MacrosProcessor(null,$clickParams);
    $tbUrl = $mp->replace_url_macros($cs['trafficBackUrl']);
    
    return empty($tbUrl)? 
        new CloakerAction('traficback','die','NO CAMPAIGN FOR THIS DOMAIN AND TRAFFICBACK NOT SET!'):
        new CloakerAction('traficback','redirect',$tbUrl);
}

function jscheck(Campaign $c):CloakerAction
{
    $page = load_content_with_include('js/jscheck.html');
    
    $detectJs = file_get_contents(__DIR__.'/js/detect.js');
    $detectJs = str_replace('{DEBUG}', DebugMethods::on() ? 'true' : 'false', $detectJs);
    $detectJs = str_replace('{DOMAIN}', get_cloaker_path(), $detectJs);
    
    $jsChecks = $c->white->jsChecks;
    $js_checks_str = implode('", "', $jsChecks->events);
    $detectJs = str_replace('{JSCHECKS}', $js_checks_str, $detectJs);
    $detectJs = str_replace('{JSTIMEOUT}', $jsChecks->timeout, $detectJs);
    $detectJs = str_replace('{JSTZMIN}', $jsChecks->tzMin, $detectJs);
    $detectJs = str_replace('{JSTZMAX}', $jsChecks->tzMax, $detectJs);
    
    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($detectJs);
        $detectJs = $hunter->Obfuscate();
    }  
    $needle = '<body>';
    $page = insert_after_tag($page, $needle, "<script>{$detectJs}</script>");
   
    $jscheckui = file_get_contents(__DIR__.'/js/jscheckui.js');
    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($jscheckui);
        $jscheckui = $hunter->Obfuscate();
    }
    $page = insert_after_tag($page, $needle, "<script>{$jscheckui}</script>");
    
    session_write('jscheck_pending', time());
    return new CloakerAction('jscheck','html',$page);
}

function white(Campaign $c):CloakerAction
{
    $ws = $c->white;
    $action = $ws->action;
    $error_codes = $ws->errorCodes;
    $folder_names = $ws->folderNames;
    $curl_urls = $ws->curlUrls;
    $redirect_urls = $ws->redirectUrls;


    if ($ws->domainFilterEnabled) { //if we want to use different white pages for different domains
        $curdomain = $_SERVER['HTTP_HOST'];
        if (str_ends_with($curdomain, ':' . $_SERVER['SERVER_PORT'])) {
            $portLength = strlen(':' . $_SERVER['SERVER_PORT']);
            $curdomain = substr($curdomain, 0, -$portLength);
        }
        foreach ($ws->domainSpecific as $wds) {
            if ($wds->name !== $curdomain) continue;
            $wtd_arr = explode(":", $wds->action, 2);
            $action = $wtd_arr[0];
            switch ($action) {
                case 'error':
                    $error_codes = [intval($wtd_arr[1])];
                    break;
                case 'folder':
                    $folder_names = [$wtd_arr[1]];
                    break;
                case 'curl':
                    $curl_urls = [$wtd_arr[1]];
                    break;
                case 'redirect':
                    $redirect_urls = [$wtd_arr[1]];
                    break;
            }
            break;
        }
    }

    switch ($action) {
        case 'error':
            $curcode = select_item($error_codes, $c->saveUserFlow, 'white', true);
            return new CloakerAction('white','error',$curcode[0]);
        case 'folder':
            $curfolder = select_item($folder_names, $c->saveUserFlow, 'white', true);
            return new CloakerAction('white','html', load_white_content($curfolder[0]));
        case 'curl':
            $cururl = select_item($curl_urls, $c->saveUserFlow, 'white', false);
            return new CloakerAction('white','html', load_white_curl($cururl[0]));
        case 'redirect':
            $cururl = select_item($redirect_urls, $c->saveUserFlow, 'white', false);
            return new CloakerAction('white','redirect',$cururl[0], $ws->redirectType);
        default:
            return new CloakerAction('white','error',404);
    }
}

function black(Campaign $c, array $clickparams):CloakerAction
{
    global $db;

    $cursubid = set_subid();
    set_px();

    $landings = [];
    $isfolderland = false;

    $bl = $c->black->land;
    if ($bl->action == 'redirect')
        $landings = $bl->redirectUrls;
    else if ($bl->action == 'folder') {
        $landings = $bl->folderNames;
        $isfolderland = true;
    }

    $bp = $c->black->preland;
    switch ($bp->action) {
        case 'none': //no prelanding
            $res = select_item($landings, $c->saveUserFlow, 'landing', $isfolderland);
            $landing = $res[0];
            $db->add_black_click($cursubid, $clickparams, '', $landing, $c->campaignId);

            switch ($bl->action) {
                case 'folder':
                    return new CloakerAction('black', 'html', load_landing($c, $landing));
                case 'redirect':
                    $redirectUrl = insert_subs_into_url($c->subIds, $_GET, $landing);
                    return new CloakerAction('black', 'redirect', $redirectUrl, $bl->redirectType);
                default:
                    return new CloakerAction('black','die',"No such landing action found: ".$bl->action);
            }
        case 'folder': //local prelanding
            $prelandings = $bp->folderNames;
            if (empty($prelandings))
                break;
            $res = select_item($prelandings, $c->saveUserFlow, 'prelanding', true);
            $prelanding = $res[0];
            $res = select_item($landings, $c->saveUserFlow, 'landing', $isfolderland);
            $landing = $res[0];
            $t = $res[1];

            $db->add_black_click($cursubid, $clickparams, $prelanding, $landing, $c->campaignId);
            return new CloakerAction('black', 'html', load_prelanding($c, $prelanding, $t));
        default:
            return new CloakerAction('black','die',"No such prelanding action found: ".$bp->action);
    }
}