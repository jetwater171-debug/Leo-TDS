<?php
require_once __DIR__ . '/js/obfuscator.php';
require_once __DIR__ . '/bases/ipcountry.php';
require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/htmlinject.php';
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/debug.php';
function load_content_with_include($url): string
{
    ob_start();
    $fulldir = __DIR__ . '/' . $url;
    if (
    str_ends_with($fulldir, ".php") ||
    str_ends_with($fulldir, ".html") ||
    str_ends_with($fulldir, ".htm")
    ) {
        require $fulldir;
    }
    // Check for each file and require/include the first one that exists
    else if (file_exists($fulldir . '/index.php')) {
        require $fulldir . '/index.php';
    } elseif (file_exists($fulldir . '/index.html')) {
        require $fulldir . '/index.html';
    } elseif (file_exists($fulldir . '/index.htm')) {
        require $fulldir . '/index.htm';
    }
    //Not Found
    else {
        http_response_code(404);
        echo $url . ' Not Found!';
    }

    $html = ob_get_clean();
    return $html;
}

//Load content of black landing from another folder
function load_prelanding($url, $land_number): string
{
    global $c; //campaign
    $fullpath = get_abs_from_rel($url);

    $html = load_content_with_include($url);
    $html = remove_scrapbook($html);

    //чистим тег <head> от всякой ненужной мути
    $html = fix_head_add_base($html, $fullpath);
    $html = fix_src($html);

    $mp = new MacrosProcessor();
    $html = $mp->replace_html_macros($html);
    $html = fix_phone_and_name($html);
    //adding subs into forms
    $html = insert_subs_into_forms($html);

    //removing target=_blank
    $html = preg_replace('/(<a[^>]+)(target="_blank")/i', "\\1", $html);

    $cloaker = get_cloaker_path();
    $querystr = $_SERVER['QUERY_STRING']??'';
    
    // Function to generate landing URL with specific landing number
    $getLandingUrl = function($landNum) use ($cloaker, $c, $querystr) {
        return $cloaker . 'landing.php?l=' . $landNum . "&campId=" . $c->campaignId . (!empty($querystr) ? '&' . $querystr : '');
    };

    // Generate base replacement for {offer}
    $replacement = $getLandingUrl($land_number);

    //if we will be replacing the prelanding with the landing, then the landing should be opened in a new window
    if ($c->scripts->replacePrelanding) {
        $replacement .= '" target="_blank"';
        $url = $mp->replace_url_macros($c->scripts->replacePrelandingAddress); //replacing macros
        $html = insert_file_content($html, 'replaceprelanding.js', '</body>', true, true, '{REDIRECT}', $url);
    }

    // replace the default {offer} macro
    $html = preg_replace('/\{offer\}/', $replacement, $html);
    
    // replace all numbered offers {offer:N} with corresponding landing numbers (N-1)
    $html = preg_replace_callback('/\{offer:(\d+)\}/', function($matches) use ($getLandingUrl, $c) {
        $landNum = intval($matches[1]) - 1; // Convert offer number to 0-based landing number
        $replacement = $getLandingUrl($landNum);
        if ($c->scripts->replacePrelanding) {
            $replacement .= '" target="_blank"';
        }
        return $replacement;
    }, $html);

    $url = $mp->replace_url_macros($c->scripts->backfixAddress); 
    $second = $mp->replace_url_macros($c->scripts->backfixSecondAddress); 
    $html = add_backfix($html, $url, $second);

    $html = add_images_lazy_load($html);
    return $html;
}

//Load content of black landing from another folder
function load_landing($url)
{
    global $c; //campaign

    $fullpath = get_abs_from_rel($url);

    $html = load_content_with_include($url);
    $html = remove_scrapbook($html);
    $html = fix_head_add_base($html, $fullpath);
    $html = fix_src($html);

    $query = http_build_query($_GET);
    $html = preg_replace_callback(
    '/\saction=[\'\"]([^\'\"]+)[\'\"]/',
    function ($matches) use ($query) {
        $originalAction = urlencode($matches[1]);
        $send = " action=\"../send.php?original_action={$originalAction}";
        if ($query !== '')
            $send .= "&" . $query;
        $send .= "\"";
        return $send;
    },
    $html
    );

    $mp = new MacrosProcessor();
    //if we will be replacing the landing when going to the Thank You page, then the Thank You page should open in a new window
    if ($c->scripts->replaceLanding) {
        $replacelandurl = $mp->replace_url_macros($c->scripts->replaceLandingAddress); //replace macros
        $html = insert_file_content($html, 'replacelanding.js', '</body>', true, true, '{REDIRECT}', $replacelandurl);
    }

    //add subs into forms
    $html = insert_subs_into_forms($html);

    $html = insert_file_content($html, "fixanchors.js", "<body", false, true);
    
    $html = $mp->replace_html_macros($html);
    //replace phone field with more convenient type - tel + add autocomplete
    $html = fix_phone_and_name($html);


    //adding backfix ONLY if we don't have a prelanding, cause prelanding will have it
    if ($c->black->preland->action==='none') {
        $url = $mp->replace_url_macros($c->scripts->backfixAddress); 
        $second = $mp->replace_url_macros($c->scripts->backfixSecondAddress); 
        $html = add_backfix($html, $url, $second);
    }
    
    $html = add_images_lazy_load($html);

    return $html;
}

function fix_head_add_base($html, $fullpath)
{
    $html = preg_replace('/<head [^>]+>/', '<head>', $html);
    $html = insert_after_tag($html, "<head>", "<base href='" . $fullpath . "'>");
    return $html;
}

function fix_src($html): string
{
    $src_regex = '/(<[^>]+src=[\'\"])\/([^\/][^>]*>)/';
    return preg_replace($src_regex, "\\1\\2", $html);
}

function add_input_attribute($html, $regex, $attribute)
{
    if (preg_match_all($regex, $html, $matches, PREG_OFFSET_CAPTURE)) {
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            if (!str_contains($matches[0][$i][0], $attribute)) {
                $replacement = "<input {$attribute}" . substr($matches[0][$i][0], 6);
                $html = substr_replace($html, $replacement, $matches[0][$i][1], strlen($matches[0][$i][0]));
            }
        }
    }
    return $html;
}

//if type of phone field is text, change it to tel for more convenient input on mobile
//add autocomplete to name and phone fields
//add required if it's not there
function fix_phone_and_name($html)
{
    //fix type=text to type=tel
    $firstr = '/(<input[^>]*name="(phone|tel)"[^>]*type=")(text)("[^>]*>)/';
    $secondr = '/(<input[^>]*type=")(text)("[^>]*name="(phone|tel)"[^>]*>)/';
    $html = preg_replace($secondr, "\\1tel\\3", $html);
    $html = preg_replace($firstr, "\\1tel\\4", $html);

    $telregex = '/<input[^>]*type="tel"[^>]*>/';
    $html = add_input_attribute($html, $telregex, 'autocomplete="tel"');
    $html = add_input_attribute($html, $telregex, 'required');

    $nameregex = '/<input[^>]*name="name"[^>]*>/';
    $html = add_input_attribute($html, $nameregex, 'autocomplete="name"');
    $html = add_input_attribute($html, $nameregex, 'required');

    return $html;
}

function add_images_lazy_load($html)
{
    global $c; //campaign
    if (!$c->scripts->imagesLazyLoad)
        return $html;
    $html = preg_replace('/(<img\s)((?!.*?loading=([\'\"])[^\'\"]+\3)[^>]*)(>)/s', '<img loading="lazy" \\2\\4', $html);
    return $html;
}

//load white page from FOLDER
function load_white_content($url):string
{
    $html = load_content_with_include($url);
    $baseurl = '/' . $url . '/';
    //переписываем все относительные src и href (не начинающиеся с http)
    $html = rewrite_relative_urls($html, $baseurl);

    //adding no-referer,noindex,nofollow
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);
    $html = remove_scrapbook($html);

    return $html;
}

//loading white page with CURL
function load_white_curl(string $url):string
{
    $res = get($url);
    $html = $res['content'];
    $html = rewrite_relative_urls($html, $url);

    //remove everything unneeded
    $html = preg_replace('/(<meta property=\"og:url\" [^>]+>)/', "", $html);
    $html = preg_replace('/(<link rel=\"canonical\" [^>]+>)/', "", $html);
    //killing tracking scripts
    $tracking_scripts = array(
    'google_analytics' => 'https://www.google-analytics.com/analytics.js',
    'google_tag_manager' => 'https://www.googletagmanager.com/gtag/js',
    'facebook_pixel' => 'connect.facebook.net/en_US/fbevents.js',
    'twitter_conversion' => 'https://platform.twitter.com/oct.js',
    'linkedin_insight_tag' => 'https://snap.licdn.com/li.lms-analytics/insight.min.js',
    'pinterest_tag' => '//s.pinimg.com/ct/core.js',
    'adobe_dtm' => 'https://assets.adobedtm.com',
    'adobe_analytics' => '.sc.omtrdc.net/s/s_code.js',
    'hubspot_tracking_code' => '//js.hs-scripts.com/',
    'bing_ads' => '//bat.bing.com/bat.js',
    'crazy_egg' => '//script.crazyegg.com/pages/scripts/',
    'yandex_metrika' => 'https://mc.yandex.ru/metrika/tag.js',
    'hotjar' => 'static.hotjar.com/c/hotjar'
    );
    foreach ($tracking_scripts as $key => $url) {
        $pattern = '#<script[^>]*(src="[^"]*' . preg_quote($url) . '[^"]*")[^>]*>.*?</script>|<script[^>]*>[^<]*' . preg_quote($url) . '[^<]*</script>#is';
        $html = preg_replace($pattern, '', $html);
    }
    //removing all noscript tags
    $pattern = '#<noscript>.*?</noscript>#is';
    $html = preg_replace($pattern, '', $html);
    //adding some additional tags to head
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

    return $html;
}

function load_js_testpage():string
{
    $test_page = load_content_with_include('js/tests/page.html');
    return add_js_testcode($test_page);
}

function add_js_testcode(string $html):string
{
    $jsCode = "<script src='./js/index.php'></script>";
    $needle = '<head>';
    if (!str_contains($html,$needle)) $needle = '<body>';
    return insert_after_tag($html, $needle, $jsCode);
}

function add_backfix(string $html, $url, $second):string
{
    $debug = DebugMethods::On()?'true':'false';
    $jsCode = "
    <script src='./scripts/backfix.js' 
        data-backlink='{$url}' 
        data-showcaselink='{$second}'
        data-traceenabled='{$debug}'
        data-redirect='false'
        data-isoff='false'>
    </script>";
    $needle = '<head>';
    if (!str_contains($html,$needle)) $needle = '<body>';
    return insert_after_tag($html, $needle, $jsCode);
}

//inserts all subs into hidden fields of each form
function insert_subs_into_forms($html)
{
    global $c; //campaign
    $all_subs = '';
    $preset = ['subid', 'prelanding', 'landing'];
    foreach ($c->subIds as $sub) {
        $key = $sub->name;
        $value = $sub->rewrite;

        if (in_array($key, $preset) && !empty(get_cookie($key))) {
            $html = preg_replace('/(<input[^>]*name="' . $value . '"[^>]*>)/', "", $html);
            $all_subs = $all_subs . '<input type="hidden" name="' . $value . '" value="' . get_cookie($key) . '"/>';
        } elseif (!empty($_GET[$key])) {
            $html = preg_replace('/(<input[^>]*name="' . $value . '"[^>]*>)/', "", $html);
            $all_subs = $all_subs . '<input type="hidden" name="' . $value . '" value="' . $_GET[$key] . '"/>';
        }
    }
    if (!empty($all_subs)) {
        return insert_after_tag($html, '<form', $all_subs);
    }
    return $html;
}



//rewrite relative urls (not starting with http or //)
function rewrite_relative_urls($html, $url)
{
    $modified = preg_replace('/\ssrc=[\'\"](?!http|\/\/|data:)([^\'\"]+)[\'\"]/', " src=\"$url\\1\"", $html);
    $modified = preg_replace('/\shref=[\'\"](?!http|#|\/\/)([^\'\"]+)[\'\"]/', " href=\"$url\\1\"", $modified);
    $modified = preg_replace('/background-image:\s*url\((?!http|#|\/\/)([^\)]+)\)/', "background-image: url($url\\1)", $modified);
    return $modified;
}

function remove_scrapbook($html)
{
    $modified = preg_replace('/data\-scrapbook\-source=[\'\"][^\'\"]+[\'\"]/', '', $html);
    $modified = preg_replace('/data\-scrapbook\-create=[\'\"][^\'\"]+[\'\"]/', '', $modified);
    return $modified;
}