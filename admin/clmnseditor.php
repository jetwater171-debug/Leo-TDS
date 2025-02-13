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

add_log('trace', "ColumnsEditor action: $action, table: $table, campId: $campId");
$currentColumns = get_columns_for_type($table, $campId);

switch ($action) {
    case 'width':
        $uc = json_decode($postData, true);
        update_width($currentColumns, $uc);
        save_columns_for_type($currentColumns, $table, $campId);
        return send_clmnseditor_result("OK");
    case 'savecolumns':
        $data = json_decode($postData, true);
        if (empty($data)) {
            return send_clmnseditor_result("Error: missing columns data", true);
        }

        $newColumns = get_new_columns($currentColumns, $data);
        save_columns_for_type($newColumns, $table, $campId);
        return send_clmnseditor_result("OK");
    case 'save':
        $data = json_decode($postData, true);

        if (!isset($data['name']) || !isset($data['columns']) || !isset($data['groupby'])) {
            return send_clmnseditor_result("Error: invalid table configuration", true);
        }

 
        break;
    case 'delete':
        $data = json_decode($postData, true);

        if (!isset($data['name'])) {
            return send_clmnseditor_result("Error: missing table name", true);
        }

        delete_stats_table($campId, $data['name']);
        break;
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

function get_columns_for_type(string $table, ?int $campId = null): array{
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
            $s = $db->get_campaign_settings($campId);
            foreach ($s['statistics']['tables'] as $t) {
                if ($t['name'] === $table) {
                    return $t['columns'];
                }
            }

            $errMsg = "Table $table not found in campaign settings";
            add_log('error', $errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
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
            $s = $db->get_campaign_settings($campId);
            foreach ($s['statistics']['tables'] as &$t) {
                if ($t['name'] === $table) {
                    $t['columns'] = $columns;
                    return $db->save_campaign_settings($campId, $s);
                }
            }

            $errMsg = "Table $table not found in campaign settings";
            add_log('error', $errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
}

function save_stats_table(int $campId, array $tableConfig) {
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        return send_clmnseditor_result("Error: campaign not found", true);
    }
    $campaign = new Campaign($campId, $s);

    $settings = $campaign->getStatisticsSettings();
    if (!$settings) {
        $settings = new StatisticsSettings();
    }

    // Find if table with this name already exists
    $existingTableIndex = -1;
    foreach ($settings->tables as $index => $table) {
        if ($table->name === $tableConfig['name']) {
            $existingTableIndex = $index;
            break;
        }
    }

    // Create new table object
    $table = new StatisticsTable($tableConfig['name'],$tableConfig['columns'],$tableConfig['groupby']);

    // Update or add the table
    if ($existingTableIndex >= 0) {
        $settings->tables[$existingTableIndex] = $table;
    } else {
        $settings->tables[] = $table;
    }

    // Save settings
    $campaign->setStatisticsSettings($settings);
    $campaign->save();

    return send_clmnseditor_result("Table saved successfully");
}

function delete_stats_table($campId, $tableName) {
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        return send_clmnseditor_result("Error: campaign not found", true);
    }

    $campaign = new Campaign($campId, $s);
    $settings = $campaign->getStatisticsSettings();
    if (!$settings) {
        return send_clmnseditor_result("Error: no statistics settings found", true);
    }

    // Find and remove the table
    $found = false;
    foreach ($settings->tables as $index => $table) {
        if ($table->name === $tableName) {
            array_splice($settings->tables, $index, 1);
            $found = true;
            break;
        }
    }

    if (!$found) {
        return send_clmnseditor_result("Error: table not found", true);
    }

    // Save settings
    $campaign->setStatisticsSettings($settings);
    $campaign->save();

    return send_clmnseditor_result("Table deleted successfully");
}