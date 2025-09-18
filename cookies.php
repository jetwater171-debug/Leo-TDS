<?php
function set_cookie($name, $value, $path = '/'): void
{
    $expires = time() + 60 * 60 * 24 * 5; //time to live for cookies - 5 days
    header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure", false);
    session_write($name, $value);
}

function session_write($name, $value){
    get_session();
    $_SESSION[$name] = $value;
    session_write_close();
}

function session_read($name){
    get_session(true);
    return $_SESSION[$name] ?? null;
}

function session_remove($name){
    get_session();
    unset($_SESSION[$name]);
    session_write_close();
}

function get_cookie($name): string
{
    get_session(true);
    return $_COOKIE[$name] ?? $_SESSION[$name] ?? '';
}

function get_subid(): string
{
    return get_cookie('subid');
}

function set_subid(): string
{
    //giving each user a unique ID - subid and saving it to cookies
    //or getting it from cookies if exists
    $cursubid = get_subid();
    if (empty($cursubid))
        $cursubid = uniqid();
    set_cookie('subid', $cursubid);
    return $cursubid;
}

function set_px(): void
{
    $curpx = $_GET['px'] ?? '';
    if (empty($curpx)) return;
    set_cookie('px', $curpx);
}

//if the user has already converted before with the same data return true
function has_conversion_cookies(array $data): bool
{
    $cdata = get_cookie('postmd5');
    $ctime = get_cookie('ctime');

    if (empty($ctime) || empty($cdata)) {
        set_conversion_cookies($data);
        return false;
    }

    $curmd5 = md5(json_encode($data));
    if ($cdata !== $curmd5) {
        set_conversion_cookies($data);
        return false;
    }

    $currentTimestamp = (new DateTime())->getTimestamp();
    $secondsDiff = $currentTimestamp - $ctime;
    if ($secondsDiff < 24 * 60 * 60) {
        set_cookie('ctime', $currentTimestamp);
        return true;
    }

    set_conversion_cookies($data);
    return false;
}

function set_conversion_cookies(array $data): void
{
    $curmd5 = md5(json_encode($data));
    set_cookie('postmd5', $curmd5);
    set_cookie('ctime', (new DateTime())->getTimestamp());
}

function get_session($readOnly = false)
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    
    session_set_cookie_params(["SameSite" => "None"]); //can be used from js connected ones
    session_set_cookie_params(["Secure" => "true"]); //https always
    session_set_cookie_params(["HttpOnly" => "false"]); //can be used from js code
    if ($readOnly)
        session_start(['read_and_close' => true]);
    else 
        session_start();
}