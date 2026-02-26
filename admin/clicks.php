<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/dates.php';

global $db;
if (isset($_GET['campId'])) {
    require_once __DIR__ . '/campinit.php';
    global $c, $campId;
    $tz = $c->statistics->timezone;

} else {
    require_once __DIR__ .'/../db/db.php';
    $gs = $db->get_common_settings();
    $tz = $gs['statistics']['timezone'];
    $campId = null;
}

$timeRange = Dates::get_time_range($tz);
$startDate = $timeRange[0];
$endDate = $timeRange[1];

$filter = $_GET['filter'] ?? 'allowed';
$defaults = json_decode(file_get_contents(__DIR__ . '/../db/default.json'), true)['statistics'] ?? [];
switch ($filter) {
    case 'trafficback':
        $tableColumns = $gs['statistics']['trafficBack'];
        break;
    case 'leads':
        $tableColumns = $c->statistics->leads;
        if (empty($tableColumns)) $tableColumns = $defaults['leads'] ?? [];
        break;
    case 'blocked':
        $tableColumns = $c->statistics->blocked;
        if (empty($tableColumns)) $tableColumns = $defaults['blocked'] ?? [];
        break;
    case 'single':
        $tableColumns = $c->statistics->allowed;
        if (empty($tableColumns)) $tableColumns = $defaults['allowed'] ?? [];
        break;
    default:
        $tableColumns = $c->statistics->allowed;
        if (empty($tableColumns)) $tableColumns = $defaults['allowed'] ?? [];
        break;
}

$tName = empty($filter) ? 'allowed' : $filter;
$tColumns = Tabulator::get_clicks_columns($campId, $tz, $tableColumns);

// Load saved filters for this table type
$filterKeyMap = ['allowed' => 'allowedFilters', 'single' => 'allowedFilters', 'blocked' => 'blockedFilters', 'leads' => 'leadsFilters', 'trafficback' => 'trafficBackFilters'];
$filterKey = $filterKeyMap[$filter] ?? 'allowedFilters';
if ($filter === 'trafficback') {
    $savedFilters = $gs['statistics'][$filterKey] ?? [];
} else {
    $s = $db->get_campaign_settings($campId);
    $savedFilters = $s['statistics'][$filterKey] ?? [];
}
$hasActiveFilters = !empty($savedFilters) && !empty($savedFilters['rules']);

$ajaxParams = ['filter' => $filter];
if ($campId !== null) $ajaxParams['campId'] = $campId;
if ($filter === 'single') $ajaxParams['subid'] = $_GET['subid'] ?? '';
if (isset($_GET['startdate'])) $ajaxParams['startdate'] = $_GET['startdate'];
if (isset($_GET['enddate'])) $ajaxParams['enddate'] = $_GET['enddate'];
$ajaxUrl = 'clicksdata.php?' . http_build_query($ajaxParams);
?>

<!doctype html>
<html lang="en">
<?php include "head.php" ?>
<body>
    <?php include "header.php" ?>
    <div class="all-content-wrapper">
        <div class="buttons-block" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
            <?php if ($filter !== 'trafficback'):?>
                <span style="display: inline-block;">
                    <label for="filterSelector" style="color: white; font-weight: bold;">Filter:</label>
                    <select id="filterSelector" class="form-select" style="width: 140px; display: inline-block; margin-left: 10px;">
                        <option value="allowed"<?= $filter == 'allowed' ? ' selected' : '' ?>>Allowed</option>
                        <option value="blocked"<?= $filter == 'blocked' ? ' selected' : '' ?>>Blocked</option>
                        <option value="leads"<?= $filter == 'leads' ? ' selected' : '' ?>>Leads</option>
                    </select>
                </span>
            <?php endif; ?>
            </div>
            <div>
                <button id="resetFilters" title="Reset all filters" class="btn btn-outline-danger" style="margin-left: 8px;<?= $hasActiveFilters ? '' : ' display:none;' ?>"><i
                        class="bi bi-funnel"></i> Reset Filters</button>
                <button id="columnsSelect" title="Select and order columns" class="btn btn-info" style="margin-left: 8px;"><i
                        class="bi bi-layout-three-columns"></i></button>
                <button id="downloadCsv" title="Download table as XLSX" class="btn btn-success" style="margin-left: 8px;"><i
                        class="bi bi-download"></i></button>
            </div>
        </div>
        <div class="tabulator-scroll-wrapper">
        <div id="t<?=$tName?>" style="clear: both;"></div>
        </div>
        <script>
            $('#filterSelector').change(function() {
                let newUrl = new URL(window.location.href);
                newUrl.searchParams.set('filter', $(this).val());
                window.location.href = newUrl.href;
            });
            
            $('#resetFilters').click(async function() {
                try {
                    await fetch("clmnseditor.php?action=savecolumns&table=<?=$filter?><?=is_null($campId)?'':'&campid='.$campId?>", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ columns: <?= json_encode(array_map(fn($c) => is_array($c) ? $c['field'] : $c, $tableColumns)) ?>, filters: {} })
                    });
                    window.location.reload();
                } catch(e) { alert('Error resetting filters: ' + e.message); }
            });
            
            let t<?=$tName?>Columns = <?=$tColumns?>;
            let t<?=$tName?>Table = new Tabulator('#t<?=$tName?>', {
                layout: "fitData",
                columns: t<?=$tName?>Columns,
                nestedFieldSeparator: false,
                columnCalcs: "both",
                pagination: true,
                paginationMode: "remote",
                sortMode: "remote",
                paginationSize: 500,
                paginationSizeSelector: [25, 50, 100, 200, 500, 1000, 2000, 5000],
                paginationCounter: "rows",
                ajaxURL: "<?=$ajaxUrl?>",
                ajaxURLGenerator: function(url, config, params){
                    let u = new URL(url, window.location.href);
                    if(params.page) u.searchParams.set("page", params.page);
                    if(params.size) u.searchParams.set("size", params.size);
                    if(params.sort && params.sort.length){
                        u.searchParams.set("sort", params.sort[0].field);
                        u.searchParams.set("dir", params.sort[0].dir);
                    }
                    return u.toString();
                },
                columnDefaults:{
                    tooltip:true,
                },
                dependencies:{
                    XLSX:XLSX,
                }
            });

            t<?=$tName?>Table.on("columnResized", async function (column) {
                let updatedColumn = { field: column.getField(), width: column.getWidth() };
                await fetch("clmnseditor.php?action=width&table=<?=$filter?><?=is_null($campId)?'':'&campid='.$campId?>", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(updatedColumn),
                });
            });
        </script>
        <?php include __DIR__."/clmnspopup.html" ?>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("downloadCsv").onclick = () => {
                t<?=$tName?>Table.download("xlsx", `<?=$tName?>${getDateSuffix()}.xlsx`);
            };

            document.getElementById("columnsSelect").onclick = async () => {
                let availableClmns = <?= json_encode(AvailableColumns::get_columns_for_type($filter)) ?>;
                let selectedClmns = <?= json_encode($tableColumns) ?>;
                let existingFilters = <?= json_encode($savedFilters) ?>;
                addColumnsToList(selectedClmns, availableClmns, existingFilters, '<?= $filter ?>');
                setSaveButtonHandler("clmnseditor.php?action=savecolumns&table=<?= $filter ?><?= is_null($campId) ? '' : '&campid=' . $campId ?>");
                $('#columnModal').modal({
                    modalClass: 'ywbmodal',
                    fadeDuration: 250,
                    fadeDelay: 0.80,
                    showClose: false
                });
            }
        });
        </script>
        <br/>
        <br/>
    </div>
</body>
</html>