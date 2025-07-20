<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/dates.php';

global $c, $campId, $db;
$timeRange = Dates::get_time_range($c->statistics->timezone);
$curTableIndex = $_GET['table']?? 0;
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
                
        <!-- Table selector with improved styling for dark background -->
        <span style="margin-left: 20px; display: inline-block;">
            <label for="tableSelector" style="color: white; font-weight: bold;">Table:</label>
            <select id="tableSelector" class="form-control" style="width: 150px; display: inline-block; margin-left: 10px; background-color: #fff; color: #000;">
                <?php
                for ($i=0; $i<count($c->statistics->tables); $i++) {
                    $t = $c->statistics->tables[$i];
                    echo "<option value='" . $i . "'" . ($i == $curTableIndex ? " selected" : "") . ">" . $t->name . "</option>";
                }
                ?>
            </select>
        </span>
    </div>
    <script>
        $('#addNewTable').click(() => {
            initializeStatsTableEditor(
                availableClmns,
                [], // no selected columns
                availableDimensions,
                [], // no selected group by
                'New', // no table name
                'clmnseditor.php?action=savestats&campid=<?=$campId?>'
            );
            $('#statsTableModal').modal({
                modalClass: 'ywbmodal',
                fadeDuration: 250,
                fadeDelay: 0.80,
                showClose: false
            });
        });
        $('#tableSelector').change(function() {
            let newUrl = new URL(window.location.href);
            newUrl.searchParams.set('table', $(this).val());
            window.location.href = newUrl.href;
        });
    </script>
    
    <?php
    $ss = $c->statistics;
    $tSettings = $ss->tables[$curTableIndex];
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
    $tColumns = Tabulator::get_stats_columns($tSettings->columns, $tName);
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
                dataTreeChildIndent: 15,
                height: "100%",
                data: t<?=$tName?>Data,
                columnDefaults:{
                    tooltip:true,
                },
                dependencies:{
                    XLSX:XLSX,
                }
            });

            t<?=$tName?>Table.on("columnResized", async function (column) {
                let updatedColumn = { field: column.getField(), width: column.getWidth() };
                await fetch("clmnseditor.php?action=width&name=<?=$tName?>&table=stats&campid=<?=$campId?>", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(updatedColumn),
                });
            });

            document.getElementById("download<?=$tName?>").onclick = () => {
                t<?=$tName?>Table.download("xlsx", "<?=$tName?>_data.xlsx");
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
                    `clmnseditor.php?action=savestats&name=<?=$tName?>&campid=<?=$campId?>`
                );

                $('#statsTableModal').modal({
                    modalClass: 'ywbmodal',
                    fadeDuration: 250,
                    fadeDelay: 0.80,
                    showClose: false
                });
            };
            $('#delete<?=$tName?>').click((e) => {
                deleteStatsTable('<?=$tName?>', 'clmnseditor.php?action=delstats&name=<?=$tName?>&campid=<?=$campId?>');
            });
        </script>
        <br/>
        <br/>
    </div>
</body>

</html>
