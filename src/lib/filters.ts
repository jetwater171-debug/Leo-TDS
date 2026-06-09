import { dbQuery } from './db';

export interface ClickParams {
  ip: string;
  ua: string;
  referer: string;
  lang: string;
  country: string;
  os: string;
  osver: string;
  device: string;
  brand: string;
  model: string;
  client: string;
  clientver: string;
  url: string;
  domain: string;
  host: string;
  qs: Record<string, string>;
}

export interface FilterRule {
  id: string; // e.g., 'country', 'os', 'vpntor', 'urlparam', 'ipbase'
  field?: string;
  operator: string; // 'in', 'not_in', 'contains', 'not_contains', 'equal', 'not_equal', 'less_or_equal', 'greater_or_equal', 'param_exists', 'param_not_exists'
  value: any; // Can be string, number, array
}

export interface FilterGroup {
  condition: 'AND' | 'OR';
  rules: (FilterRule | FilterGroup)[];
}

// Compare versions (standard php version_compare implementation in TS)
export function compareVersions(v1: string, v2: string, operator: string): boolean {
  const parse = (v: string) => v.split('.').map(x => parseInt(x, 10) || 0);
  const p1 = parse(v1);
  const p2 = parse(v2);
  const len = Math.max(p1.length, p2.length);
  
  let cmp = 0;
  for (let i = 0; i < len; i++) {
    const a = p1[i] || 0;
    const b = p2[i] || 0;
    if (a < b) {
      cmp = -1;
      break;
    }
    if (a > b) {
      cmp = 1;
      break;
    }
  }

  switch (operator) {
    case 'less_or_equal': return cmp <= 0;
    case 'greater_or_equal': return cmp >= 0;
    case 'equal': return cmp === 0;
    case 'not_equal': return cmp !== 0;
    default: return false;
  }
}

// Check if a single rule matches the visitor click parameters
export async function matchRule(
  rule: FilterRule,
  params: ClickParams
): Promise<{ matches: boolean; reason: string }> {
  const operator = rule.operator;
  const value = rule.value;
  const id = rule.id;

  const standardFields: Record<string, keyof ClickParams> = {
    os: 'os',
    osver: 'osver',
    device: 'device',
    brand: 'brand',
    model: 'model',
    client: 'client',
    clientver: 'clientver',
    country: 'country',
    lang: 'lang',
    useragent: 'ua',
    referer: 'referer',
    domain: 'domain',
    host: 'host',
    isp: 'isp' as any // We handle ISP dynamically or fallback
  };

  if (standardFields[id]) {
    const paramField = standardFields[id];
    let visitorVal = String(params[paramField] || '').toLowerCase().trim();
    
    // special check if user agent
    if (id === 'useragent') {
      visitorVal = String(params.ua || '').toLowerCase().trim();
    }

    const compareVal = String(value || '').toLowerCase().trim();
    const compareList = compareVal.split(',').map(x => x.trim()).filter(Boolean);

    switch (operator) {
      case 'in':
      case 'param_in':
        return {
          matches: compareList.includes(visitorVal),
          reason: `${id}_in`
        };
      case 'not_in':
      case 'param_not_in':
        return {
          matches: !compareList.includes(visitorVal),
          reason: `${id}_not_in`
        };
      case 'contains':
        return {
          matches: compareList.some(term => visitorVal.includes(term)),
          reason: `${id}_contains`
        };
      case 'not_contains':
        return {
          matches: !compareList.some(term => visitorVal.includes(term)),
          reason: `${id}_not_contains`
        };
      case 'equal':
        return {
          matches: visitorVal === compareVal,
          reason: `${id}_equal`
        };
      case 'not_equal':
        return {
          matches: visitorVal !== compareVal,
          reason: `${id}_not_equal`
        };
      case 'less_or_equal':
      case 'greater_or_equal':
        return {
          matches: compareVersions(params[paramField] as string || '0', String(value || '0'), operator),
          reason: `${id}_version`
        };
    }
  }

  // Handle non-standard checks (VPN, URL parameters, IP Blacklist)
  switch (id) {
    case 'urlparam': {
      // urlparam value can be string or array: [paramName, paramValue]
      let pName = '';
      let pValue = '';
      if (Array.isArray(value)) {
        pName = String(value[0] || '');
        pValue = String(value[1] || '');
      } else {
        pName = String(value || '');
      }

      const visitorHasParam = Object.prototype.hasOwnProperty.call(params.qs, pName);
      const visitorParamVal = String(params.qs[pName] || '').toLowerCase().trim();
      const compareVal = pValue.toLowerCase().trim();
      const compareList = compareVal.split(',').map(x => x.trim()).filter(Boolean);

      if (operator === 'param_exists') {
        return { matches: visitorHasParam, reason: 'urlparam_exists' };
      }
      if (operator === 'param_not_exists') {
        return { matches: !visitorHasParam, reason: 'urlparam_not_exists' };
      }

      if (!visitorHasParam) {
        // If parameter does not exist, it matches the not_in rule
        return { matches: operator === 'param_not_in' || operator === 'not_in', reason: 'urlparam_missing' };
      }

      switch (operator) {
        case 'in':
        case 'param_in':
          return { matches: compareList.includes(visitorParamVal), reason: 'urlparam_in' };
        case 'not_in':
        case 'param_not_in':
          return { matches: !compareList.includes(visitorParamVal), reason: 'urlparam_not_in' };
        case 'contains':
          return { matches: compareList.some(term => visitorParamVal.includes(term)), reason: 'urlparam_contains' };
        case 'not_contains':
          return { matches: !compareList.some(term => visitorParamVal.includes(term)), reason: 'urlparam_not_contains' };
        case 'equal':
          return { matches: visitorParamVal === compareVal, reason: 'urlparam_equal' };
        case 'not_equal':
          return { matches: visitorParamVal !== compareVal, reason: 'urlparam_not_equal' };
      }
      break;
    }

    case 'vpntor': {
      // VPN/Proxy detection
      const checkVpn = async (ipAddress: string): Promise<boolean> => {
        if (ipAddress === '127.0.0.1' || ipAddress === '::1') return false;
        try {
          // Fast edge-compatible proxy lookup using proxycheck.io (free tier)
          const res = await fetch(`https://proxycheck.io/v2/${ipAddress}?vpn=1&asn=1`);
          const data = await res.json();
          if (data && data[ipAddress]) {
            return data[ipAddress].proxy === 'yes';
          }
        } catch (e) {
          console.error('Erro na verificação de VPN:', e);
        }
        return false;
      };

      const isVpn = await checkVpn(params.ip);
      // rule.value: 1 = block if VPN is NOT detected, 0 = block if VPN IS detected
      const matches = (value === 0 && isVpn) || (value === 1 && !isVpn);
      return { matches, reason: isVpn ? 'vpn_detected' : 'vpn_not_detected' };
    }

    case 'ipbase': {
      // Check if IP matches custom lists (like bots) stored in Postgres database using CIDR index
      try {
        const queryResult = await dbQuery(`
          SELECT 1 FROM ip_blacklist 
          WHERE network >> $1::inet 
          LIMIT 1
        `, [params.ip]);

        const inBase = queryResult.length > 0;
        const matches = (operator === 'in' && inBase) || (operator === 'not_in' && !inBase);
        return { matches, reason: inBase ? 'ipbase_match' : 'ipbase_no_match' };
      } catch (err) {
        console.error('Erro ao consultar ip_blacklist:', err);
        // Fallback: match nothing if DB fails
        return { matches: operator === 'not_in', reason: 'ipbase_error' };
      }
    }
  }

  return { matches: false, reason: 'unknown_filter' };
}

// Match filter group (recursive OR / AND evaluation)
export async function matchFilters(
  group: FilterGroup,
  params: ClickParams
): Promise<{ matches: boolean; reasons: string[] }> {
  if (!group || !Array.isArray(group.rules) || group.rules.length === 0) {
    return { matches: true, reasons: [] }; // No rules means whitelist
  }

  const isAnd = group.condition === 'AND';
  const matchedReasons: string[] = [];

  for (const rule of group.rules) {
    let result = false;
    let reasons: string[] = [];

    if ('condition' in rule) {
      // Nested filter group
      const res = await matchFilters(rule as FilterGroup, params);
      result = res.matches;
      reasons = res.reasons;
    } else {
      // Single filter rule
      const res = await matchRule(rule as FilterRule, params);
      result = res.matches;
      if (res.matches) {
        reasons = [res.reason];
      }
    }

    if (result) {
      matchedReasons.push(...reasons);
      if (!isAnd) {
        return { matches: true, reasons: matchedReasons }; // OR matches immediately
      }
    } else {
      if (isAnd) {
        return { matches: false, reasons: [] }; // AND fails immediately
      }
    }
  }

  return {
    matches: isAnd, // For AND, all must be true; for OR, none matched
    reasons: isAnd ? matchedReasons : []
  };
}
