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

    public static function getJsAction(array $prefill) : JsAction
    {
        global $db;
        $dbCamp = $db->get_campaign_by_domain();
        if ($dbCamp === false) {
            $action = traficback(Cloaker::get_click_params($prefill));
        } else {
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $clkr = new Cloaker($c->filters, $prefill);

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
            return $action;
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
        else 
        {
            $jscheck_start_time = session_read('jscheck_pending');
            $current_time = time();
            $c = new Campaign($dbCamp['id'], $dbCamp['settings']);
            $max_execution_time = $c->white->jsChecks->timeout / 1000; // Convert from milliseconds to seconds
            $allowed_time = $jscheck_start_time + $max_execution_time + 5; // Add 5 second buffer
            
            if ($current_time > $allowed_time) {
                // Attempt to pass JS check after timeout
                $db->add_white_click(Cloaker::get_click_params(), 'jscheck_scam_timeout', $dbCamp['id']);
                session_remove('jscheck_pending');
                if (DebugMethods::on()) {
                    $action = new JsAction("white", "js", "console.log('Debug: JS check scam - timeout exceeded');");
                } else {
                    $action = new JsAction("white", "error", "");
                }
                return $action;
            }
            
            // All security checks passed - remove pending flag and allow black
            session_remove('jscheck_pending');
            session_write('jscheck_passed', true);
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