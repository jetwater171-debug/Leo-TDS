<?php
class AvailableColumns
{
    static $blockedColumns = [
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "reason"
    ];

    static $allowedColumns = [
        "subid",
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "preland",
        "land",
        "lpclick",
        "status",
        "cost",
        "payout"
    ];

    static $leadsColumns = [
        "subid",
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "preland",
        "land",
        "lpclick",
        "status",
        "payout",
        "name",
        "phone"
    ];
    static $trafficbackColumns = [
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params"
    ];

    static $groupbyColumns = [
        "date",
        "country",
        "isp",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "client",
        "clientver",
        "preland",
        "land"
    ];

    static $statsColumns = [
        "clicks",
        "uniques",
        "uniques_ratio",
        "lpclicks",
        "lpctr",
        "cra",
        "crs",
        "epc",
        "uepc",
        "cpc",
        "ucpc",
        "appt",
        "app",
        "conversion",
        "purchase",
        "hold",
        "reject",
        "trash",
        "cpa",
        "ec",
        "revenue",
        "costs",
        "profit",
        "roi"
    ];

    public static function get_columns_for_type($type)
    {
        if ($type === 'single') $type = 'allowed';
        $clmnsName = $type . 'Columns';
        return self::$$clmnsName;
    }
}

class TableColumns
{
    public static array $clickClmns =
        [
            "ip" => [
                "title" => "IP",
                "field" => "ip",
                "headerFilter" => "input",
            ],
            "country" => [
                "title" => "Country",
                "field" => "country",
                "headerFilter" => "input",
            ],
            "lang" => [
                "title" => "Lang",
                "field" => "lang",
                "headerTooltip" => "Language",
                "headerFilter" => "input",
            ],
            "isp" => [
                "title" => "ISP",
                "field" => "isp",
                "headerFilter" => "input",
                "headerTooltip" => "Internet Service Provider"
            ],
            "os" => [
                "title" => "OS",
                "field" => "os",
                "headerFilter" => "input",
                "headerTooltip" => "OS",
            ],
            "osver" => [
                "title" => "OSVer",
                "field" => "osver",
                "headerTooltip" => "OS version",
                "headerFilter" => "input",
            ],
            "client" => [
                "title" => "Client",
                "field" => "client",
                "headerTooltip" => "Client (browser)",
                "headerFilter" => "input",
            ],
            "clientver" => [
                "title" => "ClientVer",
                "field" => "clientver",
                "headerTooltip" => "Client (browser) version",
                "headerFilter" => "input",
            ],
            "device" => [
                "title" => "Device",
                "field" => "device",
                "headerTooltip" => "Device",
                "headerFilter" => "input",
            ],
            "brand" => [
                "title" => "Brand",
                "field" => "brand",
                "headerTooltip" => "Brand",
                "headerFilter" => "input",
            ],
            "model" => [
                "title" => "Model",
                "field" => "model",
                "headerTooltip" => "Model",
                "headerFilter" => "input",
            ],
            "ua" => [
                "title" => "UA",
                "field" => "ua",
                "headerTooltip" => "User agent",
                "headerFilter" => "input",
                "formatter" => "textarea",
                "width" => "200"
            ],
            "params" => [
                "title" => "Subs",
                "field" => "params",
                "headerTooltip" => "All url parameters",
                "headerFilter" => "input",
                "headerFilterFunc" => "FSTARTfunction(headerValue, rowValue, rowData, filterParams){ if (rowValue.length===0) return false; return JSON.stringify(rowValue).includes(headerValue);}FEND", 
                "headerSort" => false,
                "tooltip" => "FSTARTfunction(e, cell, onRendered){ var data = cell.getValue(); var keys = Object.keys(data).sort(); var formattedData = ''; keys.forEach(function(key) { if (data.hasOwnProperty(key)) { formattedData += key + '=' + data[key] + '<br>'; } }); return formattedData;}FEND",
                "formatter" => "FSTARTfunction(cell, formatterParams, onRendered){var data = cell.getValue();var keys = Object.keys(data).sort();var formattedData = ''; keys.forEach(function(key) { if (data.hasOwnProperty(key)) { formattedData += key + '=' + data[key] + '<br>';}}); return formattedData;}FEND"
            ],
            "preland"=>[
                "title" => "Preland",
                "field" => "preland",
                "headerTooltip" => "Chosen prelanding",
                "headerFilter" => "input",
            ],
            "land"=>[
                "title" => "Land",
                "field" => "land",
                "headerTooltip" => "Chosen landing",
                "headerFilter" => "input",
            ],
            "reason"=>[
                "title" => "Reason",
                "headerTooltip" => "Why click was blocked",
                "field" => "reason",
                "formatter" => "plaintext",
                "sorter" => "string",
                "headerFilter" => "input"
            ],
            "lpclick"=>[
                "title" => "LpClick",
                "field" => "lpclick",
                "sorter" => "boolean",
                "formatter" => "tickCross",
                "formatterParams" => [
                    "tristate" => true,
                ],
                "hozAlign" => "center",
                "headerFilter" => true,
                "editor" => "tickCross",
                "editorParams" => [
                    "tristate" => true,
                ]
            ],
            "status"=>[
                "title" => "Status",
                "field" => "status",
                "headerFilter" => "input",
            ],
            "payout"=>[
                "title" => "Payout",
                "field" => "payout",
                "headerFilter" => "input",
            ],
        ];


    public static array $statsClmns = [
        'group' => [
            "field" => "group",
            "headerTooltip" => "Group By",
            "headerFilter" => "input",
            "bottomCalc"=>"FSTARTfunction(values, data, calcParams){return 'TOTAL';}FEND",
        ],
        'preland' => [
            "title" => "Preland",
            "headerTooltip" => "Chosen prelanding",
            "field" => "preland",
            "headerFilter" => "input",
        ],
        'land' => [
            "title" => "Land",
            "headerTooltip" => "Chosen landing",
            "field" => "land",
            "headerFilter" => "input",
        ],
        'country' => [
            "title" => "Country",
            "field" => "country",
            "headerFilter" => "input",
        ],
        'lang' => [
            "title" => "Lang",
            "headerTooltip" => "Browser language",
            "field" => "lang",
            "headerFilter" => "input",
        ],
        'isp' => [
            "title" => "ISP",
            "headerTooltip" => "Internet Service Provider",
            "field" => "isp",
            "headerFilter" => "input",
        ],
        'date' => [
            "title" => "Date",
            "field" => "date",
            "sorter" => "date",
            "sorterParams" => [
                "format" => "yyyy-MM-dd",
                "alignEmptyValues" => "top",
            ]
        ],
        'os' => [
            "title" => "OS",
            "headerTooltip" => "Operating System",
            "field" => "os",
            "headerFilter" => "input",
        ],
        'clicks' => [
            "title" => "Clicks",
            "headerTooltip" => "Number of visitors",
            "field" => "clicks",
            "bottomCalc" => "sum"
        ],
        'uniques' => [
            "title" => "Uniques",
            "headerTooltip" => "Number of unique visitors",
            "field" => "uniques",
            "bottomCalc" => "sum"
        ],
        'uniques_ratio' => [
            "title" => "U/C",
            "headerTooltip" => "Unique visitors / visitors",
            "field" => "uniques_ratio",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'conversion' => [
            "title" => "CV",
            "headerTooltip" => "Conversions",
            "field" => "conversion",
            "bottomCalc" => "sum"
        ],
        'purchase' => [
            "title" => "P",
            "headerTooltip" => "Purchases",
            "field" => "purchase",
            "bottomCalc" => "sum"
        ],
        'hold' => [
            "title" => "H",
            "headerTooltip" => "Holds",
            "field" => "hold",
            "bottomCalc" => "sum"
        ],
        'reject' => [
            "title" => "R",
            "headerTooltip" => "Rejects",
            "field" => "reject",
            "bottomCalc" => "sum"
        ],
        'trash' => [
            "title" => "T",
            "headerTooltip" => "Trashes",
            "field" => "trash",
            "bottomCalc" => "sum"
        ],
        'lpclicks' => [
            "title" => "LPClicks",
            "headerTooltip" => "Landing page visitors",
            "field" => "lpclicks",
            "bottomCalc" => "sum"
        ],
        'lpctr' => [
            "title" => "LPCTR",
            "headerTooltip" => "Landing page visitors percentage",
            "field" => "lpctr",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'cra' => [
            "title" => "CRa",
            "headerTooltip" => "Total conversion rate",
            "field" => "cra",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'crs' => [
            "title" => "CRs",
            "headerTooltip" => "Conversion into Sales rate",
            "field" => "crs",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'appt' => [
            "title" => "App(t)",
            "headerTooltip" => "Approve rate without Trash conversions",
            "field" => "appt",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'app' => [
            "title" => "App",
            "headerTooltip" => "Approve rate",
            "field" => "app",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'cpc' => [
            "title" => "CPC",
            "headerTooltip" => "Cost per click",
            "field" => "cpc",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'ucpc' => [
            "title" => "CPuC",
            "headerTooltip" => "Cost per unique click",
            "field" => "ucpc",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'cpa' => [
            "title" => "CPA",
            "headerTooltip" => "Cost per action (conversion)",
            "field" => "cpa",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'ec' => [
            "title" => "EC",
            "headerTooltip" => "Earnings per conversion",
            "field" => "ec",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'costs' => [
            "title" => "Costs",
            "headerTooltip" => "Traffic costs",
            "field" => "costs",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ]
        ],
        'epc' => [
            "title" => "EPC",
            "headerTooltip" => "Earnings Per Click",
            "field" => "epc",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'uepc' => [
            "title" => "EPuC",
            "headerTooltip" => "Earnings Per Unique Click",
            "field" => "uepc",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'revenue' => [
            "title" => "Rev.",
            "headerTooltip" => "Revenue",
            "field" => "revenue",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ]
        ],
        'profit' => [
            "title" => "Profit",
            "headerTooltip" => "Profit",
            "field" => "profit",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ]
        ],
        'roi' => [
            "title" => "ROI",
            "headerTooltip" => "Return On Investment",
            "field" => "roi",
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
        ],
    ];
}
