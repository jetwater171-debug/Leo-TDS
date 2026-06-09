import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

export async function GET(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const result = await dbQuery('SELECT settings FROM common LIMIT 1');
    if (result.length === 0) {
      return NextResponse.json({ error: 'Configurações globais não encontradas' }, { status: 404 });
    }
    const settings = typeof result[0].settings === 'string' ? JSON.parse(result[0].settings) : result[0].settings;
    return NextResponse.json(settings);
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function PUT(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const body = await req.json();
    if (!body) {
      return NextResponse.json({ error: 'Corpo da requisição vazio' }, { status: 400 });
    }

    await dbQuery('UPDATE common SET settings = $1', [JSON.stringify(body)]);
    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
