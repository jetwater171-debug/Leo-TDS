<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/clmns.php';

class Tabulator
{
    public static function get_stats_columns(array $columns, ?string $groupByClmnTitle = null, array $groupByFields = []): string
    {
        $columnSettings = TableColumns::$statsClmns;
        $tabulatorColumns = [];

        // Prepend the group column with proper title from the first groupby field
        if (!empty($groupByFields)) {
            $firstGroupBy = $groupByFields[0];
            $groupCol = $columnSettings['group'];
            // Use the title from the groupby field definition if available
            if (array_key_exists($firstGroupBy, $columnSettings) && isset($columnSettings[$firstGroupBy]['title'])) {
                $groupCol['title'] = $columnSettings[$firstGroupBy]['title'];
            } else {
                $groupCol['title'] = ucfirst($firstGroupBy);
            }
            $tabulatorColumns[] = $groupCol;
        }

        for ($i = 0; $i < count($columns); $i++) {
            $field = $columns[$i]['field'];
            // Skip columns that are already represented by groupby
            if (in_array($field, $groupByFields))
                continue;
            $width = $columns[$i]['width'] ?? -1;
            if (array_key_exists($field, $columnSettings)) {
                $tabulatorColumns[] = $columnSettings[$field];
            } else {
                $tabulatorColumns[] = ["title" => $field, "field" => $field];
            }
            if ($width === -1)
                continue;
            $tabulatorColumns[count($tabulatorColumns) - 1]["width"] = $width;
        }

        $clmnsJson = json_encode($tabulatorColumns, JSON_UNESCAPED_SLASHES);
        $clmnsJson = str_replace('"FSTART', '', $clmnsJson);
        $clmnsJson = str_replace('FEND"', '', $clmnsJson);
        return $clmnsJson;
    }


    public static function get_clicks_columns(?int $campId, string $timezone, array $columns): string
    {
        $columnSettings = TableColumns::$clickClmns;

        $defaultColumns =
        [
            "subid" => [
                "title" => "Subid",
                "field" => "subid",
                "formatter" => "link",
                "formatterParams" => [
                        "urlPrefix" => "clicks.php?campId=$campId&filter=single&subid="
                    ],
                "headerTooltip" => "Unique click id",
                "headerSort" => false,
                "editor" => false,
            ],
            "time" => [
                "title" => "Time",
                "field" => "time",
                "formatter" => "datetime",
                "formatterParams" => [
                        "inputFormat" => "unix",
                        "outputFormat" => "yyyy-MM-dd HH:mm:ss",
                        "timezone" => "$timezone"
                    ],
                "headerTooltip" => "Date and time according to selected timezone",
                "sorter" => "datetime",
                "sorterParams" => [
                        "format" => "unix"
                ],
                "editor" => false
            ]
        ];

        $tabulatorColumns = [];
        for ($i = 0; $i < count($columns); $i++) {
            $clmn = $columns[$i]['field'];
            $width = $columns[$i]['width'] ?? -1;
            if (array_key_exists($clmn, $columnSettings)) {
                $tabulatorColumns[] = $columnSettings[$clmn];
            } else if (array_key_exists($clmn, $defaultColumns)) {
                $tabulatorColumns[] = $defaultColumns[$clmn];
            } else {
                $tabulatorColumns[] = ["title" => $clmn, "field" => $clmn];
            }
            if ($width === -1)
                continue;
            $tabulatorColumns[count($tabulatorColumns) - 1]["width"] = $width;
        }
        $clmnsJson = json_encode($tabulatorColumns, JSON_UNESCAPED_SLASHES);
        $clmnsJson = str_replace('"FSTART', '', $clmnsJson);
        $clmnsJson = str_replace('FEND"', '', $clmnsJson);
        return $clmnsJson;
    }

    public static function get_campaigns_columns(array $columns): string
    {
        $defaultClmns = <<<JSON
    [
        {
            "title": "ID",
            "field": "id",
            "visible": false,
        },
        {
            "title": "Name",
            "formatter": "link",
            "formatterParams": {
                "urlField":"id",
                "urlPrefix":"campsettings.php?campId="
            },
            "field": "name",
            "headerFilter": false,
            "width": 90,
            "editor":"input",
            "bottomCalc":() => "TOTAL"
        },
        {
            "title": "Actions",
            "formatter": "html",
            "hozAlign": "center",
            "cellClick": campActionsHandler,
            "formatter": function() {
                return `
                    <button class="btn btn-camp btn-rename" title="Rename"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn btn-camp btn-delete" title="Delete"><i class="bi bi-trash-fill"></i></button>
                    <button class="btn btn-camp btn-clone" title="Clone"><i class="bi bi-copy"></i></button>
                    <button class="btn btn-camp btn-stats" title="View stats"><i class="bi bi-bar-chart-fill"></i></button>
                    <button class="btn btn-camp btn-allowed" title="View allowed clicks"><i class="bi bi-person-circle"></i></button>
                    <button class="btn btn-camp btn-blocked" title="View blocked clicks"><i class="bi bi-ban"></i></button>
                    <button class="btn btn-camp btn-leads" title="View leads"><i class="bi bi-coin"></i></button>`;
            },
            "width":200
        },
JSON;

        $statColumns = Tabulator::get_stats_columns($columns);
        $defaultClmns .= substr($statColumns, 1);
        return $defaultClmns;
    }
}