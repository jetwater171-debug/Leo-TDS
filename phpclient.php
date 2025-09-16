<?php
/**
 * YellowCloaker PHP Client Library
 * 
 * Simple include file for connecting external PHP sites to the cloaker.
 * Just include this file in your index.php like:
 * require_once __DIR__ . '/fromfolder/cloaker_client.php';
 */

define("YC_API_KEY", "test");
define("YC_API_URL", "http://localhost:8080/fromfolder/phpapi.php");

//if called directly return 404, haha
if (__FILE__ === $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit;
}

class YellowCloakerClient 
{
    public function connect()
    {
        $this->requestClientHints();
        $params = $this->collectParams();
        $response = $this->sendRequest($params);
        return $response;
    }
    
    public function process($response) 
    {
        if (!$response || !isset($response['action'])) 
            return;
        
        switch ($response['action']) {
                
            case 'jscheck':
            case 'black_html':
                echo $response['content'];
                exit;
                
            case 'black_redirect':
                $redirect_type = $response['redirect_type'] ?? 302;
                http_response_code($redirect_type);
                header('Location: ' . $response['content']);
                exit;
                
            default:
                break;
        }
    }
    
    public function check() 
    {
        $response = $this->connect();
        $this->process($response);
    }
    
    private function collectParams() 
    {
        $params = [
            'api_key' => YC_API_KEY,
            'tds_ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'tds_ref' => $_SERVER['HTTP_REFERER'] ?? '',
            'tds_url' => $_SERVER['REQUEST_URI'] ?? '/',
            'tds_query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'tds_host' => $_SERVER['HTTP_HOST'] ?? '',
            'tds_lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        ];

        $params['tds_ip']=[];
        $params['tds_ip']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            $params['tds_ip']['HTTP_CF_CONNECTING_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $params['tds_ip']['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        
        //adding client hints if they are present
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_SEC_CH_UA_') === 0) {
                if (!array_key_exists('tds_client_hints', $params))
                    $params['tds_client_hints'] = [];
                $params['tds_client_hints'][$key] = $value;
            }
        }
        
        return $params;
    }
    
    private function sendRequest($params) 
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => YC_API_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: YellowCloaker-PHP-Client/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $this->log("YellowCloaker cURL Error: " . $curl_error);
            return null;
        }
        
        if ($http_code !== 200) {
            $this->log("YellowCloaker HTTP Error: " . $http_code);
            return null;
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("YellowCloaker JSON Error: " . json_last_error_msg(). "\n" . $response);
            return null;
        }
        
        return $decoded;
    }
    
    private function getip():string
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            return $_SERVER['HTTP_CF_CONNECTING_IP'];

        return $_SERVER['REMOTE_ADDR'];
    }

    private function requestClientHints(): void
    {
        $headers = [
            'Sec-CH-UA', 'Sec-CH-UA-Arch', 'Sec-CH-UA-Bitness',
            'Sec-CH-UA-Full-Version', 'Sec-CH-UA-Full-Version-List',
            'Sec-CH-UA-Mobile', 'Sec-CH-UA-Platform', 'Sec-CH-UA-Platform-Version',
            'Sec-CH-UA-WoW64', 'Sec-CH-UA-Model'
        ];
        $headers_str = implode(', ', $headers);
        header('Accept-CH: ' . $headers_str, true);
        header('Critical-CH: ' . $headers_str, true);
        header('Vary: ' . $headers_str, true);
    }

    private function log($msg, $addIp = false)
    {
        $dir = __DIR__ . "/ycclogs";
        if (!file_exists($dir)) 
            mkdir($dir, 0777, true);
        $date = date("d.m.y");
        $fileName = "$dir/$date.log";
        $file = fopen($fileName, 'a+');
        $time = date("Y-m-d H:i:s");
        if ($addIp) {
            $ip = $this->getip();
            $time .= " $ip";
        }
        $msg = "$time $msg\n";
        fwrite($file, $msg);
        fflush($file);
        fclose($file);
    }

}

$ycc = new YellowCloakerClient();
register_shutdown_function([$ycc, 'check']);
