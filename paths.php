<?php

function get_cloaker_path(bool $withPrefix = true, bool $withSlashEnd = true): string
{
    $domain = $_SERVER['HTTP_HOST'];
    if ($withPrefix) {
        $prefix = is_https() ? 'https://' : 'http://';
        $fullpath = $prefix . $domain . '/';
    } else {
        $fullpath = $domain . '/';
    }
    $script_path = array_values(array_filter(explode("/", $_SERVER['SCRIPT_NAME']), 'strlen'));
    array_pop($script_path);

    if (count($script_path) > 0) {
        //Dirty hack for js-connections
        if ($script_path[count($script_path) - 1] === 'js') {
            array_pop($script_path);
        }
        if (count($script_path) > 0) {
            $fullpath .= implode('/', $script_path);
        }
    }

    if ($withSlashEnd && !str_ends_with($fullpath, '/')) {
        $fullpath .= '/';
    } elseif (!$withSlashEnd && str_ends_with($fullpath, '/')) {
        $fullpath = substr($fullpath, 0, -1);
    }

    return $fullpath;
}

function is_https(): bool
{
    if (str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')) {
        return true; //for debug
    }

    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $isSecure = true;
    } elseif (
        !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
        !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
    ) {
        $isSecure = true;
    } elseif ($_SERVER['SERVER_PORT'] == 443) {
        $isSecure = true;
    }
    return $isSecure;
}
