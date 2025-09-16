<?php
require_once __DIR__.'/logging.php';
require_once __DIR__.'/db/db.php';

// Allow access only for user-agent that contains YellowCloaker
if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'YellowCloaker') === false) {
    add_log('warning','Attempt to access API with invalid user-agent',true);
    http_response_code(404);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    add_log('warning','Not a POST request',true);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (!isset($data['api_key']) || empty($data['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

global $db;
$campaign = $db->get_campaign_by_apikey($data['api_key']);
if (!$campaign) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Include required files
    require_once __DIR__ . '/../tds.php';
    require_once __DIR__ . '/../actions.php';
    require_once __DIR__ . '/../cookies.php';
    require_once __DIR__ . '/../debug.php';
    
    // Simulate the request environment from the client data
    $_SERVER['HTTP_USER_AGENT'] = $data['user_agent'] ?? '';
    $_SERVER['HTTP_REFERER'] = $data['referer'] ?? '';
    $_SERVER['REQUEST_URI'] = $data['url'] ?? '/';
    $_SERVER['QUERY_STRING'] = $data['query_string'] ?? '';
    $_SERVER['HTTP_HOST'] = $data['host'] ?? $_SERVER['HTTP_HOST'];
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $data['accept_language'] ?? '';
    $_SERVER['REMOTE_ADDR'] = $data['ip'] ?? $_SERVER['REMOTE_ADDR'];
    
    // Set GET parameters if provided
    if (isset($data['get_params']) && is_array($data['get_params'])) {
        $_GET = array_merge($_GET, $data['get_params']);
    }
    
    // Prepare prefill data for TDS
    $prefill = [];
    if (isset($data['query_string']) && !empty($data['query_string'])) {
        $prefill['tds_qs'] = $data['url'] . '?' . $data['query_string'];
    } else {
        $prefill['tds_qs'] = $data['url'] ?? '/';
    }
    $prefill['tds_ref'] = $data['referer'] ?? '';
    
    // Get the cloaker action
    $action = Tds::getAction();
    
    // Convert action to JSON response
    $response = [
        'success' => true,
        'click_type' => $action->click_type,
        'action' => convertActionType($action->action),
        'content' => $action->value,
        'redirect_type' => $action->redirect_type ?? 302
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("YellowCloaker API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'success' => false,
        'action' => 'white'
    ]);
}

/**
 * Convert internal action types to client-friendly action types
 */
function convertActionType($internal_action) {
    switch ($internal_action) {
        case 'html':
            return 'black_html';
        case 'redirect':
            return 'black_redirect';
        case 'error':
            return 'white';
        default:
            return 'white';
    }
}
