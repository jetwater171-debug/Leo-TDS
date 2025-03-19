<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/campaign.php';
global $db;

$curLink = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$subid = $_REQUEST['subid'] ?? '';
if ($subid === '') {
    http_response_code(500);
    echo "No subid found! Url: $curLink";
    return;
}
$status = $_REQUEST['status'] ?? '';
if ($status === '') {
    http_response_code(500);
    echo "No status found! Url: $curLink";
    return;
}
$payout = $_REQUEST['payout'] ?? '';
if ($payout === '') {
    http_response_code(500);
    echo "No payout found! Url: $curLink";
    return;
}
$click = $db->get_clicks_by_subid($subid,true);
if (empty($click)){
    http_response_code(500);
    echo "No click for subid $subid found! Url: $curLink";
    return;
}
$cs = $db->get_campaign_settings($click['campaign_id']);
$c = new Campaign($click['campaign_id'],$cs);

$inner_status = '';
switch (strtolower($status)) {
    case strtolower($c->postback->leadStatusName):
        $inner_status = 'Lead';
        break;
    case strtolower($c->postback->purchaseStatusName):
        $inner_status = 'Purchase';
        break;
    case strtolower($c->postback->rejectStatusName):
        $inner_status = 'Reject';
        break;
    case strtolower($c->postback->trashStatusName):
        $inner_status = 'Trash';
        break;
}

if ($inner_status === '') {
    http_response_code(500);
    echo "Status $status is unknown! Url: $curLink";
    return;
}

if ($subid === '' || $status === '')
    $msg = "Error! No subid or status! {$curLink}";
else
    $msg = "$subid, $status, $payout";
add_log("postback", $msg);

//automatic currency conversion to USD
$currency = strtoupper($_REQUEST['currency'] ?? 'USD');
$payout = convert_currency($payout, $currency);

$updated = $db->update_status($subid, $inner_status, $payout);

if ($updated) {
    process_s2s_posbacks($c->postback->s2sPostbacks, $inner_status, $subid);
    http_response_code(200);
    echo "Postback for subid $subid with status $status and payout $payout USD accepted.";
} else {
    http_response_code(404);
    echo "Postback for subid $subid with status $status and payout $payout $currency NOT accepted! Subid NOT FOUND.";
}

function convert_currency($amount, $from): float
{
    if (empty($from) || $from ==='USD') return $amount;
    try{
        $ffCurrencies = [
            "AUD","BGN","BRL","CAD","CHF","CNY","CZK","DKK","EUR","GBP","HKD","HUF","IDR",
            "ILS","INR","ISK","JPY","KRW","MXN","MYR","NOK","NZD","PHP","PLN","RON","SEK",
            "SGD", "THB","TRY","USD","ZAR" 
        ];
        
        if (in_array($from, $ffCurrencies)) {
            $url = "https://api.frankfurter.dev/v1/latest?base=$from&symbols=USD";
            $res = json_decode(file_get_contents($url), true);
            $rate = $res['rates']['USD'];
            if (empty($rate)) {
                add_log('errors',"Currency conversion failed for $from to USD! Rate is empty! Url: $url");
                return $amount;
            }
            return round($amount * $rate, 2);
        }

        $turGovCurrencies = [ 'RUB','PKR','QAR','KRW','AZN','AED' ];
        if (in_array($from, $turGovCurrencies)) {
            // Get the XML file from Turkish Central Bank
            $xmlUrl = 'https://www.tcmb.gov.tr/kurlar/today.xml';
            
            // Try to use cached version if available
            $xmlFile = __DIR__ . '/tur.xml';
            $useCache = false;
            
            if (file_exists($xmlFile)) {
                $fileTime = filemtime($xmlFile);
                $currentTime = time();
                // Use cached file if it's less than 6 hours old
                if (($currentTime - $fileTime) < 21600) {
                    $useCache = true;
                    $xmlContent = file_get_contents($xmlFile);
                }
            }
            
            if (!$useCache) {
                $curlRes = get($xmlUrl);
                if ($curlRes['error']) {
                    add_log('errors', "Curl error while trying to get Turkish Central Bank rates: " . $curlRes['error']);
                    return $amount;
                }
                
                $xmlContent = $curlRes['content'];
                // Cache the XML content
                file_put_contents($xmlFile, $xmlContent);
            }
            
            // Parse the XML
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                add_log('errors', "Failed to parse Turkish Central Bank XML data");
                return $amount;
            }
            
            // Find the currency in the XML
            $rate = null;
            foreach ($xml->Currency as $currency) {
                $currencyCode = (string)$currency['CurrencyCode'];
                if ($currencyCode === $from) {
                    $rate = (float)$currency->CrossRateUSD;
                    break;
                }
            }
            
            if ($rate === null || $rate <= 0) {
                add_log('errors', "Currency $from not found in Turkish Central Bank data or invalid rate");
                return $amount;
            }
            
            // Calculate USD amount (divide by rate for currencies where 1 USD = X currency)
            return round($amount / $rate, 2);
        }
        else{
            add_log('errors',"Currency $from is not supported by any conversion APIs!");
            return $amount;
        }
    }
    catch (Exception $e) {
        add_log('errors', "Currency conversion failed for $amount $from to USD: " . $e->getMessage());
        return $amount;
    }
}

function process_s2s_posbacks(array $s2s_postbacks, string $inner_status, string $subid): void
{
    $mp = new MacrosProcessor($subid);
    foreach ($s2s_postbacks as $s2s) {
        if (!in_array($inner_status, $s2s->events)) continue;
        if (empty($s2s->url)) continue;
        $final_url = str_replace('{status}', $inner_status, $s2s->url);
        $final_url = $mp->replace_url_macros($final_url);
        $s2s_res = '';
        switch ($s2s->method) {
            case 'GET':
                $s2s_res = get($final_url);
                break;
            case 'POST':
                $urlParts = explode('?', $final_url);
                if (count($urlParts) == 1)
                    $params = array();
                else
                    parse_str($urlParts[1], $params);
                $s2s_res = post($urlParts[0], $params);
                break;
        }
        add_log("postback", "{$s2s->method}, $final_url, $inner_status, {$s2s_res['info']['http_code']}");
    }
}