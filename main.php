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

function traficback(array $clickParams): CloakerAction
{
    global $db;
    $db->add_trafficback_click($clickParams);
    $cs = $db->get_common_settings();
    $mp = new MacrosProcessor(null, $clickParams);
    $tbUrl = $mp->replace_url_macros($cs['trafficBackUrl']);

    return empty($tbUrl) ?
        new CloakerAction('traficback', 'die', 'NO CAMPAIGN FOR THIS DOMAIN AND TRAFFICBACK NOT SET!') :
        new CloakerAction('traficback', 'redirect', $tbUrl);
}

function jscheck(Campaign $c): CloakerAction
{
    $page = load_content_with_include('js/jscheck.html');

    $detectJs = file_get_contents(__DIR__ . '/js/detect.js');
    $detectJs = str_replace('{DEBUG}', DebugMethods::on() ? 'true' : 'false', $detectJs);
    $detectJs = str_replace('{DOMAIN}', get_cloaker_path(), $detectJs);

    $jbd = $c->black->jsBotDetection;
    $js_checks_str = implode('", "', $jbd->events);
    $detectJs = str_replace('{JSCHECKS}', $js_checks_str, $detectJs);
    $detectJs = str_replace('{JSTIMEOUT}', $jbd->timeout, $detectJs);
    $detectJs = str_replace('{JSTZMIN}', $jbd->tzMin, $detectJs);
    $detectJs = str_replace('{JSTZMAX}', $jbd->tzMax, $detectJs);

    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($detectJs);
        $detectJs = $hunter->Obfuscate();
    }
    $needle = '<body>';
    $page = insert_after_tag($page, $needle, "<script>{$detectJs}</script>");

    $jscheckui = file_get_contents(__DIR__ . '/js/jscheckui.js');
    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($jscheckui);
        $jscheckui = $hunter->Obfuscate();
    }
    $page = insert_after_tag($page, $needle, "<script>{$jscheckui}</script>");

    session_write('jscheck_pending', time());
    return new CloakerAction('jscheck', 'html', $page);
}

function white(Campaign $c): CloakerAction
{
    $ws = $c->white;
    $action = $ws->action;
    $error_codes = $ws->errorCodes;
    $folder_names = $ws->folderNames;
    $curl_urls = $ws->curlUrls;
    $redirect_urls = $ws->redirectUrls;
    $redirect_type = $ws->redirectType;
    /** @var WhiteSettings|DomainWhiteSettings $loadModeSource */
    $loadModeSource = $ws;

    if ($ws->domainFilterEnabled) {
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $curdomain = $httpHost;
        if (str_ends_with($curdomain, ':' . $_SERVER['SERVER_PORT'])) {
            $curdomain = substr($curdomain, 0, -strlen(':' . $_SERVER['SERVER_PORT']));
        }
        foreach ($ws->domainSpecific as $dws) {
            if ($dws->domain !== $httpHost && $dws->domain !== $curdomain) {
                continue;
            }
            $action = $dws->action;
            $error_codes = $dws->errorCodes;
            $folder_names = $dws->folderNames;
            $curl_urls = $dws->curlUrls;
            $redirect_urls = $dws->redirectUrls;
            $redirect_type = $dws->redirectType;
            $loadModeSource = $dws;
            break;
        }
    }

    $abtest = new AbTest($c);
    switch ($action) {
        case 'error':
            $curcode = $abtest->select_item($error_codes, 'white', false);
            session_write('white', $curcode[0]);
            return new CloakerAction('white', 'error', $curcode[0]);
        case 'folder':
            $curfolder = $abtest->select_item($folder_names, 'white', true);
            session_write('white', $curfolder[0]);
            return new CloakerAction('white', 'html', load_white_content($curfolder[0], $loadModeSource->getLoadMode($curfolder[0])));
        case 'curl':
            $cururl = $abtest->select_item($curl_urls, 'white', false);
            session_write('white', $cururl[0]);
            return new CloakerAction('white', 'html', load_white_curl($cururl[0], $loadModeSource->getLoadMode($cururl[0])));
        case 'redirect':
            $cururl = $abtest->select_item($redirect_urls, 'white', false);
            session_write('white', $cururl[0]);
            return new CloakerAction('white', 'redirect', $cururl[0], $redirect_type);
        default:
            return new CloakerAction('white', 'error', 404);
    }
}

function black(Campaign $c, int $flowIndex, array $clickparams): CloakerAction
{
    global $db;

    $cursubid = set_subid();
    set_px();

    $flow = $c->black->flows[$flowIndex];
    $bl = $flow->land;
    $landings = match ($bl->action) {
        'redirect' => $bl->redirectUrls,
        'folder' => $bl->folderNames,
        default => []
    };
    $isfolderland = $bl->action == 'folder';

    $abtest = new AbTest($c);
    $bp = $flow->preland;
    $isThompson = $flow->distribution === 'thompson';

    switch ($bp->action) {
        case 'none': //no prelanding
            if ($isThompson) {
                $landing = $abtest->select_thompson_variant($landings, 'land', $flow->name, $flow->optimize_for);
            } else {
                $res = $abtest->select_distributed($landings, 'landing', $isfolderland, $flow->distribution, $bl->weights);
                $landing = $res[0];
            }
            set_cookie('landing', $landing);
            $db->add_black_click($cursubid, $clickparams, '', $landing, $flow->name, $c->campaignId);

            $action = match ($bl->action) {
                'folder' => new CloakerAction(
                    'black',
                    'html',
                    load_landing($c, $flow->hasPrelanding(), $landing, $bl->isDirectLoad($landing))
                ),
                'redirect' => new CloakerAction(
                    'black',
                    'redirect',
                    $landing,
                    $bl->redirectType
                ),
                default => new CloakerAction('black', 'die', "No such landing action found: " . $bl->action)
            };
            break;
        case 'folder': //local prelanding
            $prelandings = $bp->folderNames;
            if (empty($prelandings)) {
                add_error_log("No prelanding folders found in campaign {$c->campaignId}!", false, true);
            }

            if ($isThompson && $flow->optimize_mode === 'funnels') {
                [$prelanding, $landing] = $abtest->select_thompson_funnel(
                    $prelandings, $landings, $flow->name, $flow->optimize_for
                );
            } elseif ($isThompson) {
                $prelanding = $abtest->select_thompson_variant($prelandings, 'preland', $flow->name, $flow->optimize_for);
                $landing = $abtest->select_thompson_variant($landings, 'land', $flow->name, $flow->optimize_for);
            } else {
                $res = $abtest->select_distributed($prelandings, 'prelanding', true, $flow->distribution, $bp->weights);
                $prelanding = $res[0];
                $res = $abtest->select_distributed($landings, 'landing', $isfolderland, $flow->distribution, $bl->weights);
                $landing = $res[0];
            }
            set_cookie('prelanding', $prelanding);
            set_cookie('landing', $landing);

            $db->add_black_click($cursubid, $clickparams, $prelanding, $landing, $flow->name, $c->campaignId);
            $action = new CloakerAction('black', 'html', load_prelanding($c, $prelanding, $bp->isDirectLoad($prelanding)));
            break;
        default:
            $action = new CloakerAction('black', 'die', "No such prelanding action found: " . $bp->action);
            break;
    }
    return $action;
}
