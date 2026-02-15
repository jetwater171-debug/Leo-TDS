<?php
// ── Direct Load: serve landing/white resources via 404 catch-all ──
// Included from index.php. Expects settings.php and cookies.php already loaded.

global $cloSettings;

$dlMimeTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'otf' => 'font/otf',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mp3' => 'audio/mpeg',
    'ogg' => 'audio/ogg',
    'pdf' => 'application/pdf',
    'xml' => 'application/xml',
    'txt' => 'text/plain',
    'map' => 'application/json',
];

// Parse the relative request path (strip base dir)
function dl_get_req_path(): string
{
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptDir !== '/' && $scriptDir !== '\\') {
        $reqPath = substr($reqPath, strlen($scriptDir));
    }
    return ltrim($reqPath, '/');
}

// Serve a local file from a base directory, with security checks
// $isWhite: if true, HTML subpages are sanitized (remove trackers, add noindex/nofollow)
function dl_serve_local(string $baseDir, string $folderName, string $reqPath, array $mimeTypes, ?string $contentBaseFolder = null, bool $isWhite = false): bool
{
    $landingBase = $baseDir . '/' . $folderName;
    $filePath = realpath($landingBase . '/' . $reqPath);
    $realBase = realpath($landingBase);

    if ($filePath === false || $realBase === false || !str_starts_with($filePath, $realBase)) {
        return false;
    }
    if (!is_file($filePath)) {
        return false;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // PHP/HTML subpages
    if (in_array($ext, ['php', 'html', 'htm'])) {
        require_once __DIR__ . '/htmlprocessing.php';
        if ($contentBaseFolder !== null) {
            $relPath = $contentBaseFolder . '/' . $folderName . '/' . $reqPath;
            $html = load_content_with_include($relPath);
        } else {
            ob_start();
            require $filePath;
            $html = ob_get_clean();
        }
        if ($isWhite) {
            $html = sanitize_white_html($html);
        }
        echo $html;
        exit();
    }

    // Static resources
    $mime = $mimeTypes[$ext] ?? mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400');
    readfile($filePath);
    exit();
}

// Determine direct-load mode: single 'dl' key in session (whites) or cookie (blacks)
$dlMode = session_read('dl') ?: get_cookie('dl');

if (!empty($dlMode)) {
    $reqPath = dl_get_req_path();

    // Skip root, admin, js, and existing cloaker files
    $isCloakerFile = file_exists(__DIR__ . '/' . $reqPath) && !is_dir(__DIR__ . '/' . $reqPath);
    if ($reqPath !== '' && !str_starts_with($reqPath, 'admin') && !str_starts_with($reqPath, 'js/') && !$isCloakerFile) {

        // ── Landing/Prelanding direct load (black — from cookies) ──
        if ($dlMode === 'land' || $dlMode === 'preland') {
            $folder = get_cookie($dlMode === 'land' ? 'landing' : 'prelanding');
            if (!empty($folder)) {
                dl_serve_local(
                    __DIR__ . '/' . get_cache_path('landingFolder'),
                    $folder,
                    $reqPath,
                    $dlMimeTypes,
                    get_cache_path('landingFolder')
                );
            }
        }

        // ── White folder direct load (white — from session) ──
        if ($dlMode === 'white') {
            $folder = session_read('white');
            if (!empty($folder)) {
                dl_serve_local(
                    __DIR__ . '/' . get_cache_path('whiteFolder'),
                    $folder,
                    $reqPath,
                    $dlMimeTypes,
                    get_cache_path('whiteFolder'),
                    true
                );
            }
        }

        // ── White CURL direct load (white — proxy + cache, from session) ──
        if ($dlMode === 'white_curl') {
            $baseUrl = rtrim(session_read('white') ?: '', '/');
            if (!empty($baseUrl)) {
                $cacheDir = __DIR__ . '/' . get_cache_path('whiteCurlCache') . '/' . md5($baseUrl);
                $cachePath = $cacheDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $reqPath);

                // Try serving from cache first
                if (is_file($cachePath)) {
                    $ext = strtolower(pathinfo($cachePath, PATHINFO_EXTENSION));
                    $mime = $dlMimeTypes[$ext] ?? mime_content_type($cachePath) ?: 'application/octet-stream';
                    header('Content-Type: ' . $mime);
                    header('Content-Length: ' . filesize($cachePath));
                    header('Cache-Control: public, max-age=86400');
                    readfile($cachePath);
                    exit();
                }

                // Cache miss — fetch via CURL
                $resourceUrl = $baseUrl . '/' . $reqPath;
                $ch = curl_init($resourceUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
                ]);
                $content = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);

                if ($httpCode === 200 && $content !== false) {
                    // Sanitize HTML subpages (remove trackers, add noindex/nofollow)
                    $ext = strtolower(pathinfo($reqPath, PATHINFO_EXTENSION));
                    $isHtml = in_array($ext, ['html', 'htm', 'php']) ||
                              (stripos($contentType ?? '', 'text/html') !== false);
                    if ($isHtml) {
                        require_once __DIR__ . '/htmlprocessing.php';
                        $content = sanitize_white_html($content);
                    }

                    // Save to cache (processed HTML or raw resource)
                    $cacheFileDir = dirname($cachePath);
                    if (!is_dir($cacheFileDir)) {
                        @mkdir($cacheFileDir, 0755, true);
                    }
                    @file_put_contents($cachePath, $content);

                    // Serve
                    if ($contentType) {
                        header('Content-Type: ' . $contentType);
                    } else {
                        $mime = $dlMimeTypes[$ext] ?? 'application/octet-stream';
                        header('Content-Type: ' . $mime);
                    }
                    header('Content-Length: ' . strlen($content));
                    header('Cache-Control: public, max-age=86400');
                    echo $content;
                    exit();
                }
                // If CURL failed, fall through to normal TDS
            }
        }
    }
}
