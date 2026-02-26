<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/dates.php';

$campId = isset($_GET['campId']) ? (int)$_GET['campId'] : null;
$filter = $_GET['filter'] ?? 'allowed';

if ($filter === 'trafficback') {
    require_once __DIR__ . '/../db/db.php';
    global $db;
    $gs = $db->get_common_settings();
    $tz = $gs['statistics']['timezone'];
} else {
    if ($campId === null) {
        header('Content-Type: application/json');
        echo json_encode(['last_page' => 1, 'data' => []]);
        exit;
    }
    require_once __DIR__ . '/campinit.php';
    global $db, $c;
    $tz = $c->statistics->timezone;
}

$timeRange = Dates::get_time_range($tz);
$startDate = $timeRange[0];
$endDate = $timeRange[1];

$page = max(1, (int)($_GET['page'] ?? 1));
$size = max(1, min(5000, (int)($_GET['size'] ?? 500)));

$sortField = $_GET['sort'] ?? 'time';
$sortDir = $_GET['dir'] ?? 'desc';

// Read filters and columns from saved settings
$filters = [];
$paramColumns = [];
$filterKeyMap = ['allowed' => 'allowedFilters', 'single' => 'allowedFilters', 'blocked' => 'blockedFilters', 'leads' => 'leadsFilters', 'trafficback' => 'trafficBackFilters'];
$filterKey = $filterKeyMap[$filter] ?? 'allowedFilters';

if ($filter === 'trafficback') {
    $filters = $gs['statistics'][$filterKey] ?? [];
    $tableColumns = $gs['statistics']['trafficBack'] ?? [];
} else {
    $s = $db->get_campaign_settings($campId);
    $filters = $s['statistics'][$filterKey] ?? [];
    $columnKey = ($filter === 'leads') ? 'leads' : (($filter === 'blocked') ? 'blocked' : 'allowed');
    $tableColumns = $s['statistics'][$columnKey] ?? [];
}

// Extract param.* column keys for extraction
foreach ($tableColumns as $col) {
    $f = is_array($col) ? ($col['field'] ?? '') : $col;
    if (str_starts_with($f, 'param.')) {
        $paramColumns[] = substr($f, 6);
    }
}

// Handle 'single' filter (subid lookup) — not paginated, always small
if ($filter === 'single') {
    $subid = $_GET['subid'] ?? '';
    $dataset = $db->get_clicks_by_subid($subid);
    header('Content-Type: application/json');
    echo json_encode(['last_page' => 1, 'data' => is_array($dataset) && isset($dataset[0]) ? $dataset : [$dataset]]);
    exit;
}

$result = $db->get_clicks_paginated($filter, $startDate, $endDate, $campId, $page, $size, $sortField, $sortDir, $filters, $paramColumns);

header('Content-Type: application/json');
echo json_encode($result);
