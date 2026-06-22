import { Pool } from 'pg';

let databaseUrl = process.env.DATABASE_URL;

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
  if (!sql) {
    throw new Error('DATABASE_URL nao esta definida nas variaveis de ambiente.');
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

// Wrapper for queries that automatically ensures table structure exists
export async function dbQuery<T = Record<string, unknown>>(queryText: string, params: unknown[] = []): Promise<T[]> {
  await ensureDb();
  if (!sql) throw new Error('Cliente SQL indisponivel.');
  
  try {
    const result = await sql(queryText, params);
    return result as T[];
  } catch (error) {
    throw new Error(formatDatabaseError(error));
  }
}
