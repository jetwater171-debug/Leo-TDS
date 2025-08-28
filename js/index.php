<?php
//This file must be included if you want to connect the cloaker using Javascript.
//This works good for any website builders or GitHub for example.
//Use the following code: <script src="https://your.domain/js/index.php"></script>
//If the user passes the verification, the action you specified for the JS connection in campaign settings
//will be performed: 
//1.redirect 
//2.content substitution 
//3.show iframe
require_once __DIR__.'/obfuscator.php';
require_once __DIR__.'/../db/db.php';
require_once __DIR__.'/../debug.php';
require_once __DIR__.'/../settings.php';
require_once __DIR__.'/../requestfunc.php';
require_once __DIR__.'/../paths.php';
require_once __DIR__.'/../campaign.php';
require_once __DIR__.'/../redirect.php';
require_once __DIR__.'/../core.php';
require_once __DIR__.'/../tds.php';
require_once __DIR__.'/../actions.php';

$action = new JSAction(Tds::getAction());
$action->perform();