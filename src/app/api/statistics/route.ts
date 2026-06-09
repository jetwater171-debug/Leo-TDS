import { NextRequest, NextResponse } from 'next/server';
import { dbQuery } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// Helper to group flat rows into a tree structure recursively
function buildTree(
  rows: any[],
  groupByFields: string[],
  selectedFields: string[],
  level = 0
): any[] {
  if (groupByFields.length === 0 || level >= groupByFields.length) {
    if (level === 0 && rows.length > 0) {
      return [calculateTotals(rows, selectedFields)];
    }
    return rows;
  }

  const groupField = groupByFields[level];
  const groupedData: Record<string, any[]> = {};

  for (const row of rows) {
    const groupVal = String(row[groupField] ?? 'unknown');
    if (!groupedData[groupVal]) {
      groupedData[groupVal] = [];
    }
    groupedData[groupVal].push(row);
  }

  const tree: any[] = [];
  for (const [groupValue, groupRows] of Object.entries(groupedData)) {
    if (level >= groupByFields.length - 1) {
      const totals = calculateTotals(groupRows, selectedFields);
      totals.group = groupValue;
      tree.push(totals);
    } else {
      const children = buildTree(groupRows, groupByFields, selectedFields, level + 1);
      const totals = calculateTotals(groupRows, selectedFields);
      
      // Remove grouping keys to avoid duplication
      for (const field of groupByFields) {
        delete (totals as any)[field];
      }

      tree.push({
        ...totals,
        _children: children,
        group: groupValue
      });
    }
  }

  return tree;
}

function calculateTotals(rows: any[], selectedFields: string[]): any {
  const totals: Record<string, number> = {};
  for (const field of selectedFields) {
    totals[field] = 0;
  }

  // Base metrics accumulation
  for (const row of rows) {
    for (const field of ['clicks', 'uniques', 'conversion', 'purchase', 'revenue', 'costs', 'trash']) {
      if (selectedFields.includes(field) || ['clicks', 'uniques', 'conversion', 'purchase', 'revenue', 'costs', 'trash'].includes(field)) {
        const val = Number(row[field] || 0);
        totals[field] = (totals[field] || 0) + val;
      }
    }
  }

  // Calculate derived percentages and averages
  const clicks = totals.clicks || 0;
  const uniques = totals.uniques || 0;
  const conversion = totals.conversion || 0;
  const purchase = totals.purchase || 0;
  const revenue = totals.revenue || 0;
  const costs = totals.costs || 0;
  const trash = totals.trash || 0;

  if (selectedFields.includes('uniques_ratio')) {
    totals.uniques_ratio = clicks === 0 ? 0 : Number((uniques * 100 / clicks).toFixed(2));
  }
  if (selectedFields.includes('cra')) {
    totals.cra = clicks === 0 ? 0 : Number((conversion * 100 / clicks).toFixed(2));
  }
  if (selectedFields.includes('crs')) {
    totals.crs = clicks === 0 ? 0 : Number((purchase * 100 / clicks).toFixed(2));
  }
  if (selectedFields.includes('appt')) {
    const denom = conversion - trash;
    totals.appt = denom === 0 ? 0 : Number((purchase * 100 / denom).toFixed(2));
  }
  if (selectedFields.includes('app')) {
    totals.app = conversion === 0 ? 0 : Number((purchase * 100 / conversion).toFixed(2));
  }
  if (selectedFields.includes('roi')) {
    totals.roi = costs === 0 ? 0 : Number(((revenue - costs) * 100 / costs).toFixed(2));
  }
  if (selectedFields.includes('epc')) {
    totals.epc = clicks === 0 ? 0 : Number((revenue / clicks).toFixed(4));
  }
  if (selectedFields.includes('cpc')) {
    totals.cpc = clicks === 0 ? 0 : Number((costs / clicks).toFixed(4));
  }
  if (selectedFields.includes('profit')) {
    totals.profit = Number((revenue - costs).toFixed(4));
  }

  // Cleanup fields not requested
  for (const k of Object.keys(totals)) {
    if (!selectedFields.includes(k) && k !== 'clicks' && k !== 'uniques' && k !== 'conversion' && k !== 'purchase' && k !== 'revenue' && k !== 'costs') {
      delete totals[k];
    }
  }

  return totals;
}

export async function POST(req: NextRequest) {
  if (!(await checkAuth(req))) {
    return NextResponse.json({ error: 'Não autorizado' }, { status: 401 });
  }

  try {
    const { 
      selectedFields, 
      groupByFields, 
      campId, 
      startDate, 
      endDate, 
      timezone 
    } = await req.json();

    if (!selectedFields || !groupByFields || !campId || !startDate || !endDate) {
      return NextResponse.json({ error: 'Parâmetros incompletos' }, { status: 400 });
    }

    const tz = timezone || 'America/Sao_Paulo';

    // Build SQL Select projections
    const selectParts: string[] = [];
    const groupParts: string[] = [];

    for (const field of groupByFields) {
      if (field === 'date') {
        selectParts.push(`TO_CHAR(TO_TIMESTAMP(time) AT TIME ZONE 'UTC' AT TIME ZONE '${tz}', 'YYYY-MM-DD') AS date`);
        groupParts.push(`date`);
      } else {
        // Sanitize field name
        const allowedGroupFields = ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver', 'flow', 'step', 'path'];
        if (allowedGroupFields.includes(field)) {
          selectParts.push(field);
          groupParts.push(field);
        }
      }
    }

    // Base aggregations
    selectParts.push('COUNT(id) AS clicks');
    selectParts.push('COUNT(DISTINCT userid) AS uniques');
    selectParts.push("COUNT(CASE WHEN status IS NOT NULL THEN 1 END) AS conversion");
    selectParts.push("COUNT(CASE WHEN status = 'Purchase' THEN 1 END) AS purchase");
    selectParts.push("COUNT(CASE WHEN status = 'Trash' THEN 1 END) AS trash");
    selectParts.push('COALESCE(SUM(payout), 0) AS revenue');
    selectParts.push('COALESCE(SUM(cost), 0) AS costs');

    const selectClause = selectParts.join(', ');
    const groupByClause = groupParts.length > 0 ? 'GROUP BY ' + groupParts.join(', ') : '';

    const query = `
      SELECT ${selectClause} 
      FROM clicks 
      WHERE campaign_id = $1 AND time BETWEEN $2 AND $3
      ${groupByClause}
      ORDER BY ${groupParts.length > 0 ? groupParts[0] : 'clicks'} DESC
    `;

    const flatRows = await dbQuery(query, [Number(campId), Number(startDate), Number(endDate)]);

    // Build hierarchy tree
    const tree = buildTree(flatRows, groupByFields, selectedFields);

    return NextResponse.json(tree);
  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
