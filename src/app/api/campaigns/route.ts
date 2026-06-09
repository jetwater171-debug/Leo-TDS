import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// Default campaign settings schema in TS
const DEFAULT_CAMPAIGN_SETTINGS = {
  domains: ["yourdomain.com"],
  apikey: "",
  saveuserflow: false,
  white: {
    action: "error",
    folders: ["white"],
    redirect: { type: "302", urls: ["https://google.com"] },
    curls: ["https://google.com"],
    errorcodes: ["404"],
    domainfilter: { use: false, domains: [] },
    filters: { condition: "AND", rules: [], valid: true }
  },
  black: {
    jsconnect: "redirect",
    jsbotdetection: {
      enabled: false,
      events: ["audiocontext", "timezone"],
      timeout: "4000",
      timezone: { min: "-12", max: "12" }
    },
    flows: [
      {
        name: "Flow 1",
        filters: {},
        distribution: "equal",
        optimize_for: "Lead",
        optimize_mode: "funnels",
        steps: [
          {
            action: "redirect",
            folders: [],
            redirect: { urls: [{ url: "https://google.com", label: "google" }], type: 302 },
            weights: [],
            folderloadtypes: {}
          }
        ]
      }
    ]
  },
  scripts: {
    backfix: { use: false, urls: [] },
    nextredirect: { use: false, rules: [] },
    submitredirect: { use: false, rules: [] },
    events: {
      scroll: { use: false, thresholds: [50] },
      time: { use: false, thresholds: [60] }
    },
    imageslazyload: false
  },
  statistics: {
    timezone: "America/Sao_Paulo",
    blocked: [
      { field: "time", width: -1 },
      { field: "ip", width: -1 },
      { field: "country", width: -1 },
      { field: "lang", width: -1 },
      { field: "os", width: -1 },
      { field: "reason", width: -1 }
    ],
    allowed: [
      { field: "clickid", width: -1 },
      { field: "time", width: -1 },
      { field: "ip", width: -1 },
      { field: "country", width: -1 },
      { field: "lang", width: -1 },
      { field: "os", width: -1 },
      { field: "flow", width: -1 },
      { field: "status", width: -1 }
    ],
    leads: [
      { field: "clickid", width: -1 },
      { field: "time", width: -1 },
      { field: "ip", width: -1 },
      { field: "country", width: -1 },
      { field: "flow", width: -1 },
      { field: "status", width: -1 }
    ],
    tables: [
      {
        name: "Date",
        columns: [
          { field: "date", width: -1 },
          { field: "clicks", width: -1 },
          { field: "uniques", width: -1 },
          { field: "conversion", width: -1 },
          { field: "revenue", width: -1 }
        ],
        groupby: ["date"]
      }
    ]
  },
  postback: {
    events: { lead: "Lead", purchase: "Purchase", reject: "Reject", trash: "Trash" },
    s2s: []
  }
};

function generateApiKey(): string {
  const s4 = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1).toUpperCase();
  return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
}

export async function GET(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  const { searchParams } = new URL(req.url);
  const id = searchParams.get('id');
  const startdate = searchParams.get('startdate');
  const enddate = searchParams.get('enddate');

  try {
    if (id) {
      // Get single campaign settings
      const result = await dbQuery('SELECT * FROM campaigns WHERE id = $1', [Number(id)]);
      if (result.length === 0) {
        return NextResponse.json({ error: 'Campanha não encontrada' }, { status: 404 });
      }
      return NextResponse.json(result[0]);
    }

    // Get list of campaigns with summary stats if date range is provided
    if (startdate && enddate) {
      const start = Number(startdate);
      const end = Number(enddate);

      // Query campaigns and count their clicks/leads/revenue inside date range
      const result = await dbQuery(`
        SELECT 
          c.id, 
          c.name, 
          c.settings,
          COUNT(cl.id) as clicks,
          COUNT(DISTINCT cl.userid) as uniques,
          COUNT(CASE WHEN cl.status IS NOT NULL THEN 1 END) as conversions,
          COALESCE(SUM(cl.payout), 0) as revenue,
          COALESCE(SUM(cl.cost), 0) as costs
        FROM campaigns c
        LEFT JOIN clicks cl ON cl.campaign_id = c.id AND cl.time BETWEEN $1 AND $2
        GROUP BY c.id
        ORDER BY c.name ASC
      `, [start, end]);

      return NextResponse.json(result);
    }

    // Default simple list
    const result = await dbQuery('SELECT id, name, settings FROM campaigns ORDER BY name ASC');
    return NextResponse.json(result);
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function POST(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { name } = await req.json();
    if (!name) {
      return NextResponse.json({ error: 'Nome é obrigatório' }, { status: 400 });
    }

    // Build custom default settings with a fresh API key
    const settings = {
      ...DEFAULT_CAMPAIGN_SETTINGS,
      apikey: generateApiKey()
    };

    const result = await dbQuery(
      'INSERT INTO campaigns (name, settings) VALUES ($1, $2) RETURNING id',
      [name, JSON.stringify(settings)]
    );

    return NextResponse.json({ success: true, id: result[0].id });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function PUT(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { id, name, settings } = await req.json();
    if (!id) {
      return NextResponse.json({ error: 'ID da campanha é obrigatório' }, { status: 400 });
    }

    if (name) {
      await dbQuery('UPDATE campaigns SET name = $1 WHERE id = $2', [name, Number(id)]);
    }

    if (settings) {
      await dbQuery('UPDATE campaigns SET settings = $1 WHERE id = $2', [
        JSON.stringify(settings),
        Number(id)
      ]);
    }

    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function DELETE(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  const { searchParams } = new URL(req.url);
  const id = searchParams.get('id');

  if (!id) {
    return NextResponse.json({ error: 'ID da campanha é obrigatório' }, { status: 400 });
  }

  try {
    await dbQuery('DELETE FROM campaigns WHERE id = $1', [Number(id)]);
    return NextResponse.json({ success: true });
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
