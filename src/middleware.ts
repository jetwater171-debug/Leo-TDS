import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { userAgent } from 'next/server';
import { sql } from './lib/db';
import { matchFilters, ClickParams } from './lib/filters';
import { selectDistributed } from './lib/abtest';
import { jwtVerify } from 'jose';

// Cache campaigns list for 10 seconds to minimize DB requests
let cachedCampaigns: any[] | null = null;
let lastFetchTime = 0;
const CACHE_TTL = 10000;

function matchDomain(domains: string[], host: string): boolean {
  return domains.some(d => {
    if (d === host) return true;
    if (d.includes('*')) {
      const pattern = new RegExp('^' + d.replace(/\./g, '\\.').replace(/\*/g, '.*') + '$');
      return pattern.test(host);
    }
    return false;
  });
}

// Generate random click ID
function generateUuid(): string {
  return Array.from({ length: 16 }, () =>
    Math.floor(Math.random() * 256).toString(16).padStart(2, '0')
  ).join('');
}

export async function middleware(req: NextRequest) {
  const url = req.nextUrl;
  const path = url.pathname;

  // 0. Autenticação da área administrativa no Edge
  if (path.startsWith('/admin')) {
    if (path === '/admin/login') {
      return NextResponse.next();
    }
    const token = req.cookies.get('auth_token')?.value;
    if (!token) {
      return NextResponse.redirect(new URL('/admin/login', req.url));
    }
    try {
      const JWT_SECRET = new TextEncoder().encode(process.env.JWT_SECRET || 'yellowtds-secret-12345!');
      await jwtVerify(token, JWT_SECRET);
      return NextResponse.next();
    } catch {
      const response = NextResponse.redirect(new URL('/admin/login', req.url));
      response.cookies.set('auth_token', '', { maxAge: 0, path: '/' });
      return response;
    }
  }

  // Bypass static resources and api calls
  if (
    path.startsWith('/api') ||
    path.startsWith('/_next') ||
    path.includes('/favicon.ico') ||
    path.includes('/robots.txt') ||
    path.includes('/sitemap') ||
    path.match(/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|mp4|webm)$/)
  ) {
    return NextResponse.next();
  }

  // Ensure DB client is available
  if (!sql) {
    return NextResponse.next();
  }

  // 1. Fetch campaigns (cached for low latency)
  const now = Date.now();
  if (!cachedCampaigns || now - lastFetchTime > CACHE_TTL) {
    try {
      cachedCampaigns = await sql(`SELECT id, settings FROM campaigns`);
      lastFetchTime = now;
    } catch (e) {
      console.error('Erro ao buscar campanhas no Middleware:', e);
      cachedCampaigns = cachedCampaigns || [];
    }
  }

  const host = req.headers.get('host') || '';
  let matchedCampaign: any = null;

  for (const camp of (cachedCampaigns || [])) {
    const settings = typeof camp.settings === 'string' ? JSON.parse(camp.settings) : camp.settings;
    if (settings && Array.isArray(settings.domains) && matchDomain(settings.domains, host)) {
      matchedCampaign = {
        id: camp.id,
        settings
      };
      break;
    }
  }

  // If no campaign matches this domain, continue to next route (or default Page)
  if (!matchedCampaign) {
    return NextResponse.next();
  }

  const campaignId = matchedCampaign.id;
  const settings = matchedCampaign.settings;

  // 2. Parse visitor headers & location (Vercel Edge features)
  const ua = userAgent(req);
  const ip = req.headers.get('x-forwarded-for')?.split(',')[0] || req.headers.get('x-real-ip') || '127.0.0.1';
  const country = req.headers.get('x-vercel-ip-country') || 'Unknown';
  
  // Parse language
  const acceptLang = req.headers.get('accept-language') || '';
  const langMatch = acceptLang.match(/^([a-z]{2})/i);
  const lang = langMatch ? langMatch[1].toLowerCase() : 'en';

  const qs: Record<string, string> = {};
  url.searchParams.forEach((v, k) => {
    qs[k] = v;
  });

  const params: ClickParams = {
    ip,
    ua: req.headers.get('user-agent') || 'Unknown',
    referer: req.headers.get('referer') || '',
    lang,
    country,
    os: ua.os.name || 'Unknown',
    osver: ua.os.version || '0',
    device: ua.device.type || 'desktop',
    brand: ua.device.vendor || 'Unknown',
    model: ua.device.model || 'Unknown',
    client: ua.browser.name || 'Unknown',
    clientver: ua.browser.version || '0',
    url: req.nextUrl.pathname + req.nextUrl.search,
    domain: host,
    host,
    qs
  };

  // 3. Evaluate White Filters (Safe traffic)
  const whiteFilterGroup = settings.white?.filters || {};
  const whiteCheck = await matchFilters(whiteFilterGroup, params);

  // If visitor matches white filters -> Serve White Page (Safe Page)
  if (whiteCheck.matches) {
    const blockReason = whiteCheck.reasons.join(', ') || 'white_filter';
    
    // Log white click asynchronously to avoid blocking the request
    const logPromise = sql(
      `INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, device, brand, model, client, clientver, ua, params, reason) 
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)`,
      [
        campaignId,
        Math.floor(now / 1000),
        params.ip,
        params.country,
        params.lang,
        params.os,
        params.osver,
        params.device,
        params.brand,
        params.model,
        params.client,
        params.clientver,
        params.ua,
        JSON.stringify(params.qs),
        blockReason
      ]
    ).catch((e: any) => console.error('Erro ao registrar clique bloqueado:', e));

    // Execute the White action
    const whiteAction = settings.white.action || 'error';
    if (whiteAction === 'redirect') {
      const urls = settings.white.redirect?.urls || [];
      const urlToRedirect = urls[Math.floor(Math.random() * urls.length)] || 'https://google.com';
      const redirectType = Number(settings.white.redirect?.type || 302);
      return NextResponse.redirect(urlToRedirect, redirectType);
    }

    if (whiteAction === 'error') {
      const code = Number(settings.white.errorcodes?.[0] || 404);
      return new NextResponse('Not Found', { status: code });
    }

    // Default white folders or curl proxying is rewritten to the internal cloner router
    const folder = settings.white.folders?.[0] || 'default';
    const serveUrl = new URL(`/api/serve?type=white&campaignId=${campaignId}&folder=${folder}&reason=${encodeURIComponent(blockReason)}`, req.url);
    return NextResponse.rewrite(serveUrl);
  }

  // 4. Visitor is Black (Real customer) -> Serve landing/offer
  // Check JS check bot detection if enabled
  const jsBot = settings.black?.jsbotdetection;
  const cookieName = `cl_sess_${campaignId}`;
  const sessionCookie = req.cookies.get(cookieName)?.value;

  if (jsBot?.enabled && !sessionCookie) {
    // Redirect to JS challenge page
    const challengeUrl = new URL(`/api/serve?type=jscheck&campaignId=${campaignId}`, req.url);
    return NextResponse.rewrite(challengeUrl);
  }

  // Parse or start session
  let clickSession: { clickid: string; step: number; path: string[]; flow: string } | null = null;
  if (sessionCookie) {
    try {
      clickSession = JSON.parse(sessionCookie);
    } catch {}
  }

  // Find matching flow
  const flows = settings.black?.flows || [];
  let matchedFlow: any = null;
  for (const f of flows) {
    const check = await matchFilters(f.filters, params);
    if (check.matches) {
      matchedFlow = f;
      break;
    }
  }

  if (!matchedFlow) {
    // Redirect to general trafficback if no flows match
    const tbUrl = settings.trafficBackUrl || 'https://google.com';
    return NextResponse.redirect(tbUrl, 302);
  }

  // Select variants and steps
  const distribution = matchedFlow.distribution || 'equal';
  const steps = matchedFlow.steps || [];

  if (steps.length === 0) {
    const tbUrl = settings.trafficBackUrl || 'https://google.com';
    return NextResponse.redirect(tbUrl, 302);
  }

  if (!clickSession) {
    const clickid = generateUuid();
    const userid = generateUuid();
    const path: string[] = [];

    // Pre-calculate path variants for each step
    steps.forEach((step: any) => {
      const items = step.action === 'redirect'
        ? step.redirect?.urls.map((u: any) => u.label || u.url) || []
        : step.folders || [];
      
      if (items.length > 0) {
        const selected = selectDistributed(items, distribution, step.weights || []);
        path.push(selected.item as string);
      }
    });

    clickSession = {
      clickid,
      step: 0,
      path,
      flow: matchedFlow.name
    };

    // Log black click asynchronously
    sql(
      `INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, client, clientver, ua, userid, clickid, flow, path, step, status, cost, payout) 
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21)`,
      [
        campaignId,
        Math.floor(now / 1000),
        params.ip,
        params.country,
        params.lang,
        params.os,
        params.osver,
        params.device,
        params.brand,
        params.model,
        params.client,
        params.clientver,
        params.ua,
        userid,
        clickid,
        matchedFlow.name,
        JSON.stringify(path),
        0,
        null,
        0,
        0
      ]
    ).catch((e: any) => console.error('Erro ao registrar clique aceito:', e));
  }

  const currentStepIndex = clickSession.step;
  const currentStep = steps[currentStepIndex];

  if (!currentStep) {
    // Completed all steps
    const tbUrl = settings.trafficBackUrl || 'https://google.com';
    return NextResponse.redirect(tbUrl, 302);
  }

  const chosenVariant = clickSession.path[currentStepIndex];

  // Prepare response object
  let response: NextResponse;

  if (currentStep.action === 'redirect') {
    const redirectUrl = currentStep.redirect?.urls.find((u: any) => u.label === chosenVariant || u.url === chosenVariant)?.url || chosenVariant;
    const redirectType = Number(currentStep.redirect?.type || 302);
    response = NextResponse.redirect(redirectUrl, redirectType);
  } else {
    // Serve landing folder by rewriting internally
    const serveUrl = new URL(`/api/serve?type=landing&campaignId=${campaignId}&folder=${chosenVariant}&clickid=${clickSession.clickid}`, req.url);
    response = NextResponse.rewrite(serveUrl);
  }

  // Set the session cookie
  response.cookies.set(cookieName, JSON.stringify(clickSession), {
    path: '/',
    maxAge: 3600 * 24 // 1 day session
  });

  return response;
}
