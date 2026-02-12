<?php

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/logging.php';

class Campaign implements JsonSerializable
{
    public int $campaignId;
    public array $domains;
    public bool $saveUserFlow;
    public string $apiKey;

    public WhiteSettings $white;
    public BlackSettings $black;
    public ScriptsSettings $scripts;
    public PostbackSettings $postback;
    public StatisticsSettings $statistics;

    public function __construct(int $campId, array $s)
    {
        $this->campaignId = $campId;

        $this->domains = $s['domains'];
        $this->saveUserFlow = $s['saveuserflow'];
        $this->apiKey = $s['apikey'];

        $this->white = WhiteSettings::fromArray($s['white']);
        $this->black = BlackSettings::fromArray($s['black']);

        $this->scripts = ScriptsSettings::fromArray($s['scripts']);
        $this->postback = PostbackSettings::fromArray($s['postback']);
        $this->statistics = StatisticsSettings::fromArray($s['statistics']);
    }

    function jsonSerialize(): array
    {
        return [
            "domains" => $this->domains,
            "saveuserflow" => $this->saveUserFlow,
            "apikey" => $this->apiKey,
            "white" => $this->white,
            "black" => $this->black,
            "statistics" => $this->statistics,
            "postback" => $this->postback,
            "scripts" => $this->scripts
        ];
    }
}

class WhiteSettings implements JsonSerializable
{
    public array $filters;
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public array $curlUrls;
    public array $errorCodes;
    public bool $domainFilterEnabled;
    public array $domainSpecific;
    public JsChecks $jsChecks;

    public static function fromArray(array $s): WhiteSettings
    {
        $ws = new WhiteSettings();
        $ws->filters = $s['filters'] ?? [];
        $ws->action = $s['action'];
        $ws->folderNames = $s['folders'];
        $ws->redirectUrls = $s['redirect']['urls'];
        $ws->redirectType = $s['redirect']['type'];
        $ws->curlUrls = $s['curls'];
        $ws->errorCodes = $s['errorcodes'];
        $ws->domainFilterEnabled = $s['domainfilter']['use'];

        $ws->domainSpecific = [];
        foreach ($s['domainfilter']['domains'] as $df) {
            $ws->domainSpecific[] = DomainSpecificWhite::fromArray($df);
        }

        $ws->jsChecks = JsChecks::fromArray($s['jschecks']);
        return $ws;
    }

    public function jsonSerialize(): array
    {
        return [
            "filters" => $this->filters,
            "action" => $this->action,
            "folders" => $this->folderNames,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ],
            "curls" => $this->curlUrls,
            "errorcodes" => $this->errorCodes,
            "jschecks" => $this->jsChecks,
            "domainfilter" => [
                "use" => $this->domainFilterEnabled,
                "domains" => $this->domainSpecific
            ]
        ];
    }
}

class DomainSpecificWhite implements JsonSerializable
{
    public string $name;
    public string $action;

    public function __construct($name, $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    public static function fromArray($arr): DomainSpecificWhite
    {
        return new DomainSpecificWhite($arr['name'], $arr['action']);
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "action" => $this->action
        ];
    }
}

class BlackSettings implements JsonSerializable
{
    public string $jsconnectAction;
    /** @var FlowSettings[] */
    public array $flows;

    public static function fromArray($arr): BlackSettings
    {
        $bs = new BlackSettings();
        $bs->jsconnectAction = $arr['jsconnect'];
        $bs->flows = [];
        foreach ($arr['flows'] as $f) {
            $bs->flows[] = FlowSettings::fromArray($f);
        }
        return $bs;
    }

    public function jsonSerialize(): array
    {
        return [
            "jsconnect" => $this->jsconnectAction,
            "flows" => $this->flows
        ];
    }
}

class FlowSettings implements JsonSerializable
{
    public string $name;
    public array $filters;
    public PrelandSettings $preland;
    public LandingSettings $land;
    public string $distribution;
    public string $optimize_for;
    public string $optimize_mode;

    public static function fromArray($arr): FlowSettings
    {
        $fs = new FlowSettings();
        $fs->name = $arr['name'] ?? 'Flow';
        $fs->filters = $arr['filters'] ?? [];
        $fs->preland = PrelandSettings::fromArray($arr['prelanding']);
        $fs->land = LandingSettings::fromArray($arr['landing']);
        $fs->distribution = $arr['distribution'] ?? 'equal';
        $fs->optimize_for = $arr['optimize_for'] ?? 'Lead';
        $fs->optimize_mode = $arr['optimize_mode'] ?? 'funnels';
        return $fs;
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "filters" => $this->filters,
            "prelanding" => $this->preland,
            "landing" => $this->land,
            "distribution" => $this->distribution,
            "optimize_for" => $this->optimize_for,
            "optimize_mode" => $this->optimize_mode
        ];
    }

    public function hasPrelanding(): bool
    {
        return $this->preland->action !== 'none';
    }
}

class PrelandSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;
    public string $distribution;
    public array $weights;

    public static function fromArray($arr): PrelandSettings
    {
        $pls = new PrelandSettings();
        $pls->action = $arr['action'];
        $pls->folderNames = $arr['folders'];
        $pls->distribution = $arr['distribution'] ?? 'equal';
        $pls->weights = $arr['weights'] ?? [];
        return $pls;
    }

    public function jsonSerialize(): array
    {
        return [
            "action" => $this->action,
            "folders" => $this->folderNames,
            "distribution" => $this->distribution,
            "weights" => $this->weights
        ];
    }
}

class LandingSettings implements JsonSerializable
{
    public string $action;
    public array $folderNames;
    public array $redirectUrls;
    public int $redirectType;
    public string $distribution;
    public array $weights;

    public static function fromArray($arr): LandingSettings
    {
        $ls = new LandingSettings();
        $ls->action = $arr['action'];
        $ls->folderNames = $arr['folders'];
        $ls->redirectUrls = $arr['redirect']['urls'];
        $ls->redirectType = $arr['redirect']['type'];
        $ls->distribution = $arr['distribution'] ?? 'equal';
        $ls->weights = $arr['weights'] ?? [];
        return $ls;
    }

    public function jsonSerialize(): array
    {
        return [
            "action" => $this->action,
            "folders" => $this->folderNames,
            "redirect" => [
                "urls" => $this->redirectUrls,
                "type" => $this->redirectType
            ],
            "distribution" => $this->distribution,
            "weights" => $this->weights
        ];
    }
}

class JsChecks implements JsonSerializable
{
    public bool $enabled;
    public array $events;
    public int $timeout;
    public int $tzMin;
    public int $tzMax;

    public static function fromArray($arr): JsChecks
    {
        $jsc = new JsChecks();
        $jsc->enabled = $arr['enabled'];
        $jsc->events = $arr['events'];
        $jsc->timeout = $arr['timeout'];
        $jsc->tzMin = $arr['timezone']['min'];
        $jsc->tzMax = $arr['timezone']['max'];
        return $jsc;
    }
    public function jsonSerialize(): array
    {
        return [
            "jschecks" => [
                "enabled" => $this->enabled,
                "events" => $this->events,
                "timeout" => $this->timeout,
                "timezone" => [
                    "min" => $this->tzMin,
                    "max" => $this->tzMax
                ]
            ]
        ];

    }
}

class ScriptsSettings implements JsonSerializable
{
    public bool $backfix;
    public string $backfixAddress;
    public string $backfixSecondAddress;
    public bool $replacePrelanding;
    public string $replacePrelandingAddress;
    public bool $replaceLanding;
    public string $replaceLandingAddress;
    public bool $imagesLazyLoad;

    public static function fromArray($arr): ScriptsSettings
    {
        $ss = new ScriptsSettings();
        $ss->backfix = $arr['backfix']['use'] ?? false;
        $ss->backfixAddress = $arr['backfix']['url'] ?? '';
        $ss->backfixSecondAddress = $arr['backfix']['second'] ?? '';
        $ss->replacePrelanding = $arr['prelandingreplace']['use'] ?? false;
        $ss->replacePrelandingAddress = $arr['prelandingreplace']['url'] ?? '';
        $ss->replaceLanding = $arr['landingreplace']['use'] ?? false;
        $ss->replaceLandingAddress = $arr['landingreplace']['url'] ?? '';
        $ss->imagesLazyLoad = $arr['imageslazyload'] ?? false;
        return $ss;
    }

    public function jsonSerialize(): array
    {
        return [
            "scripts" => [
                "backfix" => [
                    "use" => $this->backfix,
                    "url" => $this->backfixAddress,
                    "second" => $this->backfixSecondAddress
                ],
                "replacePrelanding" => [
                    "use" => $this->replacePrelanding,
                    "url" => $this->replacePrelandingAddress
                ],
                "replaceLanding" => [
                    "use" => $this->replaceLanding,
                    "url" => $this->replaceLandingAddress
                ],
                "imagesLazyLoad" => $this->imagesLazyLoad
            ]
        ];
    }
}

class PostbackSettings implements JsonSerializable
{
    public array $s2sPostbacks;
    public string $leadStatusName;
    public string $purchaseStatusName;
    public string $rejectStatusName;
    public string $trashStatusName;

    public static function fromArray($arr): PostbackSettings
    {
        $ps = new PostbackSettings();

        $ps->s2sPostbacks = [];
        foreach ($arr['s2s'] as $s2s) {
            $ps->s2sPostbacks[] = S2sPostback::fromArray($s2s);
        }

        $ps->leadStatusName = $arr['events']['lead'];
        $ps->purchaseStatusName = $arr['events']['purchase'];
        $ps->rejectStatusName = $arr['events']['reject'];
        $ps->trashStatusName = $arr['events']['trash'];
        return $ps;
    }

    public function jsonSerialize(): array
    {
        return [
            "postback" => [
                "events" => [
                    "lead" => $this->leadStatusName,
                    "purchase" => $this->purchaseStatusName,
                    "reject" => $this->rejectStatusName,
                    "trash" => $this->trashStatusName
                ],
                "s2s" => $this->s2sPostbacks
            ]
        ];
    }
}

class S2sPostback implements JsonSerializable
{
    public string $url;
    public string $method;
    public array $events;

    public function __construct($url, $method, $events)
    {
        $this->url = $url;
        $this->method = $method;
        $this->events = $events;
    }

    public static function fromArray($arr): S2sPostback
    {
        return new S2sPostback($arr['url'], $arr['method'], $arr['events']);
    }

    public function jsonSerialize(): array
    {
        return [
            "url" => $this->url,
            "method" => $this->method,
            "events" => $this->events
        ];
    }

}

class StatisticsSettings implements JsonSerializable
{
    public string $timezone;
    public array $allowed;
    public array $leads;
    public array $blocked;
    public array $tables;

    public static function fromArray($arr)
    {
        $ss = new StatisticsSettings();
        $ss->timezone = $arr['timezone'];
        $ss->allowed = $arr['allowed'];
        $ss->leads = $arr['leads'];
        $ss->blocked = $arr['blocked'];
        $ss->tables = [];
        foreach ($arr['tables'] as $st) {
            $ss->tables[] = StatisticsTable::fromArray($st);
        }

        return $ss;
    }
    public function jsonSerialize(): array
    {
        return [
            "statistics" => [
                "timezone" => $this->timezone,
                "allowed" => $this->allowed,
                "leads" => $this->leads,
                "blocked" => $this->blocked,
                "tables" => $this->tables
            ]
        ];
    }
}

class StatisticsTable implements JsonSerializable
{
    public string $name;
    public array $columns;
    public array $groupby;

    public function __construct($name, $columns, $groupby)
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->groupby = $groupby;
    }

    public static function fromArray($arr): StatisticsTable
    {
        return new StatisticsTable($arr['name'], $arr['columns'], $arr['groupby']);
    }

    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "columns" => $this->columns,
            "groupby" => $this->groupby,
        ];
    }
}