import { NextRequest, NextResponse } from 'next/server';
import { sql } from '@/lib/db';
import { selectDistributed } from '@/lib/abtest';
import { matchFilters, ClickParams } from '@/lib/filters';
import { userAgent } from 'next/server';

export async function GET(req: NextRequest) {
  const url = req.nextUrl;
  const campaignId = url.searchParams.get('campaignId');
  const reason = url.searchParams.get('reason');

  if (!sql || !campaignId) {
    return NextResponse.redirect(new URL('/', req.url));
  }

  // Fetch campaign
  const res = await sql(`SELECT settings FROM campaigns WHERE id = $1`, [Number(campaignId)]);
  if (res.length === 0) {
    return NextResponse.redirect(new URL('/', req.url));
  }

  const settings = typeof res[0].settings === 'string' ? JSON.parse(res[0].settings) : res[0].settings;
  const cookieName = `cl_sess_${campaignId}`;
  const now = Date.now();

  // Visitor failed the JS check
  if (reason) {
    const ua = userAgent(req);
    const ip = req.headers.get('x-forwarded-for')?.split(',')[0] || req.headers.get('x-real-ip') || '127.0.0.1';
    const country = req.headers.get('x-vercel-ip-country') || 'Unknown';
    const acceptLang = req.headers.get('accept-language') || '';
    const langMatch = acceptLang.match(/^([a-z]{2})/i);
    const lang = langMatch ? langMatch[1].toLowerCase() : 'en';

    await sql(
      `INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, device, brand, model, client, clientver, ua, params, reason) 
       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)`,
      [
        Number(campaignId),
        Math.floor(now / 1000),
        ip,
        country,
        lang,
        ua.os.name || 'Unknown',
        ua.os.version || '0',
        ua.device.type || 'desktop',
        ua.device.vendor || 'Unknown',
        ua.device.model || 'Unknown',
        ua.browser.name || 'Unknown',
        ua.browser.version || '0',
        req.headers.get('user-agent') || 'Unknown',
        JSON.stringify({}),
        reason
      ]
    );

    const whiteAction = settings.white?.action || 'error';
    if (whiteAction === 'redirect') {
      const urls = settings.white.redirect?.urls || [];
      const urlToRedirect = urls[Math.floor(Math.random() * urls.length)] || 'https://google.com';
      return NextResponse.redirect(urlToRedirect, 302);
    }
    const errorCode = Number(settings.white?.errorcodes?.[0] || 404);
    return new NextResponse('Not Found', { status: errorCode });
  }

  // Visitor passed JS check! Create black session
  const ua = userAgent(req);
  const ip = req.headers.get('x-forwarded-for')?.split(',')[0] || req.headers.get('x-real-ip') || '127.0.0.1';
  const country = req.headers.get('x-vercel-ip-country') || 'Unknown';
  const acceptLang = req.headers.get('accept-language') || '';
  const langMatch = acceptLang.match(/^([a-z]{2})/i);
  const lang = langMatch ? langMatch[1].toLowerCase() : 'en';

  const qs: Record<string, string> = {};
  url.searchParams.forEach((v, k) => {
    if (k !== 'campaignId' && k !== 'reason') {
      qs[k] = v;
    }
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
    domain: req.headers.get('host') || '',
    host: req.headers.get('host') || '',
    qs
  };

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
    return NextResponse.redirect(settings.trafficBackUrl || 'https://google.com', 302);
  }

  const distribution = matchedFlow.distribution || 'equal';
  const steps = matchedFlow.steps || [];

  if (steps.length === 0) {
    return NextResponse.redirect(settings.trafficBackUrl || 'https://google.com', 302);
  }

  const clickid = Array.from({ length: 16 }, () =>
    Math.floor(Math.random() * 256).toString(16).padStart(2, '0')
  ).join('');
  const userid = Array.from({ length: 16 }, () =>
    Math.floor(Math.random() * 256).toString(16).padStart(2, '0')
  ).join('');

  const pathList: string[] = [];
  steps.forEach((step: any) => {
    const items = step.action === 'redirect'
      ? step.redirect?.urls.map((u: any) => u.label || u.url) || []
      : step.folders || [];
    
    if (items.length > 0) {
      const selected = selectDistributed(items, distribution, step.weights || []);
      pathList.push(selected.item as string);
    }
  });

  const clickSession = {
    clickid,
    step: 0,
    path: pathList,
    flow: matchedFlow.name
  };

  // Log black click in database
  await sql(
    `INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, client, clientver, ua, userid, clickid, flow, path, step, status, cost, payout) 
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21)`,
    [
      Number(campaignId),
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
      JSON.stringify(pathList),
      0,
      null,
      0,
      0
    ]
  );

  // Return reload script
  const response = new NextResponse(
    `<html><body><script>window.location.href = "/";</script></body></html>`,
    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
  );

  response.cookies.set(cookieName, JSON.stringify(clickSession), {
    path: '/',
    maxAge: 3600 * 24
  });

  return response;
}
