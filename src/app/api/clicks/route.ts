import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

export async function GET(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  const { searchParams } = new URL(req.url);
  const filter = searchParams.get('filter') || 'allowed'; // 'allowed', 'blocked', 'leads', 'trafficback'
  const campId = searchParams.get('campId');
  const startdate = Number(searchParams.get('startdate') || 0);
  const enddate = Number(searchParams.get('enddate') || Date.now());
  const page = Math.max(1, Number(searchParams.get('page') || 1));
  const size = Math.max(1, Number(searchParams.get('size') || 50));
  const sortField = searchParams.get('sortField') || 'time';
  const sortDir = (searchParams.get('sortDir') || 'desc').toUpperCase() === 'ASC' ? 'ASC' : 'DESC';
  const searchTerm = (searchParams.get('searchTerm') || '').trim();

  // Resolve target table
  let table = 'clicks';
  if (filter === 'blocked') table = 'blocked';
  if (filter === 'trafficback') table = 'trafficback';

  // Build WHERE clause
  const whereParts: string[] = ['time BETWEEN $1 AND $2'];
  const params: any[] = [startdate, enddate];

  if (table !== 'trafficback' && campId) {
    whereParts.push(`campaign_id = $${params.length + 1}`);
    params.push(Number(campId));
  }

  if (filter === 'leads') {
    whereParts.push(`status IS NOT NULL`);
  }

  if (searchTerm) {
    if (table === 'clicks') {
      whereParts.push(`(userid LIKE $${params.length + 1} OR clickid LIKE $${params.length + 2})`);
      params.push(`%${searchTerm}%`);
      params.push(`%${searchTerm}%`);
    } else {
      whereParts.push(`ip LIKE $${params.length + 1}`);
      params.push(`%${searchTerm}%`);
    }
  }

  const whereClause = 'WHERE ' + whereParts.join(' AND ');
  const offset = (page - 1) * size;

  // Sanitize sortField to prevent SQL injection
  const allowedSortFields = [
    'id', 'time', 'ip', 'country', 'lang', 'os', 'osver', 
    'device', 'brand', 'model', 'isp', 'client', 'clientver', 
    'ua', 'userid', 'clickid', 'flow', 'step', 'status', 'cost', 'payout', 'reason'
  ];
  const orderField = allowedSortFields.includes(sortField) ? sortField : 'time';

  try {
    // 1. Get total count
    const countQuery = `SELECT COUNT(*) as total FROM ${table} ${whereClause}`;
    const countResult = await dbQuery(countQuery, params);
    const total = Number(countResult[0]?.total || 0);

    // 2. Get data rows
    const dataParams = [...params, size, offset];
    const dataQuery = `
      SELECT * FROM ${table} 
      ${whereClause} 
      ORDER BY ${orderField} ${sortDir} 
      LIMIT $${dataParams.length - 1} OFFSET $${dataParams.length}
    `;
    const data = await dbQuery(dataQuery, dataParams);

    // Parse path, events, params fields back to objects if they are strings (JSONB parsed naturally by pg client)
    const formattedData = data.map((row: any) => {
      if (typeof row.path === 'string') {
        try { row.path = JSON.parse(row.path); } catch { row.path = []; }
      }
      if (typeof row.events === 'string') {
        try { row.events = JSON.parse(row.events); } catch { row.events = {}; }
      }
      if (typeof row.params === 'string') {
        try { row.params = JSON.parse(row.params); } catch { row.params = {}; }
      }
      return row;
    });

    return NextResponse.json({
      data: formattedData,
      last_page: Math.max(1, Math.ceil(total / size))
    });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
