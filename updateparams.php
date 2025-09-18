<?php
require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/db/db.php';

global $db;

$subid = $_GET['subid'] ?? '';
if ($subid === '') {
    http_response_code(400);
    $msg = "No subid provided in URL parameters";
    add_log("updateparams", $msg);
    echo $msg;
    exit;
}

try {
    // Look up the click by subid
    $click = $db->get_clicks_by_subid($subid, true);
    if (empty($click)) {
        http_response_code(404);
        $msg = "No click found for subid: $subid";
        add_log("updateparams", $msg);
        echo $msg;
        exit;
    }

    // Get all URL parameters except subid
    $urlParams = $_GET;
    unset($urlParams['subid']);

    if (empty($urlParams)) {
        http_response_code(200);
        $msg = "No parameters to update for subid: $subid";
        add_log("updateparams", $msg);
        echo $msg;
        exit;
    }

    // Get existing params or initialize empty array
    $existingParams = $click['params'] ?? [];

    // Update existing params and add new ones
    foreach ($urlParams as $key => $value) {
        $existingParams[$key] = $value;
    }

    // Update the database
    $updated = $db->update_click_params($click['id'], $existingParams);

    if ($updated) {
        http_response_code(200);
        $updatedKeys = array_keys($urlParams);
        $msg = "Successfully updated parameters for subid: $subid. Updated keys: " . implode(', ', $updatedKeys);
        add_log("updateparams", $msg);
        echo $msg;
    } else {
        http_response_code(500);
        $msg = "Failed to update parameters for subid: $subid";
        add_log("updateparams", $msg);
        echo $msg;
    }

} catch (Exception $e) {
    http_response_code(500);
    $msg = "Error updating parameters for subid $subid: " . $e->getMessage();
    add_log("updateparams", $msg);
    echo $msg;
}