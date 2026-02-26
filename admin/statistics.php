<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/dates.php';

global $c, $campId, $db;
$timeRange = Dates::get_time_range($c->statistics->timezone);
$curTableIndex = $_GET['table']?? 0;

$ss = $c->statistics;
if (count($ss->tables)>0){
    $tSettings = $ss->tables[$curTableIndex];
    $tFilters = isset($tSettings->filters) ? (array)$tSettings->filters : [];
    $tOrderby = isset($tSettings->orderby) ? $tSettings->orderby : [];
    $dataset = $db->get_statistics(
        array_column($tSettings->columns, 'field'),
        $tSettings->groupby,
        $campId,
        $timeRange[0],
        $timeRange[1],
        $ss->timezone,
        $tFilters,
        $tOrderby
    );
    $dJson = json_encode($dataset);
    $tName = $tSettings->name;
    $tColumns = Tabulator::get_stats_columns($tSettings->columns, $tName, $tSettings->groupby);
}
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
    <div class="buttons-block" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button id="addNewTable" title="Add new statisctics table" class="btn btn-primary"><i
                    class="bi bi-plus-circle-fill"></i>&nbsp;New</button>
            <?php if (count($ss->tables)>0){ ?>
            <!-- Table selector with improved styling for dark background -->
            <span style="margin-left: 20px; display: inline-block;">
                <label for="tableSelector" style="color: white; font-weight: bold;">Table:</label>
                <select id="tableSelector" class="form-control" style="width: 150px; display: inline-block; margin-left: 10px; background-color: #fff; color: #000; appearance: auto; -webkit-appearance: menulist; padding-right: 24px;">
                    <?php
                    for ($i=0; $i<count($c->statistics->tables); $i++) {
                        $t = $c->statistics->tables[$i];
                        echo "<option value='" . $i . "'" . ($i == $curTableIndex ? " selected" : "") . ">" . $t->name . "</option>";
                    }
                    ?>
                </select>
            </span>
            <?php } ?>
        </div>
        <?php if (count($ss->tables)>0){ ?>
        <div>
            <button id="columnsSelect<?=$tName?>" title="Edit table" class="btn btn-info" style="margin-left: 8px;"><i
                    class="bi bi-layout-three-columns"></i></button>
            <button id="download<?=$tName?>" title="Download table as XLSX" class="btn btn-success" style="margin-left: 8px;"><i
                    class="bi bi-download"></i></button>
            <button id="delete<?=$tName?>" title="Delete table" class="btn btn-danger" style="margin-left: 8px;"><i
                    class="bi bi-trash-fill"></i></button>
        </div>
        <?php } ?>
    </div>
    <?php if (count($ss->tables)>0){ ?>
    <div class="tabulator-scroll-wrapper">
    <div id="t<?=$tName?>" style="clear: both;"></div>
    </div>
    <script>
        let t<?=$tName?>Data = <?=$dJson?>;
        let t<?=$tName?>Columns = <?=$tColumns?>;
        let t<?=$tName?>Table = new Tabulator('#t<?=$tName?>', {
            layout: "fitData",
            columns: t<?=$tName?>Columns,
            nestedFieldSeparator: false,
            columnCalcs: "both",
            pagination: "local",
            paginationSize: 500,
            paginationSizeSelector: [25, 50, 100, 200, 500, 1000, 2000, 5000],
            paginationCounter: "rows",
            dataTree: true,
            dataTreeBranchElement:false,
            dataTreeStartExpanded:false,
            dataTreeChildIndent: 15,
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

        $('#tableSelector').change(function() {
            let newUrl = new URL(window.location.href);
            newUrl.searchParams.set('table', $(this).val());
            window.location.href = newUrl.href;
        });
        
        document.getElementById("download<?=$tName?>").onclick = () => {
            const treeData = t<?=$tName?>Data;
            const table = t<?=$tName?>Table;

            // Get visible columns from Tabulator
            const cols = table.getColumns().filter(c => c.isVisible() && c.getField());
            const fields = cols.map(c => c.getField());
            const titles = cols.map(c => c.getDefinition().title || c.getField());

            // Flatten tree into rows with depth info
            const rowDepths = [];
            const flatRows = [];
            function walkTree(nodes, depth) {
                for (const node of nodes) {
                    flatRows.push(node);
                    rowDepths.push(depth);
                    if (node._children && node._children.length) {
                        walkTree(node._children, depth + 1);
                    }
                }
            }
            walkTree(treeData, 0);

            // TOTAL row from Tabulator's columnCalcs (correct formulas for %, ratios, etc.)
            const calcResults = table.getCalcResults();
            const totalData = calcResults.bottom || {};
            totalData[fields[0]] = 'TOTAL';

            // Build AOA
            const aoa = [titles];
            flatRows.forEach(node => {
                aoa.push(fields.map(f => { const v = node[f]; return v !== undefined && v !== null ? v : ''; }));
            });
            aoa.push(fields.map(f => {
                const v = totalData[f];
                if (v === undefined || v === null) return '';
                if (typeof v === 'string' && v !== 'TOTAL') { const n = parseFloat(v); if (!isNaN(n)) return n; }
                return v;
            }));

            // Build worksheet + workbook
            const ws = XLSX.utils.aoa_to_sheet(aoa);
            // Set hidden rows (SheetJS community writes hidden but not outlineLevel)
            ws['!rows'] = [{}]; // header
            rowDepths.forEach(d => ws['!rows'].push(d > 0 ? { hidden: true } : {}));
            ws['!rows'].push({}); // TOTAL

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');

            // Generate xlsx as zip, patch sheet XML to add outlineLevel + outlinePr
            const maxDepth = Math.max(0, ...rowDepths);
            if (maxDepth > 0) {
                const zipData = XLSX.write(wb, { bookType: 'xlsx', type: 'array', compression: true });
                const zip = new JSZip();
                zip.loadAsync(zipData).then(z => {
                    return z.file('xl/worksheets/sheet1.xml').async('string');
                }).then(xml => {
                    // Add outlineLevel to <row> elements (row numbers are 1-indexed, row 1 = header)
                    let rowIdx = 0;
                    xml = xml.replace(/<row /g, (match) => {
                        const dataIdx = rowIdx - 1; // -1 for header row
                        rowIdx++;
                        if (dataIdx >= 0 && dataIdx < rowDepths.length && rowDepths[dataIdx] > 0) {
                            return match + 'outlineLevel="' + rowDepths[dataIdx] + '" ';
                        }
                        return match;
                    });
                    // Add <sheetPr><outlinePr summaryBelow="0"/></sheetPr> after <worksheet> tag
                    xml = xml.replace(/<sheetPr[^>]*\/>/, '<sheetPr><outlinePr summaryBelow="0"/></sheetPr>');
                    xml = xml.replace(/<sheetPr>/, '<sheetPr><outlinePr summaryBelow="0"/>');
                    if (!xml.includes('outlinePr')) {
                        xml = xml.replace(/<worksheet[^>]*>/, '$&<sheetPr><outlinePr summaryBelow="0"/></sheetPr>');
                    }
                    return zip.file('xl/worksheets/sheet1.xml', xml).generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                }).then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `<?=$tName?>${getDateSuffix()}.xlsx`;
                    a.click();
                    URL.revokeObjectURL(url);
                });
            } else {
                XLSX.writeFile(wb, `<?=$tName?>${getDateSuffix()}.xlsx`);
            }
        };
        document.getElementById("columnsSelect<?=$tName?>").onclick = async () => {
            let selectedClmns = <?= json_encode($tSettings->columns) ?>;
            let selectedDimensions = <?= json_encode($tSettings->groupby) ?>;
            
            let existingFilters = <?= json_encode(isset($tSettings->filters) ? $tSettings->filters : new stdClass()) ?>;
            let existingOrderby = <?= json_encode(isset($tSettings->orderby) ? $tSettings->orderby : []) ?>;
            initializeStatsTableEditor(
                availableClmns,
                selectedClmns,
                availableDimensions,
                selectedDimensions,
                "<?=$tName?>",
                `clmnseditor.php?action=savestats&name=<?=$tName?>&campid=<?=$campId?>`,
                existingFilters,
                existingOrderby
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
    <?php } ?>
    <script>
        $('#addNewTable').click(() => {
            initializeStatsTableEditor(
                availableClmns,
                [], // no selected columns
                availableDimensions,
                [], // no selected group by
                'New', // no table name
                'clmnseditor.php?action=savestats&campid=<?=$campId?>',
                {},
                []
            );
            $('#statsTableModal').modal({
                modalClass: 'ywbmodal',
                fadeDuration: 250,
                fadeDelay: 0.80,
                showClose: false
            });
        });
    </script>
    <br/>
    <br/>
    </div>
</body>

</html>
