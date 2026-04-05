```
                            Yellow TDS
    _            __     __  _ _             __          __  _
   | |           \ \   / / | | |            \ \        / / | |
   | |__  _   _   \ \_/ /__| | | _____      _\ \  /\  / /__| |__
   | '_ \| | | |   \   / _ \ | |/ _ \ \ /\ / /\ \/  \/ / _ \ '_ \
   | |_) | |_| |    | |  __/ | | (_) \ V  V /  \  /\  /  __/ |_) |
   |_.__/ \__, |    |_|\___|_|_|\___/ \_/\_/    \/  \/ \___|_.__/
           __/ |
          |___/             https://yellowweb.top

If you like this script, PLEASE DONATE!
USDT TRC20: TKeNEVndhPSKXuYmpEwF4fVtWUvfCnWmra
Bitcoin: bc1qqv99jasckntqnk0pkjnrjtpwu0yurm0qd0gnqv
Ethereum: 0xBC118D3FDE78eE393A154C29A4545c575506ad6B
```

# Yellow TDS - Documentation (EN)

## What this project is

Yellow TDS is a PHP traffic routing app that decides per-visitor action (`white`/`black`/`trafficback`) using campaign rules, with click logging, lead tracking, postbacks, and an admin UI.

## Current requirements

- PHP `>= 8.2`
- PHP extensions: `curl`, `sqlite3`
- Valid HTTPS on your domain
- Writable project directories (`db`, `logs`, `cache`, sessions)

Runtime checks are enforced in `debug.php`.

## Quick start

1. Deploy contents of `fromfolder/` to your hosting.
2. Edit `settings.php` and set at least:
- `adminPassword`
- `adminDomain` (optional)
- `dbConnection` (SQLite filename inside `db/`)
- `debug` (`false` for production)
- `maxMindKey` (optional, for GeoLite2 auto-updates)
3. Open `https://your-domain/admin/` and sign in.
4. Create a campaign, configure domains, white/black actions, filters, postbacks, then save.

## Main request flow

- `index.php` -> `tds.php` -> selects action (`white`, `black`, `trafficback`)
- `core.php` collects click params: IP, GEO, ISP, OS, browser, UA, query params
- `next.php` and `send.php` handle step transitions + form forwarding
- `postback.php` receives S2S status updates and updates leads

## Data storage

SQLite database file: `db/<dbConnection>`.

Main tables:
- `campaigns` - campaign JSON settings
- `clicks` - allowed clicks and leads
- `blocked` - filtered clicks
- `trafficback` - clicks without matching campaign
- `common` - global UI settings

Schema: `db/db.sql`.

## Admin panel

Main pages:
- `admin/index.php` - campaigns list
- `admin/campsettings.php` - campaign settings
- `admin/clicks.php` - allowed/blocked/leads/trafficback logs
- `admin/statistics.php` - aggregated stats tables

Campaign settings blocks include:
- Domains
- Safe page (white): `folder`, `redirect`, `curl`, `error`
- Money page (black): multi-step flows (`steps[]`, folder/redirect per step)
- Filters (query-builder, AND/OR groups)
- Scripts (backfix, transit/landing replace, lazy images)
- Statistics (timezone, columns/tables/grouping)
- Postbacks (incoming + outgoing S2S)
- API key for `phpconnect.php`

## Integrations

### JS connect

Include:

```html
<script src="https://your-domain/js/index.php"></script>
```

`js/index.php` can return JS to:
- replace page content
- show iframe
- do meta redirect
- process JS-check flow

### PHP API

Endpoint: `phpconnect.php`

Constraints:
- `POST` only
- `User-Agent` must contain `YellowTDS`
- JSON body with `api_key` and request params (`tds_ua`, `tds_ref`, `tds_ip`, ...)

Reference client: `phpclient.php`.

### Postback

Endpoint: `postback.php`

Required params:
- `clickid`
- `status`
- `payout`

Optional:
- `currency` (`USD` default, converted via `currency.php`)

## UTP (Universal Thank You Page)

If `settings.php -> useUTP = true`, lead flow uses `thankyou/index.php`.

UTP features:
- template selection/generation
- translation via `thankyou/translator.php`
- caching in `thankyou/cache/`
- macro replacement (`{NAME}`, `{PHONE}`, `{CLICKID}`)

## Logs and maintenance

Logs are written to `logs/<subdir>/`.

Common folders:
- `logs/error`
- `logs/login`
- `logs/postback`
- `logs/trace` (when `debug=true`)

GeoLite2 updater: `bases/update.php` (requires `maxMindKey`).

## Security checklist

- Change default `adminPassword` immediately.
- Set `adminDomain` if admin must be restricted to one host.
- Keep `debug=false` in production.
- Do not expose real keys/passwords in public repos.
- On nginx, block direct access to SQLite DB files.

## Notes about current version

- Campaign settings source of truth is UI + SQLite, not per-campaign PHP variables.
- `admin/autoupdate.php` has partial implementation; file-copy step is currently disabled (TODO).
- Some sample/dev folders in repo (`black`, `white`, `student`) are not mandatory for production.
