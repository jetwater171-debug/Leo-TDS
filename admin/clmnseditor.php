<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../logging.php';

$passOk = check_password(false);
if (!$passOk)
    return send_clmnseditor_result("Error: password check not passed!",true);

$action = $_REQUEST['action'];
$table = $_REQUEST['table']??''; //table type: various clicks or campaigns or stats
$tName = $_REQUEST['name']??''; //table name for stats
$campId = $_REQUEST['campid']??null;
$postData = file_get_contents('php://input');

//ugly one but we need it!
if ($action === 'trafficback') {
    $s = $db->get_common_settings();
    $s['trafficBackUrl'] = $postData;
    $res = $db->set_common_settings($s);
    if ($res===false)
        return send_clmnseditor_result("Error saving settings!",true);
    return send_clmnseditor_result("OK");
}

add_log('trace', "ColumnsEditor action: $action, table: $table, name: $tName, campId: $campId");

switch ($action) {
    case 'width':
        $currentColumns = $table==='stats' ? 
            get_current_stats_columns($tName, $campId) : 
            get_current_columns_for_type($table, $campId);
        $uc = json_decode($postData, true);
        update_width($currentColumns, $uc);
        $saved = $table==='stats' ? 
            save_stats_columns($currentColumns, $tName, $campId) :
            save_columns_for_type($currentColumns, $table, $campId);
        return $saved? 
            send_clmnseditor_result("OK"):
            send_clmnseditor_result("Error saving settings!",true);
    case 'savecolumns':
        $currentColumns = get_current_columns_for_type($table, $campId);
        $data = json_decode($postData, true);
        if (empty($data)) {
            return send_clmnseditor_result("Error: missing columns data", true);
        }

        $newColumns = get_new_columns($currentColumns, $data);
        $saved = save_columns_for_type($newColumns, $table, $campId);
        return $saved? 
            send_clmnseditor_result("OK"):
            send_clmnseditor_result("Error saving settings!",true);
    
    case 'newstats':
    case 'savestats':
        $data = json_decode($postData, true);

        if (!isset($data['name']) || !isset($data['columns']) || !isset($data['groupby'])) {
            return send_clmnseditor_result("Error: invalid table configuration", true);
        }

        $saved = save_stats_table($campId, $tName,$data);

        return $saved?
            send_clmnseditor_result("Stats table saved successfully"):
            send_clmnseditor_result("Error saving table",true);
    case 'delstats':
        $data = json_decode($postData, true);

        if (!isset($data['name'])) {
            return send_clmnseditor_result("Error: missing table name", true);
        }

        $deleted = delete_stats_table($campId, $data['name']);
        return $deleted?
            send_clmnseditor_result("Stats table deleted successfully"):
            send_clmnseditor_result("Error deleting table",true);
    default:
        return send_clmnseditor_result("Error: unknown action", true);
}

function send_clmnseditor_result($msg,$error=false): void
{
    $res = ["result" => $msg];
    if ($error){
        $res['error']=true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    $json = json_encode($res);
    echo $json;
}


/**
 * Updates the width of the specified column in the specified table array
 * @param array $table
 * @param array $c
 * @return void
 */
function update_width(array &$table, array $c) {
    foreach ($table as &$tcolumn) {
        if ($tcolumn['field'] !== $c['field'])
            continue;
        $tcolumn['width'] = $c['width'];
        break;
    }
}


function get_new_columns($existingColumns, $newColumnNames): array
{
    $newColumns = [];

    // Process each column from the new data
    foreach ($newColumnNames as $cName) {
        $found = false;
        foreach ($existingColumns as $existingColumn) {
            if ($existingColumn['field'] === $cName) {
                $newColumns[] = $existingColumn;
                $found = true;
                break;
            }
        }
        // If column not found, add it with default width
        if (!$found) {
            $newColumns[] = ['field' => $cName, 'width' => -1];
        }
    }

    return $newColumns;
}

function get_current_columns_for_type(string $table, ?int $campId = null): array{
    global $db;
    switch($table){
        case 'campaigns':
            $s = $db->get_common_settings();
            return $s['statistics']['table'];
        case 'trafficback':
            $s = $db->get_common_settings();
            return $s['statistics']['trafficBack'];
        case 'allowed':
        case 'single':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['allowed'];
        case 'blocked':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['blocked'];
        case 'leads':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['leads'];
        default:
            $errMsg = "Table $table not found in campaign settings";
            add_log('error', $errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
}

function get_current_stats_columns(string $name, int $campId, bool $groupBy = false):array{
    global $db;
    $s = $db->get_campaign_settings($campId);
    foreach ($s['statistics']['tables'] as $t) {
        if ($t['name'] === $name) {
            return $groupBy ? $t['groupby'] : $t['columns'];
        }
    }
    return [];
}

function save_columns_for_type(array $columns, string $table, ?int $campId = null):bool{
    global $db;
    switch($table){
        case 'campaigns':
            $s = $db->get_common_settings();
            $s['statistics']['table'] = $columns;
            return $db->set_common_settings($s);
        case 'trafficback':
            $s = $db->get_common_settings();
            $s['statistics']['trafficBack'] = $columns;
            return $db->set_common_settings($s);
        case 'allowed':
        case 'single':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['allowed'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        case 'blocked':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['blocked'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        case 'leads':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['leads'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        default:
            $errMsg = "Table $table not found in campaign settings";
            add_log('error', $errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
}

function save_stats_columns(array $columns, string $name, int $campId): bool
{  
    global $db;
    $s = $db->get_campaign_settings($campId);
    foreach ($s['statistics']['tables'] as &$t) {
        if ($t['name'] === $name) {
            $t['columns'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        }
    }
    
    $errMsg = "Stats table $name not found in campaign $campId settings";
    add_log('error', $errMsg);
    trigger_error($errMsg, E_USER_ERROR);
    exit;
}

function save_stats_table(int $campId, string $tableName,array $tableConfig): bool
{
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        add_log('error', "Error: campaign $campId not found");
        return false;
    }

    // Find if table with this name already exists
    $existingTableIndex = -1;
    foreach ($s['statistics']['tables'] as $index => &$t) {
        if ($t['name'] === $tableName) {
            $existingTableIndex = $index;
            break;
        }
    }

    $allColumnNames = array_merge($tableConfig['groupby'], $tableConfig['columns']);
    // Create new table object
    $table = [
        'name' => $tableConfig['name'], 
        'columns' => get_new_columns([], $allColumnNames),
        'groupby' => $tableConfig['groupby']
    ];

    // Update or add the table
    if ($existingTableIndex >= 0) {
        $s['statistics']['tables'][$existingTableIndex] = $table;
    } else {
        $s['statistics']['tables'][] = $table;
    }

    // Save settings
    return $db->save_campaign_settings($campId, $s);

}

function delete_stats_table($campId, $tableName): bool {
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        add_log('error', "Error: campaign $campId not found");
        return false;
    }

    // Find and remove the table
    $found = false;
    foreach ($s['statistics']['tables'] as $index => $table) {
        if ($table['name'] === $tableName) {
            array_splice($s['statistics']['tables'], $index, 1);
            $found = true;
            break;
        }
    }

    if (!$found) {
        add_log('error', "Error deleting stats table: table $tableName not found in campaign $campId");
        return false;
    }

    // Save settings
    return $db->save_campaign_settings($campId, $s);
}