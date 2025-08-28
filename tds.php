<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/main.php';

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
                if ($c->white->jsChecks->enabled) {
                    $action = jscheck($c);
                } else {
                    $action = black($c, $clkr->click_params);
                }
            }
        }
        return $action;
    }
}