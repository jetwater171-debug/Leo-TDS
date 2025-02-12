<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/dates.php';

global $c, $db;
$timeRange = Dates::get_time_range($c->statistics->timezone);
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "header.php" ?>
    <div class="all-content-wrapper">
    <?php include __DIR__."/statstableeditor.html" ?>

    <script>
        let availableClmns = <?= json_encode(AvailableColumns::get_columns_for_type('stats')) ?>;
        let availableDimensions = <?= json_encode(AvailableColumns::get_columns_for_type('groupby')) ?>;
    </script>
    <div class="buttons-block">
        <button id="addNewTable" title="Add new statisctics table" class="btn btn-primary"><i
                class="bi bi-plus-circle-fill"></i>&nbsp;New</button>
    </div>
    <script>
        $('#addNewTable').click(() => {
            initializeStatsTableEditor(
                availableClmns,
                [], // no selected columns
                availableDimensions,
                [], // no selected group by
                '', // no table name
                'clmnseditor.php?action=statstable'
            );
            $('#statsTableModal').modal({
                escapeClose: false,
                clickClose: false,
                showClose: false
            });
        });
    </script>
    <?php
    $tableData ='';
    $ss = $c->statistics;
    foreach ($ss->tables as $tSettings) {
        $dataset = $db->get_statistics(
            array_column($tSettings->columns, 'field'),
            $tSettings->groupby,
            $campId,
            $timeRange[0],
            $timeRange[1],
            $ss->timezone
        );
        $dJson = json_encode($dataset);
        $tName = $tSettings->name;
        $tColumns = get_stats_columns($tSettings->columns, $tName);
    ?>

            <div class="buttons-block" style="float: right;">
                <button id="columnsSelect<?=$tName?>" title="Edit table" class="btn btn-info"><i
                        class="bi bi-layout-three-columns"></i></button>
                <button id="download<?=$tName?>" title="Download table as CSV" class="btn btn-success"><i
                        class="bi bi-download"></i></button>
                <button id="delete<?=$tName?>" title="Delete table" class="btn btn-danger"><i
                        class="bi bi-trash-fill"></i></button>
            </div>
            <div id="t<?=$tName?>" style="clear: both;"></div>
            <script>
                let t<?=$tName?>Data = <?=$dJson?>;
                let t<?=$tName?>Columns = <?=$tColumns?>;
                let t<?=$tName?>Table = new Tabulator('#t<?=$tName?>', {
                    layout: "fitColumns",
                    columns: t<?=$tName?>Columns,
                    columnCalcs: "both",
                    pagination: "local",
                    paginationSize: 500,
                    paginationSizeSelector: [25, 50, 100, 200, 500, 1000, 2000, 5000],
                    paginationCounter: "rows",
                    dataTree: true,
                    dataTreeBranchElement:false,
                    dataTreeStartExpanded:false,
                    dataTreeChildIndent: 35,
                    height: "100%",
                    data: t<?=$tName?>Data,
                    columnDefaults:{
                        tooltip:true,
                    }
                });

                t<?=$tName?>Table.on("columnResized", async function (column) {
                    let updatedColumn = { field: column.getField(), width: column.getWidth() };
                    await fetch("clmnseditor.php?action=width&table=<?=$tName?>&campid=<?=$campId?>", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(updatedColumn),
                    });
                });

                document.getElementById("download<?=$tName?>").onclick = () => {
                    t<?=$tName?>Table.download("csv", "<?=$tName?>_data.csv");
                };
                document.getElementById("columnsSelect<?=$tName?>").onclick = async () => {
                    let selectedClmns = <?= json_encode($tSettings->columns) ?>;
                    let selectedDimensions = <?= json_encode($tSettings->groupby) ?>;
                    
                    initializeStatsTableEditor(
                        availableClmns,
                        selectedClmns,
                        availableDimensions,
                        selectedDimensions,
                        "<?=$tName?>",
                        `clmnseditor.php?action=statstable&table=<?=$tName?>&campid=<?=$campId?>`
                    );

                    $('#statsTableModal').modal({
                        modalClass: 'ywbmodal',
                        fadeDuration: 250,
                        fadeDelay: 0.80,
                        showClose: false
                    });
                };
                $('#delete<?=$tName?>').click((e) => {
                    const tableName = $(e.target).data('table-name');
                    deleteStatsTable(tableName, 'clmnseditor.php?action=statstable');
                });
            </script>
            <br/>
            <br/>
    <?php } ?>

    </div>
</body>

</html>
