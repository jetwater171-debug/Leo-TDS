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

function get_cookie($name): string
{
    get_session(true);
    return $_COOKIE[$name] ?? $_SESSION[$name] ?? '';
}

function set_subid(): string
{
    //giving each user a unique ID - subid and saving it to cookies
    //or getting it from cookies if exists
    $cursubid = get_cookie('subid');
    if (empty($cursubid))
        $cursubid = uniqid();
    set_cookie('subid', $cursubid, '/');
    return $cursubid;
}

function set_px(): void
{
    $curpx = $_GET['px'] ?? '';
    if (empty($curpx)) return;
    set_cookie('px', $curpx, '/');
}

//if the user has already converted before with the same data return true
function has_conversion_cookies($data): bool
{
    $cdata = get_cookie('postmd5');
    $ctime = get_cookie('ctime');

    if (empty($ctime) || empty($cdata)) {
        set_conversion_cookies($data);
        return false;
    }

    $curmd5 = md5($data);
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

function set_conversion_cookies($data): void
{
    $curmd5 = md5($data);
    set_cookie('postmd5', $curmd5);
    set_cookie('ctime', (new DateTime())->getTimestamp());
}

function get_session($readOnly = false)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if ($readOnly)
            session_start(['read_and_close' => true]);
        else {
            //TODO:what for is this cookie_secure?
            ini_set("session.cookie_secure", 1);
            session_start();
        }
    }
}