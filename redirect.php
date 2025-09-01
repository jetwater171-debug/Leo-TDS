<?php
require_once __DIR__ . '/macros.php';

function redirect($url, $redirect_type = 302, $rep_macros = false): void
{
    $url = urldecode($url);
    if ($rep_macros) {
        $mp = new MacrosProcessor();
        $url = $mp->replace_url_macros($url);
    }
    header('X-Robots-Tag: noindex, nofollow');
    header('Location: ' . $url, true, $redirect_type);
}

function jsredirect(string $url, bool $add_script_tag=true, bool $rep_macros = false): string
{
    $url = urldecode($url);
    if ($rep_macros) {
        $mp = new MacrosProcessor();
        $url = $mp->replace_url_macros($url);
    }
    $js_code = "window.location='$url'";
    if ($add_script_tag) {
        return "<script type='text/javascript'>{$js_code}</script>";
    } else {
        return $js_code;
    }
}

function insert_subs_into_url(array $subIds, array $currentParams, string $redirectUrl)
{
    $preset = ['subid', 'prelanding', 'landing'];
    
    $redirectParsed = parse_url($redirectUrl);
    parse_str($redirectParsed['query'] ?? '', $redirectParams);

    foreach ($subIds as $sub) {
        $name = $sub->name;
        $rewrite = $sub->rewrite;

        if (in_array($name, $preset) && !empty(get_cookie($name))) {
            $redirectParams[$rewrite] = get_cookie($name);
        }
        else if (isset($currentParams[$name])) {
            $redirectParams[$rewrite] = $currentParams[$name];
        }
    }

    $newQuery = http_build_query($redirectParams);

    $path = $redirectParsed['path']??'';
    $finalRedirectUrl = $redirectParsed['scheme'] . '://' . $redirectParsed['host'] . $path;
    if (!empty($newQuery)) {
        $finalRedirectUrl .= '?' . $newQuery;
    }

    return $finalRedirectUrl;
}
