import { NextRequest, NextResponse } from 'next/server';
import { SignJWT, jwtVerify } from 'jose';

const JWT_SECRET = new TextEncoder().encode(process.env.JWT_SECRET || 'yellowtds-secret-12345!');
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || '12345qweasd';

function hasProductionSecrets(): boolean {
  if (process.env.NODE_ENV !== 'production') return true;
  return Boolean(process.env.JWT_SECRET && process.env.ADMIN_PASSWORD);
}

export async function POST(req: NextRequest) {
  try {
    if (!hasProductionSecrets()) {
      return NextResponse.json(
        { success: false, msg: 'Configure ADMIN_PASSWORD e JWT_SECRET no Vercel antes de usar em producao.' },
        { status: 500 }
      );
    }

    const body = await req.json();
    const password = body.password;

    if (password !== ADMIN_PASSWORD) {
      return NextResponse.json({ success: false, msg: 'Senha incorreta!' }, { status: 401 });
    }

    // Sign JWT
    const token = await new SignJWT({ admin: true })
      .setProtectedHeader({ alg: 'HS256' })
      .setExpirationTime('12h') // 12 hours session
      .sign(JWT_SECRET);

    const response = NextResponse.json({ success: true });

    // Set secure cookie
    response.cookies.set('auth_token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'strict',
      maxAge: 3600 * 12,
      path: '/'
    });

    return response;
  } catch (error) {
    return NextResponse.json({ success: false, msg: 'Erro interno no servidor' }, { status: 500 });
  }
}

export async function GET(req: NextRequest) {
  const token = req.cookies.get('auth_token')?.value;
  if (!token) {
    return NextResponse.json({ authenticated: false }, { status: 401 });
  }

  try {
    await jwtVerify(token, JWT_SECRET);
    return NextResponse.json({ authenticated: true });
  } catch {
    return NextResponse.json({ authenticated: false }, { status: 401 });
  }
}

export async function DELETE() {
  const response = NextResponse.json({ success: true });
  response.cookies.set('auth_token', '', { maxAge: 0, path: '/' });
  return response;
}
