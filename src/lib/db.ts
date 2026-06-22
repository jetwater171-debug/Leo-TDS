import { Pool } from 'pg';

let databaseUrl = process.env.DATABASE_URL;
const supabaseUrl = process.env.SUPABASE_URL || process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseServiceRoleKey = process.env.SUPABASE_SERVICE_ROLE_KEY;
const useSupabaseRest = Boolean(!databaseUrl && supabaseUrl && supabaseServiceRoleKey);

// Normalize postgres:// to postgresql:// (common in Supabase/Heroku)
if (databaseUrl && databaseUrl.startsWith('postgres://')) {
  databaseUrl = databaseUrl.replace('postgres://', 'postgresql://');
}

type SqlQuery = {
  (queryText: string, params?: unknown[]): Promise<Record<string, unknown>[]>;
  (strings: TemplateStringsArray, ...values: unknown[]): Promise<Record<string, unknown>[]>;
  query: (queryText: string, params?: unknown[]) => Promise<unknown>;
};

const globalForPg = globalThis as typeof globalThis & {
  __yellowtdsPool?: Pool;
};

let poolClient: Pool | null = null;
if (databaseUrl) {
  try {
    poolClient = globalForPg.__yellowtdsPool ?? new Pool({
      connectionString: databaseUrl,
      max: 15,
      idleTimeoutMillis: 30000,
      connectionTimeoutMillis: 5000,
      ssl: databaseUrl.includes('localhost') ? false : { rejectUnauthorized: false }
    });
    globalForPg.__yellowtdsPool = poolClient;
  } catch (e) {
    console.error('Falha ao inicializar o Pool do Postgres (pg):', e);
  }
}

function templateToSql(strings: TemplateStringsArray): string {
  return strings.reduce((query, chunk, index) => {
    return `${query}${chunk}${index < strings.length - 1 ? `$${index + 1}` : ''}`;
  }, '');
}

function normalizeSql(queryText: string): string {
  return queryText.replace(/\s+/g, ' ').trim();
}

function jsonHeaders(extra?: HeadersInit): HeadersInit {
  return {
    apikey: supabaseServiceRoleKey || '',
    Authorization: `Bearer ${supabaseServiceRoleKey || ''}`,
    'Content-Type': 'application/json',
    ...extra,
  };
}

async function supabaseRest<T = Record<string, unknown>>(
  table: string,
  search = '',
  init: RequestInit = {}
): Promise<{ data: T[]; count?: number }> {
  if (!supabaseUrl || !supabaseServiceRoleKey) {
    throw new Error('SUPABASE_URL e SUPABASE_SERVICE_ROLE_KEY nao estao configuradas.');
  }

  const endpoint = `${supabaseUrl.replace(/\/$/, '')}/rest/v1/${table}${search}`;
  const response = await fetch(endpoint, {
    ...init,
    headers: jsonHeaders(init.headers),
    cache: 'no-store',
  });

  const text = await response.text();
  const data = text ? JSON.parse(text) : [];

  if (!response.ok) {
    const message = data?.message || data?.hint || text || response.statusText;
    throw new Error(`Supabase REST ${response.status}: ${message}`);
  }

  const contentRange = response.headers.get('content-range');
  const totalMatch = contentRange?.match(/\/(\d+)$/);

  return {
    data: Array.isArray(data) ? data : [data],
    count: totalMatch ? Number(totalMatch[1]) : undefined,
  };
}

function encodeJson(value: unknown): string {
  return encodeURIComponent(JSON.stringify(value));
}

function asObject(value: unknown): Record<string, unknown> {
  return value && typeof value === 'object' ? value as Record<string, unknown> : {};
}

// Small compatibility wrapper for the previous Neon-style SQL helper.
export const sql = poolClient
  ? Object.assign(
      async (strings: string | TemplateStringsArray, ...values: unknown[]) => {
        if (typeof strings === 'string') {
          const params = Array.isArray(values[0]) ? values[0] as unknown[] : values;
          const res = await poolClient!.query(strings, params);
          return res.rows;
        }
        const queryText = templateToSql(strings);
        const res = await poolClient!.query(queryText, values);
        return res.rows;
      },
      {
        query: async (queryText: string, params: unknown[] = []) => {
          return poolClient!.query(queryText, params);
        }
      }
    ) as SqlQuery
  : useSupabaseRest
    ? Object.assign(
        async (strings: string | TemplateStringsArray, ...values: unknown[]) => {
          const queryText = typeof strings === 'string' ? strings : templateToSql(strings);
          return dbQuery(queryText, Array.isArray(values[0]) ? values[0] as unknown[] : values);
        },
        {
          query: async (queryText: string, params: unknown[] = []) => {
            return { rows: await dbQuery(queryText, params) };
          }
        }
      ) as SqlQuery
    : null;

// Helper to check if tables exist and run migrations
let isInitialized = false;

function toErrorMessage(error: unknown): string {
  if (error instanceof Error) return error.message;
  return String(error);
}

export function formatDatabaseError(error: unknown): string {
  const message = toErrorMessage(error);

  if (
    message.includes('ENOTFOUND') ||
    message.includes('ENETUNREACH') ||
    message.includes('getaddrinfo') ||
    message.includes('Network is unreachable')
  ) {
    return [
      'Falha ao conectar no Supabase/Postgres.',
      'Na Vercel, use a connection string do Supabase Pooler em DATABASE_URL.',
      'Evite o host direto db.<project-ref>.supabase.co:5432, porque ele pode resolver apenas IPv6 e quebrar em serverless.',
    ].join(' ');
  }

  if (message.includes('password authentication failed')) {
    return 'Falha de autenticacao no Supabase/Postgres. Confira usuario, senha e se caracteres especiais da senha estao URL-encoded.';
  }

  return message;
}

const DDL_SCHEMA = `
  CREATE TABLE IF NOT EXISTS campaigns (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    settings JSONB NOT NULL
  );

  CREATE TABLE IF NOT EXISTS clicks (
    id SERIAL PRIMARY KEY,
    campaign_id INTEGER REFERENCES campaigns (id) ON DELETE CASCADE,
    time INTEGER NOT NULL,
    ip VARCHAR(45) NOT NULL,
    country VARCHAR(10),
    lang VARCHAR(10),
    os VARCHAR(50),
    osver VARCHAR(50),
    device VARCHAR(50),
    brand VARCHAR(50),
    model VARCHAR(50),
    isp VARCHAR(255),
    client VARCHAR(100),
    clientver VARCHAR(50),
    ua TEXT,
    userid VARCHAR(255) NOT NULL,
    clickid VARCHAR(255) NOT NULL UNIQUE,
    flow VARCHAR(255),
    path JSONB DEFAULT '[]'::jsonb,
    step INTEGER DEFAULT 0,
    events JSONB DEFAULT '{}'::jsonb,
    params JSONB DEFAULT '{}'::jsonb,
    leaddata TEXT,
    status VARCHAR(50),
    cost NUMERIC DEFAULT 0,
    payout NUMERIC DEFAULT 0
  );

  CREATE TABLE IF NOT EXISTS click_event_log (
    id SERIAL PRIMARY KEY,
    clickid VARCHAR(255) NOT NULL REFERENCES clicks (clickid) ON DELETE CASCADE,
    time INTEGER NOT NULL,
    step_index INTEGER NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_value NUMERIC NOT NULL
  );

  CREATE TABLE IF NOT EXISTS click_steps (
    id SERIAL PRIMARY KEY,
    clickid VARCHAR(255) NOT NULL REFERENCES clicks (clickid) ON DELETE CASCADE,
    step INTEGER NOT NULL,
    variant VARCHAR(255) NOT NULL,
    time INTEGER NOT NULL,
    UNIQUE (clickid, step)
  );

  CREATE TABLE IF NOT EXISTS blocked (
    id SERIAL PRIMARY KEY,
    campaign_id INTEGER REFERENCES campaigns (id) ON DELETE CASCADE,
    time INTEGER NOT NULL,
    ip VARCHAR(45) NOT NULL,
    country VARCHAR(10),
    lang VARCHAR(10),
    os VARCHAR(50),
    osver VARCHAR(50),
    device VARCHAR(50),
    brand VARCHAR(50),
    model VARCHAR(50),
    isp VARCHAR(255),
    client VARCHAR(100),
    clientver VARCHAR(50),
    ua TEXT,
    params JSONB DEFAULT '{}'::jsonb,
    reason VARCHAR(255)
  );

  CREATE TABLE IF NOT EXISTS trafficback (
    id SERIAL PRIMARY KEY,
    time INTEGER NOT NULL,
    ip VARCHAR(45) NOT NULL,
    country VARCHAR(10),
    lang VARCHAR(10),
    os VARCHAR(50),
    osver VARCHAR(50),
    device VARCHAR(50),
    brand VARCHAR(50),
    model VARCHAR(50),
    isp VARCHAR(255),
    client VARCHAR(100),
    clientver VARCHAR(50),
    ua TEXT,
    params JSONB DEFAULT '{}'::jsonb
  );

  CREATE TABLE IF NOT EXISTS common (
    settings JSONB NOT NULL
  );

  CREATE TABLE IF NOT EXISTS ip_blacklist (
    id SERIAL PRIMARY KEY,
    network CIDR NOT NULL UNIQUE
  );

  CREATE INDEX IF NOT EXISTS idx_ip_blacklist_network ON ip_blacklist USING spgist (network);
  CREATE INDEX IF NOT EXISTS idx_camp_time ON clicks (campaign_id, time);
  CREATE INDEX IF NOT EXISTS idx_camp_time_status ON clicks (campaign_id, time, status);
  CREATE INDEX IF NOT EXISTS idx_userid ON clicks (userid);
  CREATE INDEX IF NOT EXISTS idx_camp_flow ON clicks (campaign_id, flow);
  CREATE INDEX IF NOT EXISTS idx_bcamp_time ON blocked (campaign_id, time);
  CREATE INDEX IF NOT EXISTS idx_tbtime ON trafficback (time);
`;

const DEFAULT_COMMON_SETTINGS = {
  statistics: {
    timezone: "America/Sao_Paulo",
    table: [
      { field: "clicks", width: 81 },
      { field: "uniques", width: 90 },
      { field: "cra", width: -1 },
      { field: "crs", width: -1 },
      { field: "epc", width: -1 },
      { field: "appt", width: -1 },
      { field: "app", width: -1 },
      { field: "uniques_ratio", width: 71 },
      { field: "conversion", width: -1 },
      { field: "purchase", width: -1 },
      { field: "hold", width: -1 },
      { field: "reject", width: -1 },
      { field: "trash", width: -1 },
      { field: "cpc", width: 76 },
      { field: "revenue", width: -1 },
      { field: "costs", width: -1 },
      { field: "profit", width: -1 },
      { field: "roi", width: -1 }
    ],
    trafficBack: [
      { field: "time", width: -1 },
      { field: "ip", width: -1 },
      { field: "country", width: -1 },
      { field: "lang", width: -1 },
      { field: "os", width: -1 },
      { field: "osver", width: -1 },
      { field: "brand", width: -1 },
      { field: "model", width: -1 },
      { field: "isp", width: -1 },
      { field: "client", width: -1 },
      { field: "clientver", width: -1 },
      { field: "ua", width: -1 },
      { field: "params", width: -1 }
    ]
  },
  trafficBackUrl: "https://google.com"
};

export async function ensureDb(): Promise<void> {
  if (useSupabaseRest) {
    return;
  }

  if (!sql) {
    throw new Error('Configure DATABASE_URL ou SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY nas variaveis de ambiente.');
  }
  if (isInitialized) return;

  try {
    const statements = DDL_SCHEMA.split(';').map(s => s.trim()).filter(Boolean);
    for (const statement of statements) {
      await sql(statement);
    }

    const commonRows = await sql('SELECT 1 FROM common LIMIT 1');
    if (commonRows.length === 0) {
      await sql(
        `INSERT INTO common (settings) VALUES ($1)`,
        [JSON.stringify(DEFAULT_COMMON_SETTINGS)]
      );
    }

    isInitialized = true;
  } catch (error) {
    console.error('Falha ao inicializar o banco de dados:', error);
    throw new Error(formatDatabaseError(error));
  }
}

async function supabaseDbQuery<T = Record<string, unknown>>(queryText: string, params: unknown[] = []): Promise<T[]> {
  const query = normalizeSql(queryText);
  const lower = query.toLowerCase();

  if (lower === 'select id, settings from campaigns') {
    return (await supabaseRest<T>('campaigns', '?select=id,settings')).data;
  }

  if (lower.startsWith('select * from campaigns where id =')) {
    return (await supabaseRest<T>('campaigns', `?select=*&id=eq.${Number(params[0])}`)).data;
  }

  if (lower.startsWith('select settings from campaigns where id =')) {
    return (await supabaseRest<T>('campaigns', `?select=settings&id=eq.${Number(params[0])}`)).data;
  }

  if (lower.startsWith('select id, name, settings from campaigns')) {
    return (await supabaseRest<T>('campaigns', '?select=id,name,settings&order=name.asc')).data;
  }

  if (lower.includes('from campaigns c left join clicks cl')) {
    const start = Number(params[0]);
    const end = Number(params[1]);
    const campaigns = (await supabaseRest<Record<string, unknown>>('campaigns', '?select=id,name,settings&order=name.asc')).data;
    const clicks = (await supabaseRest<Record<string, unknown>>(
      'clicks',
      `?select=campaign_id,userid,status,payout,cost&time=gte.${start}&time=lte.${end}`
    )).data;

    return campaigns.map((campaign) => {
      const campaignClicks = clicks.filter((click) => Number(click.campaign_id) === Number(campaign.id));
      const uniques = new Set(campaignClicks.map((click) => String(click.userid || ''))).size;
      return {
        ...campaign,
        clicks: campaignClicks.length,
        uniques,
        conversions: campaignClicks.filter((click) => click.status !== null && click.status !== undefined).length,
        revenue: campaignClicks.reduce((sum, click) => sum + Number(click.payout || 0), 0),
        costs: campaignClicks.reduce((sum, click) => sum + Number(click.cost || 0), 0),
      };
    }) as T[];
  }

  if (lower.startsWith('insert into campaigns')) {
    return (await supabaseRest<T>('campaigns', '?select=id', {
      method: 'POST',
      headers: { Prefer: 'return=representation' },
      body: JSON.stringify({ name: params[0], settings: JSON.parse(String(params[1])) }),
    })).data;
  }

  if (lower.startsWith('update campaigns set name')) {
    await supabaseRest('campaigns', `?id=eq.${Number(params[1])}`, {
      method: 'PATCH',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({ name: params[0] }),
    });
    return [];
  }

  if (lower.startsWith('update campaigns set settings')) {
    await supabaseRest('campaigns', `?id=eq.${Number(params[1])}`, {
      method: 'PATCH',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({ settings: JSON.parse(String(params[0])) }),
    });
    return [];
  }

  if (lower.startsWith('delete from campaigns')) {
    await supabaseRest('campaigns', `?id=eq.${Number(params[0])}`, { method: 'DELETE' });
    return [];
  }

  if (lower.startsWith('select settings from common')) {
    return (await supabaseRest<T>('common', '?select=settings&limit=1')).data;
  }

  if (lower.startsWith('update common set settings')) {
    const rows = (await supabaseRest<Record<string, unknown>>('common', '?select=settings&limit=1')).data;
    if (rows.length === 0) {
      await supabaseRest('common', '', {
        method: 'POST',
        headers: { Prefer: 'return=minimal' },
        body: JSON.stringify({ settings: JSON.parse(String(params[0])) }),
      });
    } else {
      await supabaseRest('common', '', {
        method: 'PATCH',
        headers: { Prefer: 'return=minimal' },
        body: JSON.stringify({ settings: JSON.parse(String(params[0])) }),
      });
    }
    return [];
  }

  if (lower.startsWith('insert into blocked')) {
    const [
      campaign_id, time, ip, country, lang, os, osver, device, brand, model,
      client, clientver, ua, rowParams, reason
    ] = params;
    await supabaseRest('blocked', '', {
      method: 'POST',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({
        campaign_id, time, ip, country, lang, os, osver, device, brand, model,
        client, clientver, ua, params: JSON.parse(String(rowParams || '{}')), reason
      }),
    });
    return [];
  }

  if (lower.startsWith('insert into clicks')) {
    const [
      campaign_id, time, ip, country, lang, os, osver, device, brand, model,
      client, clientver, ua, userid, clickid, flow, path, step, status, cost, payout
    ] = params;
    await supabaseRest('clicks', '', {
      method: 'POST',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({
        campaign_id, time, ip, country, lang, os, osver, device, brand, model,
        client, clientver, ua, userid, clickid, flow,
        path: JSON.parse(String(path || '[]')), step, status, cost, payout
      }),
    });
    return [];
  }

  if (lower.startsWith('select * from clicks where clickid')) {
    return (await supabaseRest<T>('clicks', `?select=*&clickid=eq.${encodeURIComponent(String(params[0]))}&order=time.desc&limit=1`)).data;
  }

  if (lower.startsWith('select id, step, events from clicks where clickid')) {
    return (await supabaseRest<T>('clicks', `?select=id,step,events&clickid=eq.${encodeURIComponent(String(params[0]))}&order=time.desc&limit=1`)).data;
  }

  if (lower.startsWith('insert into click_event_log')) {
    const [clickid, time, step_index, event_name, event_value] = params;
    await supabaseRest('click_event_log', '', {
      method: 'POST',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({ clickid, time, step_index, event_name, event_value }),
    });
    return [];
  }

  if (lower.startsWith('update clicks set events')) {
    await supabaseRest('clicks', `?id=eq.${Number(params[1])}`, {
      method: 'PATCH',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({ events: JSON.parse(String(params[0] || '{}')) }),
    });
    return [];
  }

  if (lower.startsWith('update clicks set status')) {
    await supabaseRest('clicks', `?id=eq.${Number(params[2])}`, {
      method: 'PATCH',
      headers: { Prefer: 'return=minimal' },
      body: JSON.stringify({ status: params[0], payout: params[1] }),
    });
    return [];
  }

  if (lower.startsWith('select 1 from ip_blacklist')) {
    const rows = (await supabaseRest<Record<string, unknown>>(
      'ip_blacklist',
      `?select=id&network=cs.${encodeURIComponent(String(params[0]))}&limit=1`
    )).data;
    return rows.map(() => ({ '?column?': 1 })) as T[];
  }

  if (lower.startsWith('select count(*) as total from')) {
    const table = lower.includes(' from blocked ') ? 'blocked' : lower.includes(' from trafficback ') ? 'trafficback' : 'clicks';
    const search = buildLogSearch(query, params, table, true);
    const result = await supabaseRest<T>(table, search, {
      headers: { Prefer: 'count=exact' },
    });
    return [{ total: result.count || 0 } as T];
  }

  if (lower.startsWith('select * from')) {
    const table = lower.includes(' from blocked ') ? 'blocked' : lower.includes(' from trafficback ') ? 'trafficback' : 'clicks';
    return (await supabaseRest<T>(table, buildLogSearch(query, params, table, false))).data;
  }

  throw new Error(`Query nao suportada pelo modo SUPABASE_SERVICE_ROLE_KEY: ${query}`);
}

function buildLogSearch(query: string, params: unknown[], table: string, countOnly: boolean): string {
  const lower = query.toLowerCase();
  const search = new URLSearchParams();
  search.set('select', countOnly ? 'id' : '*');
  search.set('time', `gte.${Number(params[0])}`);
  search.append('time', `lte.${Number(params[1])}`);

  let paramIndex = 2;
  if (table !== 'trafficback' && lower.includes('campaign_id =')) {
    search.set('campaign_id', `eq.${Number(params[paramIndex])}`);
    paramIndex += 1;
  }

  if (lower.includes('status is not null')) {
    search.set('status', 'not.is.null');
  }

  if (lower.includes('userid like') || lower.includes('clickid like')) {
    const term = String(params[paramIndex] || '').replace(/%/g, '*');
    search.set('or', `(userid.like.${term},clickid.like.${term})`);
  } else if (lower.includes('ip like')) {
    const term = String(params[paramIndex] || '').replace(/%/g, '*');
    search.set('ip', `like.${term}`);
  }

  if (!countOnly) {
    const limit = Number(params[params.length - 2] || 50);
    const offset = Number(params[params.length - 1] || 0);
    const orderMatch = query.match(/ORDER BY\s+([a-z_]+)\s+(ASC|DESC)/i);
    const orderField = orderMatch?.[1] || 'time';
    const orderDir = (orderMatch?.[2] || 'DESC').toLowerCase();
    search.set('order', `${orderField}.${orderDir}`);
    search.set('limit', String(limit));
    search.set('offset', String(offset));
  }

  return `?${search.toString()}`;
}

// Wrapper for queries that automatically ensures table structure exists
export async function dbQuery<T = Record<string, unknown>>(queryText: string, params: unknown[] = []): Promise<T[]> {
  if (useSupabaseRest) {
    try {
      return await supabaseDbQuery<T>(queryText, params);
    } catch (error) {
      throw new Error(formatDatabaseError(error));
    }
  }

  await ensureDb();
  if (!sql) throw new Error('Cliente SQL indisponivel.');
  
  try {
    const result = await sql(queryText, params);
    return result as T[];
  } catch (error) {
    throw new Error(formatDatabaseError(error));
  }
}
