<?php
//Language detection
require_once __DIR__ . '/bases/language.php';
//Device/Model/Browser/Platform detection
require_once __DIR__ . '/bases/device/autoload.php';
require_once __DIR__ . '/bases/device/ClientHints.php';
require_once __DIR__ . '/bases/device/DeviceDetector.php';
require_once __DIR__ . '/bases/device/Spyc.php';
//DeviceDetector caching
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiGetCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiDeleteCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiPutCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/Cache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/FlushableCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/ClearableCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiOperationCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/CacheProvider.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/FileCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/PhpFileCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/CacheProvider.php';

require_once __DIR__ . '/bases/device/Cache/CacheInterface.php';
require_once __DIR__ . '/bases/device/Cache/DoctrineBridge.php';
//GEO and referer
require_once __DIR__ . '/bases/iputils.php';
require_once __DIR__ . '/bases/ipcountry.php';

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Cache\DoctrineBridge;
class Cloaker
{
    private array $filters;
    public string $block_reason = "";
    public array $click_params = [];

    public function __construct(array $filters=[], array $prefill=[])
    {
        DebugMethods::start("YWBCoreConstruct");
        $this->filters = $filters;
        $this->click_params = self::get_click_params($prefill);
        DebugMethods::stop("YWBCoreConstruct");
    }

    public static function get_click_params(array $prefill=[]): array
    {
        ClientHints::requestClientHints();
        $a = [];
        $a['ua'] = $prefill['tds_ua'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $a['referer'] = $prefill['tds_ref'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        $lang = $prefill['tds_lang'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $a['lang'] = LanguageDetector::detect($lang);
        
        $clientHints = ClientHints::factory($prefill['tds_client_hints'] ?? $_SERVER); 
        $dd = new DeviceDetector($a['ua'], $clientHints);

        DebugMethods::start("YWBCoreDeviceDetector");
        $phpFileCache = new Doctrine\Common\Cache\PhpFileCache(__DIR__ . '/cache/');
        $dd->setCache(new DoctrineBridge($phpFileCache));
        $dd->parse();
        $clientInfo = $dd->getClient();
        $a['client'] = $clientInfo['name'];
        $a['clientver'] = $clientInfo['version'];
        DebugMethods::stop("YWBCoreDeviceDetector");
        
        $osInfo = $dd->getOs();
        $a['os'] = $osInfo['name'];
        $a['osver'] = $osInfo['version'];
        $a['device'] = $dd->getDeviceName();
        $a['brand'] = $dd->getBrandName();
        $a['model'] = $dd->getModel();
        
        DebugMethods::start("YWBCoreMaxMind");
        $a['ip'] = getip($prefill['tds_ip']??$_SERVER);
        $a['country'] = getcountry($a['ip']);
        $a['isp'] = getisp($a['ip']);
        DebugMethods::stop("YWBCoreMaxMind");
        $a['url'] = $prefill['tds_qs'] ?? $_SERVER['REQUEST_URI'];
        //TODO: check prefill url
        //TODO: add query string as an array
        return $a;
    }

    private function match_filters(bool $all, array|null $filters): bool
    {
        for ($i = 0; $i < count($filters); $i++) {
            $f = $filters[$i];
            if (!empty($f['condition'])) //this is a filter group
                $fRes = $this->match_filters($f['condition'] === 'AND', $f['rules']);
            else
                $fRes = $this->match_filter($f);
            if ($all && !$fRes)
                return false;
            if (!$all && $fRes)
                return true;
        }
        return $all; //if we are here, then for AND all are true and for OR all are false
    }


    private function match_filter(array $filter): bool
    {
        $val = $filter['value'] ?? '';

        switch ($filter['id']) {
            case 'os':
                return $this->operator($val, $filter['operator'], 'os');
            case 'country':
                return $this->operator($val, $filter['operator'], 'country');
            case 'language':
                return $this->operator($val, $filter['operator'], 'lang');
            case 'useragent':
                return $this->operator($val, $filter['operator'], 'ua');
            case 'isp':
                return $this->operator($val, $filter['operator'], 'isp');
            case 'url':
                return $this->operator($val, $filter['operator'], 'url');
            case 'referer':
                return $this->operator($val, $filter['operator'], 'referer');
            case 'vpntor':
                $vpnDetected = $this->is_proxy_or_vpn($this->click_params['ip']);
                if ($val === 0 && $vpnDetected)
                    return true;
                if ($val === 1 && !$vpnDetected)
                    return true;
                $this->block_reason = $filter['id'];
                return false;
            case 'ipbase':
                $inBase = $this->is_ip_in_base($this->click_params['ip'], $val);
                if ($filter['operator'] === 'contains' && $inBase)
                    return true;
                if ($filter['operator'] === 'not_contains' && !$inBase)
                    return true;
                $this->block_reason = $filter['id'];
                return false;
        }
        return true;
    }

    public function is_bad_click(): bool
    {
        try {
            DebugMethods::start("YWBCoreCheck");
            if (!array_key_exists('rules', $this->filters))
                return false;
            return !$this->match_filters($this->filters['condition'] === 'AND', $this->filters['rules']);
        } finally {
            DebugMethods::stop("YWBCoreCheck");
        }
    }

    private function is_proxy_or_vpn($ip): bool
    {
        //checks the commonly added by proxies header X-Forwarded-For
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $xip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $xip = explode(", ", $xip);
            if (count($xip) <= 1) {
                $xip = explode(",", $xip[0]);
            }
            if (!empty($xip[0])) {
                $xip = $xip[0];
            }
            if ($xip!==$ip) return true;
        }

        //perform checks using 3rd party services, SLOW
        $blackbox = $this->is_bad_by_blackbox($ip);
        if ($blackbox !== null) return $blackbox;
        $ipintel = $this->is_bad_by_ipintel($ip);
        return ($ipintel === null ? false : $ipintel);
    }

    private function is_bad_by_blackbox($ip): ?bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://blackbox.ipinfo.app/lookup/' . $ip);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            add_log('trace', "is_bad_by_blackbox: $ip from blackbox: $http_code");
            return null;
        }

        return $res === 'Y';
    }

    private function is_bad_by_ipintel($ip): ?bool
    {
        $contactEmail = "support@" . $_SERVER['HTTP_HOST'];
        $banOnProbability = 0.99;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_URL, "http://check.getipintel.net/check.php?ip=$ip&contact=$contactEmail&flags=m");
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno > 0) {
            add_error_log("is_bad_by_ipintel: $ip from ipintel: $errno - $error");
            return null;
        }

        if ($response === false) {
            add_error_log("is_bad_by_ipintel: $ip from ipintel: response is false");
            return null;
        }

        if ($response >= $banOnProbability) {
            return true;
        } else {
            if ($response < 0 || strcmp($response, "") == 0) {
                add_error_log("is_bad_by_ipintel: $ip from ipintel: response is incorrect");
                return null;
            }
            return false;
        }
    }

    private function is_ip_in_base($ip, $baseFileName): bool
    {
        $base_full_path = __DIR__ . "/bases/" . $baseFileName;
        if (!file_exists($base_full_path))
            return false;
        $cidr = file($base_full_path, FILE_IGNORE_NEW_LINES);
        return IpUtils::checkIp($ip, $cidr);
    }

    private function operator(string $val, string $operator, string $param): bool
    {
        $check = true;
        switch ($operator) {
            case 'in':
                $values = explode(',', $val);
                $check = $this->in_arrayi($this->click_params[$param], $values);
                break;
            case 'not_in':
                $values = explode(',', $val);
                $check = !$this->in_arrayi($this->click_params[$param], $values);
                break;
            case 'contains':
                $values = explode(',', $val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value))
                        continue;
                    if (stripos($this->click_params[$param], $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if (!$contains)
                    $check = false;
                break;
            case 'not_contains':
                $values = explode(',', $val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value))
                        continue;
                    if (stripos($this->click_params[$param], $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if ($contains)
                    $check = false;
                break;
            case 'not_equal':
                $check = strtolower($this->click_params[$param]) !== strtolower($val);
                break;
            default:
                throw new Exception("No operator $operator defined for $param check!");
        }
        if (!$check) {
            $this->block_reason = $param;
            return false;
        }
        return true;
    }

    private function in_arrayi($needle, $haystack)
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
}
