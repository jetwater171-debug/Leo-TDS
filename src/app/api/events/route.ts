import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';

export async function POST(req: NextRequest) {
  try {
    const { clickid, event: eventName, value: valueRaw } = await req.json();

    if (
      !clickid || 
      !eventName || 
      !/^[a-z0-9_]+$/.test(eventName) || 
      isNaN(Number(valueRaw))
    ) {
      return NextResponse.json({ error: 'Payload de evento inválido' }, { status: 400 });
    }

    const value = parseFloat(valueRaw);
    if (!isFinite(value) || value === 0) {
      return NextResponse.json({ error: 'Valor de evento inválido' }, { status: 400 });
    }

    // 1. Fetch current click row
    const clickResult = await dbQuery(
      'SELECT id, step, events FROM clicks WHERE clickid = $1 ORDER BY time DESC LIMIT 1',
      [clickid]
    );

    if (clickResult.length === 0) {
      return NextResponse.json({ error: 'Clickid não encontrado' }, { status: 404 });
    }

    const clickRow = clickResult[0];
    
    // Parse current events
    let events: Record<string, number> = {};
    if (clickRow.events) {
      events = typeof clickRow.events === 'string' ? JSON.parse(clickRow.events) : clickRow.events;
    }

    // Accumulate value
    events[eventName] = Number(((events[eventName] || 0) + value).toFixed(6));

    // 2. Insert event log and update clicks table (run inside transaction for consistency)
    await dbQuery(
      `INSERT INTO click_event_log (clickid, time, step_index, event_name, event_value) 
       VALUES ($1, $2, $3, $4, $5)`,
      [
        clickid,
        Math.floor(Date.now() / 1000),
        Math.max(0, Number(clickRow.step || 0)),
        eventName,
        value
      ]
    );

    await dbQuery(
      'UPDATE clicks SET events = $1 WHERE id = $2',
      [JSON.stringify(events), clickRow.id]
    );

    return NextResponse.json({ ok: true });
  } catch (error: any) {
    console.error('Erro ao salvar evento:', error);
    return NextResponse.json({ error: 'Erro ao salvar evento' }, { status: 500 });
  }
}
