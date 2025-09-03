<?php
//This file must be included if you want to connect the cloaker using Javascript.
//This works good for any website builders or GitHub for example.
//Use the following code: <script src="https://your.domain/js/index.php"></script>
//If the user passes the verification, the action you specified for the JS connection in campaign settings
//will be performed: 
//1.redirect 
//2.content substitution 
//3.show iframe

//fix for Apache Multiviews and/or PHP Development Server
if ($_SERVER['SCRIPT_NAME'] !== $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit("Not Found");
}

require_once __DIR__.'/../tds.php';
require_once __DIR__.'/../actions.php';

if (isset($_GET['subid']))
    $action = Tds::processJsCheck();
else
    $action = Tds::getJsAction();
$action->perform();