import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';

// Helper to replace postback URL macros
function replaceMacros(
  url: string,
  click: any,
  status: string,
  payout: number,
  currency: string
): string {
  return url
    .replace(/{clickid}/gi, click.clickid || '')
    .replace(/{hash:clickid}/gi, click.clickid || '')
    .replace(/{userid}/gi, click.userid || '')
    .replace(/{status}/gi, status)
    .replace(/{payout}/gi, String(payout))
    .replace(/{currency}/gi, currency);
}

// Simple currency converter helper
function convertCurrency(amount: number, currency: string): number {
  // Free Frankfurter API currency rates can be queried, or we can use standard static multiplier for common currencies
  // Default converter maps non-USD to USD
  const rates: Record<string, number> = {
    BRL: 0.18, // 1 BRL ≈ 0.18 USD
    EUR: 1.08, // 1 EUR ≈ 1.08 USD
    GBP: 1.27,
    RUB: 0.011,
    USD: 1.0
  };
  
  const upperCurrency = currency.toUpperCase();
  const rate = rates[upperCurrency] || 1.0;
  return Number((amount * rate).toFixed(2));
}

export async function GET(req: NextRequest) {
  return handlePostback(req);
}

export async function POST(req: NextRequest) {
  return handlePostback(req);
}

async function handlePostback(req: NextRequest) {
  const url = req.nextUrl;
  const clickid = url.searchParams.get('clickid') || '';
  const status = url.searchParams.get('status') || '';
  const payoutStr = url.searchParams.get('payout') || '0';
  const currency = url.searchParams.get('currency') || 'USD';

  if (!clickid || !status) {
    return new NextResponse('Erro: Faltando clickid ou status', { status: 400 });
  }

  try {
    // 1. Fetch click data
    const clickResult = await dbQuery('SELECT * FROM clicks WHERE clickid = $1 ORDER BY time DESC LIMIT 1', [clickid]);
    if (clickResult.length === 0) {
      return new NextResponse('Erro: Clickid não encontrado', { status: 404 });
    }
    const click = clickResult[0];

    // 2. Fetch campaign settings
    const campResult = await dbQuery('SELECT settings FROM campaigns WHERE id = $1', [Number(click.campaign_id)]);
    if (campResult.length === 0) {
      return new NextResponse('Erro: Campanha não encontrada', { status: 404 });
    }
    const settings = typeof campResult[0].settings === 'string' ? JSON.parse(campResult[0].settings) : campResult[0].settings;

    // 3. Map status
    const postbackEvents = settings.postback?.events || { lead: 'Lead', purchase: 'Purchase', reject: 'Reject', trash: 'Trash' };
    let innerStatus = '';
    
    const lowerStatus = status.toLowerCase();
    if (lowerStatus === String(postbackEvents.lead).toLowerCase()) innerStatus = 'Lead';
    else if (lowerStatus === String(postbackEvents.purchase).toLowerCase()) innerStatus = 'Purchase';
    else if (lowerStatus === String(postbackEvents.reject).toLowerCase()) innerStatus = 'Reject';
    else if (lowerStatus === String(postbackEvents.trash).toLowerCase()) innerStatus = 'Trash';

    if (!innerStatus) {
      return new NextResponse(`Erro: Status '${status}' desconhecido para esta campanha`, { status: 400 });
    }

    // 4. Convert Currency
    const rawPayout = parseFloat(payoutStr) || 0;
    const usdPayout = convertCurrency(rawPayout, currency);

    // 5. Update Click Status and Payout
    await dbQuery(
      `UPDATE clicks 
       SET status = $1, payout = $2 
       WHERE id = $3`,
      [innerStatus, usdPayout, click.id]
    );

    // 6. Trigger Outbound S2S Postbacks
    const s2sList = settings.postback?.s2s || [];
    for (const s2s of s2sList) {
      if (!s2s.url || !s2s.events.includes(innerStatus)) continue;

      const finalUrl = replaceMacros(s2s.url, click, innerStatus, usdPayout, currency);
      
      // Fire-and-forget outbound webhook
      if (s2s.method === 'POST') {
        const urlParts = finalUrl.split('?');
        const postUrl = urlParts[0];
        const params = urlParts[1] ? Object.fromEntries(new URLSearchParams(urlParts[1])) : {};
        
        fetch(postUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(params)
        }).catch(e => console.error('Outbound S2S POST failed:', e));
      } else {
        // GET
        fetch(finalUrl).catch(e => console.error('Outbound S2S GET failed:', e));
      }
    }

    console.log(`Postback aceito para o clickid ${clickid}: Status=${innerStatus}, Payout=$${usdPayout}`);
    return new NextResponse(`Postback aceito: clickid=${clickid}, status=${innerStatus}, payout=${usdPayout} USD`);
  } catch (error: any) {
    console.error('Erro ao processar postback:', error);
    return new NextResponse('Erro no processamento do postback', { status: 500 });
  }
}
