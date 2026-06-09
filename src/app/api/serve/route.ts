import { NextRequest, NextResponse } from 'next/server';
import fs from 'fs';
import path from 'path';

// Map file extensions to MIME types
const MIME_TYPES: Record<string, string> = {
  css: 'text/css',
  js: 'application/javascript',
  json: 'application/json',
  png: 'image/png',
  jpg: 'image/jpeg',
  jpeg: 'image/jpeg',
  gif: 'image/gif',
  svg: 'image/svg+xml',
  webp: 'image/webp',
  ico: 'image/x-icon',
  woff: 'font/woff',
  woff2: 'font/woff2',
  ttf: 'font/ttf',
  eot: 'application/vnd.ms-fontobject',
  otf: 'font/otf',
  mp4: 'video/mp4',
  webm: 'video/webm',
  mp3: 'audio/mpeg',
  ogg: 'audio/ogg',
  pdf: 'application/pdf',
  xml: 'application/xml',
  txt: 'text/plain',
};

// JS Challenge HTML Page
const JS_CHECK_HTML = `
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança do Navegador</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
        }
        .container {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 600;
            color: #38bdf8;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #94a3b8;
            font-size: 15px;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #38bdf8;
        }
        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid #334155;
            border-top: 2px solid #38bdf8;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">Verificação de Segurança</div>
        <div class="subtitle">Estamos verificando a conexão do seu navegador. Por favor, aguarde alguns instantes...</div>
        <div class="loading">
            <div class="spinner"></div>
            <span>Verificando...</span>
        </div>
    </div>
    
    <script>
        // Simple client-side bot detection tests
        async function runChecks() {
            const botDetector = {
                timezone: () => {
                    const tz = -(new Date().getTimezoneOffset() / 60);
                    return tz >= {TZMIN} && tz <= {TZMAX};
                },
                audio: () => {
                    try {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;
                        return !!AudioContext;
                    } catch (e) {
                        return false;
                    }
                }
            };

            const isBot = !botDetector.timezone() || !botDetector.audio();

            // Redirect back with session confirmation
            setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                if (isBot) {
                    params.set('reason', 'jscheck_failed');
                }
                window.location.href = '/api/serve/callback?' + params.toString() + '&campaignId={CAMPAIGN_ID}';
            }, 1000);
        }
        window.addEventListener('load', runChecks);
    </script>
</body>
</html>
`;

export async function GET(req: NextRequest) {
  const url = req.nextUrl;
  const type = url.searchParams.get('type');
  const campaignId = url.searchParams.get('campaignId');
  const folder = url.searchParams.get('folder');
  const file = url.searchParams.get('file') || 'index.html';
  const clickid = url.searchParams.get('clickid') || '';

  // 1. JSbot Check page
  if (type === 'jscheck') {
    // Generate JScheck challenge page
    // Normally timezone is read from the campaign settings, we can default to -12 to 12
    const html = JS_CHECK_HTML
      .replace('{TZMIN}', '-12')
      .replace('{TZMAX}', '12')
      .replace('{CAMPAIGN_ID}', campaignId || '');

    return new NextResponse(html, {
      headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
  }

  // 2. Serve files from folder (landing or white)
  if (type === 'landing' || type === 'white') {
    const subFolder = type === 'landing' ? 'landings' : 'whites';
    
    // Security check: prevent directory traversal
    const safeFile = path.normalize(file).replace(/^(\.\.[\/\\])+/, '');
    
    // Resolve absolute path in workspace
    const filePath = path.join(process.cwd(), 'caching', subFolder, folder || '', safeFile);

    if (!fs.existsSync(filePath)) {
      return new NextResponse('File Not Found', { status: 404 });
    }

    const stat = fs.statSync(filePath);
    if (stat.isDirectory()) {
      // If it's a directory, try loading index.html
      const indexPath = path.join(filePath, 'index.html');
      if (fs.existsSync(indexPath)) {
        return serveHtmlFile(indexPath, clickid);
      }
      return new NextResponse('Directory Index Not Allowed', { status: 403 });
    }

    const ext = path.extname(filePath).toLowerCase().substring(1);
    const mime = MIME_TYPES[ext] || 'application/octet-stream';

    if (ext === 'html' || ext === 'htm') {
      return serveHtmlFile(filePath, clickid);
    }

    // Serve static resource
    const fileBuffer = fs.readFileSync(filePath);
    return new NextResponse(fileBuffer, {
      headers: {
        'Content-Type': mime,
        'Content-Length': fileBuffer.length.toString(),
        'Cache-Control': 'public, max-age=86400',
      }
    });
  }

  return new NextResponse('Bad Request', { status: 400 });
}

// Read HTML file, replace macros, and serve
function serveHtmlFile(filePath: string, clickid: string): NextResponse {
  let html = fs.readFileSync(filePath, 'utf8');

  // Simple macros replacements
  html = html
    .replace(/{CLICKID}/g, clickid)
    .replace(/{USERID}/g, clickid); // fallback

  return new NextResponse(html, {
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0'
    }
  });
}
