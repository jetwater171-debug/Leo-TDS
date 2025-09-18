<?php
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/logging.php';

class MacrosProcessor
{
    private ?string $subid;
    private ?array $clickParams;
    public function __construct(?string $subid = null, ?array $clickParams = null)
    {
        $this->subid = $subid ?? get_subid();
        $this->clickParams = $clickParams;
    }

    public function replace_html_macros($html): string
    {
        $html = preg_replace('/\{subid\}/', $this->subid, $html);

        $px = get_cookie('px');
        $html = preg_replace('/\{px\}/', $px, $html);
        return $html;
    }

    public function replace_url_macros($url): string
    {
        if (empty($url)) return "";
        $url_components = parse_url($url);
        parse_str($url_components['query'] ?? '', $query_array);

        // Iterate over the $sub_ids and replace the keys
        foreach ($query_array as $qk => $qv) {
            if (empty($qv))
                continue;
            if ($qv[0] !== '{' || $qv[strlen($qv) - 1] !== '}')
                continue; //we need only macroses

            $macro = substr($qv, 1, strlen($qv) - 2);
            $macroValue = $this->get_macro_value($macro);
            if ($macroValue === false)
                continue; //HINT: should we log $url?
            $query_array[$qk] = $macroValue;
        }

        // Build the new query string
        $new_query = http_build_query($query_array);

        // Rebuild the URL
        $new_url = $url_components['scheme'] . '://' . $url_components['host'];
        if (isset($url_components['path'])) {
            $new_url .= $url_components['path'];
        }
        if ($new_query) {
            $new_url .= '?' . $new_query;
        }

        return $new_url;
    }

    private function get_macro_value($macro): string|bool
    {
        global $db;
        
        return match(true) {
            $macro === 'subid' => $this->subid ?? false,
            $macro === 'domain' => $_SERVER['HTTP_HOST'],
            $macro === 'time' => time(),
            
            // Click parameter names
            in_array($macro, ['ip', 'country', 'lang', 'os', 'osver', 'client', 'clientver', 'device', 'brand', 'model', 'isp', 'ua', 'preland', 'land', 'status']) => 
                $this->getClickParam($macro, $db),
            
            // Custom click parameters (c.*)
            str_starts_with($macro, 'c.') => 
                $this->getCustomClickParam($macro, $db),
            
            // Hash macros (hash:*)
            str_starts_with($macro, 'hash:') => 
                $this->getHashedMacro($macro),
            
            // Random macros (random:*)
            str_starts_with($macro, 'random:') => 
                $this->getRandomMacro($macro),
            
            // Unknown macro
            default => $this->logUnknownMacro($macro)
        };
    }
    
    private function getClickParam($macro, $db): string|bool
    {
        if (!empty($this->clickParams))
            return $this->clickParams[$macro];
        if (!empty($this->subid)) {
            $click = $db->get_clicks_by_subid($this->subid, true);
            return $click[$macro];
        }
        add_log("macros", "Couldn't get macros $macro value. Clickparams and subid not set!");
        return false;
    }
    
    private function getCustomClickParam($macro, $db): string|bool
    {
        if (empty($this->subid)) {
            add_log("macros", "Couldn't get macros $macro value from DB. Subid not set!");
            return false;
        }
        
        $click = $db->get_clicks_by_subid($this->subid, true);
        if (count($click['params']) == 0) {
            add_log("macros", "Couldn't find click macro $macro value. Subid:{$this->subid}, Params are EMPTY!");
            return false;
        }
        
        $p = $click['params'];
        $cmacro = substr($macro, 2);
        if (array_key_exists($cmacro, $p)) {
            return $p[$cmacro];
        }
        
        add_log("macros", "Couldn't find click macro $macro value. Subid:{$this->subid}, Params:" . json_encode($p));
        return false;
    }
    
    private function getHashedMacro($macro): string|bool
    {
        $toHash = substr($macro, 5);
        $toHashValue = $this->get_macro_value($toHash);
        if ($toHashValue === false) {
            add_log("macros", "Couldn't find macro $toHash value to hash. Subid:{$this->subid}");
            return false;
        }
        $hashed = md5($toHashValue);
        add_log("macros", "Hashing $toHashValue to $hashed");
        return $hashed;
    }
    
    private function getRandomMacro($macro): int
    {
        $range = explode('-', substr($macro, 7));
        $selected = rand($range[0], $range[1]);
        add_log("macros", "Got random $selected from range " . implode('-', $range));
        return $selected;
    }
    
    private function logUnknownMacro($macro): bool
    {
        add_log("macros", "Couldn't find macros: $macro. Subid:{$this->subid}");
        return false;
    }
}