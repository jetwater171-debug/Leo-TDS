<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/main.php';
require_once __DIR__ . '/cookies.php';

class Tds
{
    public static function getAction() : CloakerAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) {
            $action = traficback(Cloaker::get_click_params());
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new Cloaker($c->filters);

            if ($clkr->is_bad_click()) {
                $db->add_white_click($clkr->click_params, $clkr->block_reason, $c->campaignId);
                $action = white($c);
            } else {
                $jscheck_passed = session_read('jscheck_passed');
                if ($c->white->jsChecks->enabled && is_null($jscheck_passed)) {
                    $action = jscheck($c);
                } else {
                    $action = black($c, $clkr->click_params);
                }
            }
        }
        return $action;
    }

    public static function getJsAction() : JsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) {
            $action = traficback(Cloaker::get_click_params());
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new Cloaker($c->filters);

            if ($clkr->is_bad_click()) {
                $db->add_white_click($clkr->click_params, $clkr->block_reason, $c->campaignId);
                $action = white($c);
            } else {
                $jscheck_passed = session_read('jscheck_passed');
                if ($c->white->jsChecks->enabled && is_null($jscheck_passed)) {
                    $action = jscheck($c);
                    $action->action = 'html_content';
                } else {
                    $action = black($c, $clkr->click_params);
                    if ($c->black->jsconnectAction === 'iframe') {
                        $action->action = 'html_iframe';
                    }else{
                        $action->action = 'html_content';
                    }
                }
            }
        }
        return JsAction::FromCloakerAction($action);
    }

    public static function processJsCheck() : JsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false){ //campaign already deleted or domain changed? lol
            if (DebugMethods::on()) 
                $action = new JsAction("traficback", "js", "console.log('Debug: No campaign found for this domain!');");
            else
                $action = new JsAction("traficback", "error", "");
        }
        
        if (isset($_GET['reason'])) //This means that the user didn't pass JS checks
        {
            $added = $db->add_white_click(Cloaker::get_click_params(), $_GET['reason'], $dbCamp['id']);
            if (DebugMethods::on()) {
                $msg = ($added ? "console.log('Debug: White click logged.');" : "console.log('Debug: Error adding white click!');");
                $action = new JsAction("white", "js", $msg);
            }
            else
                $action = new JsAction("white", "error", "");
        }
        else{
            //TODO: check if SUBID.txt file exists
            //if not - it is a scam, log it
            //if it does - check its creation time and if current time is more than js check time + 5 seconds behind, then it is also a scam!
            //only if two of those params are ok, then allow black
            session_write('jscheck_passed', true);
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $action = black($c, Cloaker::get_click_params());
            $action = JsAction::FromCloakerAction($action);
            if ($c->black->jsconnectAction === 'iframe') {
                $action->action = 'html_iframe';
            }else{
                $action->action = 'html_content';
            }
        }

        return $action;
    }
}