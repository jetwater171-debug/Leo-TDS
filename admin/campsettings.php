<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/../paths.php';
global $c;
?>
<!doctype html>
<html lang="en">
<?php include __DIR__.'/head.php' ?>
<link rel="stylesheet" href="<?=get_cloaker_path()?>css/campsettings.css?v=<?=filemtime(__DIR__.'/css/campsettings.css')?>">

<body>
    <?php include __DIR__.'/header.php' ?>
    <div class="all-content-wrapper">
        <div class="camp-layout">
            <nav class="camp-sidebar">
                <ul>
                    <li><a href="#sec-domains" class="active">Domains</a></li>
                    <li><a href="#sec-safepage">Safe Page</a></li>
                    <li><a href="#sec-flows">Flows</a></li>
                    <?php foreach ($c->black->flows as $fi => $flow) { ?>
                    <li class="flow-nav-item" data-flow-index="<?= $fi ?>"><a href="#sec-flow-<?= $fi ?>">&nbsp;&nbsp;<?= htmlspecialchars($flow->name) ?></a></li>
                    <?php } ?>
                    <li><a href="#sec-scripts">Scripts</a></li>
                    <li><a href="#sec-statistics">Statistics</a></li>
                    <li><a href="#sec-postbacks">Postbacks</a></li>
                    <li><a href="#sec-api">API</a></li>
                </ul>
            </nav>
            <div class="camp-content">
        <form id="campsettings">
            <section id="sec-domains" class="camp-section active">
            <div class="form-group-inner">
            <div class="row">
                <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                <label class="login2 pull-left pull-left-pro">
                    <img src="img/info.ico" title="Add all of the campaign's domains WITHOUT HTTP(S)! You can use *.xxx.com to match ALL subdomains."/> Domains list
                </label>
                </div>
            </div>
            </div>
 
            <div id="domains_container">
                <?php for ($i = 0; $i < count($c->domains); $i++) {
                        $dn = $c->domains[$i];
                ?>
                <div class="form-group-inner domains">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Domain:</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="domain.com" value="<?=$dn?>" name="domains[<?= $i ?>]" />
                            </div>
                        </div>
                        <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                            <a href="javascript:void(0)" class="remove-domain-item btn btn-danger">✕ Delete</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <a id="add-domain-item" class="btn btn-primary" href="javascript:;">+ Add Domain</a>
            </section>

            <section id="sec-safepage" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title">Method</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Choose
                            method:</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'folder' ? 'checked' : '' ?> value="folder" name="white.action" onclick="(document.getElementById('b_2').style.display = 'block'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Local safe page from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'block'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'none')" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'curl' ? 'checked' : '' ?> value="curl" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'block'); (document.getElementById('b_5').style.display = 'none')" />
                                            Load a website using CURL
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->action === 'error' ? 'checked' : '' ?> value="error" name="white.action" onclick="(document.getElementById('b_2').style.display = 'none'); (document.getElementById('b_3').style.display = 'none'); (document.getElementById('b_4').style.display = 'none'); (document.getElementById('b_5').style.display = 'block')" />
                                            Return HTTP-code <small>(for example,
                                                404 for NotFound or 200 for
                                                OK)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_2" style="display:<?= $c->white->action === 'folder' ? 'block' : 'none' ?>;">
                <div id="white_folder_container">
                    <?php for ($i = 0; $i < count($c->white->folderNames); $i++) {
                            $fn = $c->white->folderNames[$i];
                    ?>
                    <div class="form-group-inner white-folder-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Safe page folder:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="white1" value="<?=$fn?>" name="white.folders[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-white-folder-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-white-folder-item" class="btn btn-primary" href="javascript:;">+ Add Safe Page Folder</a>
            </div>
            <div id="b_3" style="display:<?= ($c->white->action === 'redirect' ? 'block' : 'none') ?>;">

                <div id="redirect_container">
                    <?php for ($i = 0; $i < count($c->white->redirectUrls); $i++) {
                            $ru = $c->white->redirectUrls[$i];
                    ?>
                    <div class="form-group-inner redirect-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Redirect address:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://ya.ru" value="<?=$ru?>" name="white.redirect.urls[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-redirect-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-redirect-item" class="btn btn-primary" href="javascript:;">+ Add Redirect</a>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Choose
                                Redirect HTTP-code:</label>
                        </div>
                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                            <div class="bt-df-checkbox pull-left">

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 301 ? 'checked' : '' ?> value="301" name="white.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 302 ? 'checked' : '' ?> value="302" name="white.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 303 ? 'checked' : '' ?> value="303" name="white.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->white->redirectType === 307 ? 'checked' : '' ?> value="307" name="white.redirect.type" />
                                                307
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_4" style="display:<?= $c->white->action === 'curl' ? 'block' : 'none' ?>;">
                <div id="curl_container">
                    <?php for ($i = 0; $i < count($c->white->curlUrls); $i++) {
                            $cu = $c->white->curlUrls[$i];
                    ?>
                    <div class="form-group-inner curl-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Curl address:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://ya.ru" value="<?=$cu?>" name="white.curls[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-curl-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-curl-item" class="btn btn-primary" href="javascript:;">+ Add Curl</a>
            </div>
            <div id="b_5" style="display:<?= $c->white->action === 'error' ? 'block' : 'none' ?>;">
                <div id="errorcodes_container">
                    <?php for ($i = 0; $i < count($c->white->errorCodes); $i++) {
                            $ec = $c->white->errorCodes[$i];
                    ?>
                    <div class="form-group-inner errorcode-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">HTTP code:</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="404" value="<?=$ec?>" name="white.errorcodes[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-errorcode-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-errorcode-item" class="btn btn-primary" href="javascript:;">+ Add HTTP Code</a>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Settings</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">

                            <img src="img/info.ico" title="Allowed methods are: folder, redirect, curl, error" />
                            Show
                            individual
                            domain-specific safe page?
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->domainFilterEnabled === false ? 'checked' : '' ?> value="false" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->domainFilterEnabled === true ? 'checked' : '' ?> value="true" name="white.domainfilter.use" onclick="(document.getElementById('b_6').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="b_6" style="display:<?= $c->white->domainFilterEnabled === true ? 'block' : 'none' ?>;">
                <div id="domainspecific_container">
                    <?php for ($j = 0; $j < count($c->white->domainSpecific); $j++) { ?>
                    <div class="form-group-inner domain-specific-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro">Domain => Method:Action</label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="xxx.yyy.com" value="<?= $c->white->domainSpecific[$j]->name ?>" name="white.domainfilter.domains[<?= $j ?>][name]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <p>=></p>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="folder:white" value="<?= $c->white->domainSpecific[$j]->action ?>" name="white.domainfilter.domains[<?= $j ?>][action]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-domain-specific-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-domain-specific-item" class="btn btn-primary" href="javascript:;">+ Add Domain-Specific Safe Page</a>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="If JS filters are switched ON, then the user will be shown a safe page for a moment and only after all the checks are passed he'll be shown the money page." />
                            Use Javascript filters?
                            <small></small>
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->white->jsChecks->enabled === false ? 'checked="checked"' : '' ?> value="false" name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'none')" />
                                            No, don't use
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" value="true" <?= $c->white->jsChecks->enabled === true ? 'checked="checked"' : '' ?> name="white.jschecks.enabled" onclick="(document.getElementById('jscheckssettings').style.display = 'block')" />
                                            Yes, use
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="jscheckssettings" style="display:<?=$c->white->jsChecks->enabled === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">JS-Test
                                timeout (msec): </label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="10000" name="white.jschecks.timeout" value="<?= $c->white->jsChecks->timeout ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">What will
                                be tested? </label>
                        </div>
                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                            <div class="bt-df-checkbox pull-left">

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="pointerdown" <?= in_array('pointerdown', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Mouse click / Touch start  
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="keydown" <?= in_array('keydown', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Text typing
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="devicemotion" <?= in_array('devicemotion', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Device motion (Android only)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="deviceorientation" <?= in_array('deviceorientation', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Device orientation (Android
                                                only)
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="checkbox" name="white.jschecks.events[]" value="audiocontext" <?= in_array('audiocontext', $c->white->jsChecks->events) ? 'checked' : '' ?> />
                                                Audio engine existence
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input id="tzcheck" type="checkbox" name="white.jschecks.events[]" value="timezone" <?= in_array('timezone', $c->white->jsChecks->events) ? 'checked' : '' ?> onchange="(document.getElementById('jscheckstz').style.display = this.checked ? 'block' : 'none')" />
                                                Time zone
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="jscheckstz" class="form-group-inner" style="display:<?= in_array('timezone', $c->white->jsChecks->events) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Minimum
                                allowed timezone</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="-3" name="white.jschecks.timezone.min" value="<?= $c->white->jsChecks->tzMin ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Maximum
                                allowed timezone</label>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                            <div class="input-group custom-go-button">
                                <input type="text" class="form-control" placeholder="3" name="white.jschecks.timezone.max" value="<?= $c->white->jsChecks->tzMax ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Filters</span>
            <div class="form-group-inner">
                <p>
                Traffic matching these filters will be shown the <strong>safe page</strong>. Everyone else goes to the black flows.
                </p>
                <div class="row">
                    <div id="filtersbuilder"></div>
                </div>
            </div>
            </div>
            </section>

            <section id="sec-flows" class="camp-section">
            <div class="form-group-inner">
                <p>Flows are processed top-to-bottom. First flow whose filters match the visitor gets the traffic. Empty filters = catch-all.</p>
                <div id="flows-list">
                <?php foreach ($c->black->flows as $fi => $flow) { ?>
                    <div class="flow-list-row" data-flow-index="<?= $fi ?>">
                        <input type="text" class="form-control flow-name-label" value="<?= htmlspecialchars($flow->name) ?>" readonly style="display:inline-block;width:200px;cursor:default;" />
                        <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-move-up" title="Move Up">&uarr;</a>
                        <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-move-down" title="Move Down">&darr;</a>
                        <a href="javascript:void(0)" class="btn btn-danger btn-sm flow-delete" title="Delete">✕ Delete</a>
                    </div>
                <?php } ?>
                </div>
                <a id="add-flow-btn" class="btn btn-primary" href="javascript:void(0)" style="margin-top:15px;display:inline-block;">+ Add Flow</a>
            </div>
            <hr/>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="You can connect any website to the cloaker using &lt;script src='https://yourwebsite.com/js/index.php'&gt;&lt;/script&gt;" />
                            Javascript Connect Action:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12"><div class="i-checks pull-left"><label>
                                    <input type="radio" <?= $c->black->jsconnectAction === 'replace' ? 'checked' : '' ?> value="replace" name="black_jsconnect" /> Content replace
                                </label></div></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12"><div class="i-checks pull-left"><label>
                                    <input type="radio" <?= $c->black->jsconnectAction === 'iframe' ? 'checked' : '' ?> value="iframe" name="black_jsconnect" /> IFrame
                                </label></div></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12"><div class="i-checks pull-left"><label>
                                    <input type="radio" <?= $c->black->jsconnectAction === 'redirect' ? 'checked' : '' ?> value="redirect" name="black_jsconnect" /> Redirect
                                </label></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="If Yes then the user will always be shown the same content on every visit" />
                            Save user flow:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12"><div class="i-checks pull-left"><label>
                                    <input type="radio" <?= $c->saveUserFlow === false ? 'checked' : '' ?> value="false" name="saveuserflow" /> No
                                </label></div></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12"><div class="i-checks pull-left"><label>
                                    <input type="radio" <?= $c->saveUserFlow === true ? 'checked' : '' ?> value="true" name="saveuserflow" /> Yes
                                </label></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <?php foreach ($c->black->flows as $fi => $flow) { ?>
            <section id="sec-flow-<?= $fi ?>" class="camp-section flow-section" data-flow-index="<?= $fi ?>">
            <h5 class="flow-section-title"><?= htmlspecialchars($flow->name) ?></h5>

            <div class="flow-group">
            <span class="flow-group-title">Distribution</span>
            <div class="form-group-inner">
                <select class="form-select flow-dist" data-fi="<?= $fi ?>">
                    <option value="equal" <?= $flow->distribution === 'equal' ? 'selected' : '' ?>>Equal</option>
                    <option value="weighted" <?= $flow->distribution === 'weighted' ? 'selected' : '' ?>>Weighted</option>
                    <option value="thompson" <?= $flow->distribution === 'thompson' ? 'selected' : '' ?>>Thompson Sampling</option>
                </select>
            </div>
            <div class="flow-thompson-opts" id="flow-thompson-opts-<?= $fi ?>" style="display:<?= $flow->distribution === 'thompson' ? 'block' : 'none' ?>">
                <div class="form-group-inner">
                    <label class="login2 pull-left pull-left-pro">Optimize for:</label>
                    <div class="bt-df-checkbox pull-left">
                        <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                            <input type="radio" <?= $flow->optimize_for === 'Lead' ? 'checked' : '' ?> value="Lead" name="flow_<?= $fi ?>_optimize_for" class="flow-optimize-for" data-fi="<?= $fi ?>" /> Lead
                        </label></div></div></div>
                        <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                            <input type="radio" <?= $flow->optimize_for === 'Purchase' ? 'checked' : '' ?> value="Purchase" name="flow_<?= $fi ?>_optimize_for" class="flow-optimize-for" data-fi="<?= $fi ?>" /> Purchase
                        </label></div></div></div>
                    </div>
                </div>
                <div class="form-group-inner flow-optimize-mode-wrap" id="flow-optimize-mode-wrap-<?= $fi ?>" style="display:<?= $flow->hasPrelanding() ? 'block' : 'none' ?>">
                    <label class="login2 pull-left pull-left-pro">Optimize mode:</label>
                    <div class="bt-df-checkbox pull-left">
                        <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                            <input type="radio" <?= $flow->optimize_mode === 'funnels' ? 'checked' : '' ?> value="funnels" name="flow_<?= $fi ?>_optimize_mode" class="flow-optimize-mode" data-fi="<?= $fi ?>" /> Funnels (preland+land combos)
                        </label></div></div></div>
                        <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                            <input type="radio" <?= $flow->optimize_mode === 'separate' ? 'checked' : '' ?> value="separate" name="flow_<?= $fi ?>_optimize_mode" class="flow-optimize-mode" data-fi="<?= $fi ?>" /> Separate (independent)
                        </label></div></div></div>
                    </div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Flow Filters</span>
            <div class="form-group-inner">
                <div class="row">
                    <div id="flow-filters-<?= $fi ?>"></div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Prelanding method</span>
            <div class="form-group-inner">
                <div class="bt-df-checkbox pull-left">
                    <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                        <input type="radio" <?= $flow->preland->action === 'none' ? 'checked' : '' ?> value="none" name="flow_<?= $fi ?>_preland_action" class="flow-preland-action" data-fi="<?= $fi ?>" /> Don't use prelanding
                    </label></div></div></div>
                    <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                        <input type="radio" <?= $flow->preland->action === 'folder' ? 'checked' : '' ?> value="folder" name="flow_<?= $fi ?>_preland_action" class="flow-preland-action" data-fi="<?= $fi ?>" /> Local prelanding(s) from folder
                    </label></div></div></div>
                </div>
            </div>
            <div class="flow-preland-folders" id="flow-preland-folders-<?= $fi ?>" style="display:<?= $flow->preland->action === 'folder' ? 'block' : 'none' ?>">
                <div class="flow-preland-items" id="flow-preland-items-<?= $fi ?>">
                <?php foreach ($flow->preland->folderNames as $pi => $pf) { ?>
                    <div class="form-group-inner flow-path-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Prelanding folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control flow-preland-folder" value="<?= htmlspecialchars($pf) ?>" placeholder="preland1" /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-preland-weight" value="<?= $flow->preland->weights[$pi] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-preland">✕ Delete</a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-preland" data-fi="<?= $fi ?>">+ Add Prelanding</a>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">Landing method</span>
            <div class="form-group-inner">
                <div class="bt-df-checkbox pull-left">
                    <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                        <input type="radio" <?= $flow->land->action === 'folder' ? 'checked' : '' ?> value="folder" name="flow_<?= $fi ?>_land_action" class="flow-land-action" data-fi="<?= $fi ?>" /> Local landing(s) from folder
                    </label></div></div></div>
                    <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                        <input type="radio" <?= $flow->land->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="flow_<?= $fi ?>_land_action" class="flow-land-action" data-fi="<?= $fi ?>" /> Redirect(s)
                    </label></div></div></div>
                </div>
            </div>
            <div class="flow-land-folders" id="flow-land-folders-<?= $fi ?>" style="display:<?= $flow->land->action === 'folder' ? 'block' : 'none' ?>">
                <div class="flow-land-folder-items" id="flow-land-folder-items-<?= $fi ?>">
                <?php foreach ($flow->land->folderNames as $li => $lf) { ?>
                    <div class="form-group-inner flow-path-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Landing folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control flow-land-folder" value="<?= htmlspecialchars($lf) ?>" placeholder="land1" /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-land-weight" value="<?= $flow->land->weights[$li] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-folder">✕ Delete</a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-folder" data-fi="<?= $fi ?>">+ Add Landing Folder</a>
            </div>
            <div class="flow-land-redirects" id="flow-land-redirects-<?= $fi ?>" style="display:<?= $flow->land->action === 'redirect' ? 'block' : 'none' ?>">
                <div class="flow-land-redirect-items" id="flow-land-redirect-items-<?= $fi ?>">
                <?php foreach ($flow->land->redirectUrls as $ri => $ru) { ?>
                    <div class="form-group-inner flow-path-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>
                            <div class="col-lg-4"><input type="text" class="form-control flow-land-redirect" value="<?= htmlspecialchars($ru) ?>" placeholder="https://..." /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-land-weight" value="<?= $flow->land->weights[$ri] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-redirect">✕ Delete</a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-redirect" data-fi="<?= $fi ?>">+ Add Redirect</a>
                <div class="form-group-inner" style="margin-top:10px">
                    <label class="login2 pull-left pull-left-pro">Redirect type:</label>
                    <div class="bt-df-checkbox pull-left">
                        <?php foreach ([301,302,303,307] as $rt) { ?>
                        <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                            <input type="radio" <?= $flow->land->redirectType === $rt ? 'checked' : '' ?> value="<?= $rt ?>" name="flow_<?= $fi ?>_redirect_type" class="flow-redirect-type" /> <?= $rt ?>
                        </label></div></div></div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            </div>
            </section>
            <?php } ?>

            <section id="sec-scripts" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title">Backfix</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> 
                            <img src="img/info.ico" title="Backfix is a script that will prevent the user from going back from out site. Instead the user fill be shown another money page that you'll choose."/>
                            Should we use backfix?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->backfix === false ? 'checked' : '' ?> value="false" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->backfix ? 'checked' : '' ?> value="true" name="scripts.backfix.use" onclick="(document.getElementById('b_backfix').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_backfix" style="display:<?= $c->scripts->backfix? 'block' : 'none' ?>;">
                <div id="backfix_urls_container">
                    <?php
                    $bfCount = max(count($c->scripts->backfixUrls), 1);
                    for ($i = 0; $i < $bfCount; $i++) {
                            $bu = $c->scripts->backfixUrls[$i] ?? '';
                    ?>
                    <div class="form-group-inner backfix-url-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">Backfix URL:</label>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}" value="<?= $bu ?>" name="scripts.backfix.urls[<?= $i ?>]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-backfix-url-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-backfix-url-item" class="btn btn-primary" href="javascript:;">+ Add Backfix URL</a>
            </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Should we open landing in a new tab and
                            redirect prelanding page to another URL?</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replacePrelanding === false ? 'checked' : '' ?> value="false" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replacePrelanding === true ? 'checked' : '' ?> value="true" name="scripts.prelandingreplace.use" onclick="(document.getElementById('b_10').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_10" style="display:<?= $c->scripts->replacePrelanding === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Prelanding redirect URL:</label>
                        </div>
                        <div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.prelandingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->replacePrelandingAddress?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Should we open ThankYou page 
                        in a new tab and redirect landing page to another URL:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replaceLanding === false ? 'checked' : '' ?> value="false" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'none')" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->replaceLanding === true ? 'checked' : '' ?> value="true" name="scripts.landingreplace.use" onclick="(document.getElementById('b_1010').style.display = 'block')" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_1010" style="display:<?= $c->scripts->replaceLanding === true ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> Landing redirect URL:</label>
                        </div>
                        <div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.landingreplace.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->replaceLandingAddress ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Use lazy loading for images?
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->imagesLazyLoad === false ? 'checked' : '' ?> value="false" name="scripts.imageslazyload" />
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->scripts->imagesLazyLoad === true ? 'checked' : '' ?> value="true" name="scripts.imageslazyload" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <section id="sec-statistics" class="camp-section">
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> Time zone to show statistics:
                        </label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <?= select_timezone('statistics.timezone', $c->statistics->timezone); ?>
                    </div>
                </div>
            </div>
            </section>

            <section id="sec-postbacks" class="camp-section">
            <div class="flow-group">
            <span class="flow-group-title">Postback</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                        <img src="img/info.ico" title="Put it into your Affiliate Network's postback URL. Change macros names if needed. Subid, payout and status parameters are required. Currency is optional, will be USD if omitted." />
                        Your postback URL example:
                    </label>
                    </div>
                    <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
                        <div class="input-group custom-go-button">
                            <?php $cloakerRoot = dirname(get_cloaker_path()); ?>
                            <input type="text" readonly class="form-control" value="<?= $cloakerRoot ?>/postback.php?subid={sub1}&payout={payout}&currency=USD&status={status}"/>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            Here you need to write lead statuses in the format that
                            you get them from Affiliate Network's postback:
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Lead</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.lead" class="form-control" placeholder="Lead" value="<?= $c->postback->leadStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Purchase</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.purchase" class="form-control" placeholder="Purchase" value="<?= $c->postback->purchaseStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Reject</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.reject" class="form-control" placeholder="Reject" value="<?= $c->postback->rejectStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-2 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Trash</label>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" name="postback.events.trash" class="form-control" placeholder="Trash" value="<?= $c->postback->trashStatusName ?>" />
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div class="flow-group">
            <span class="flow-group-title">S2S</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
                        <label class="login2 pull-left pull-left-pro"> S2S-postbacks settings:</label>
                        <br />
                    </div>
                </div>

                <div id="s2s_container">
                    <?php 
                    for ($i = 0; $i < count($c->postback->s2sPostbacks); $i++) { 
                        $s2sUrl = $c->postback->s2sPostbacks[$i]->url;
                        $s2sMethod = $c->postback->s2sPostbacks[$i]->method;
                        $s2sEvents = $c->postback->s2sPostbacks[$i]->events;
                    ?>
                    <div class="form-group-inner s2s">
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro">
                                    <img src="img/info.ico" title="Inside the S2S-postback address you can use the following macros: {subid}, {prelanding}, {landing}, {px}, {domain}, {status}" />
                                    Address:
                                </label>
                                <br /><br />
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://s2s-postback.com" value="<?= $s2sUrl ?>" name="postback.s2s[<?= $i ?>][url]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a class="remove-s2s-item btn btn-danger">✕ Delete</a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro"> S2S-Postback send method:
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <select class="form-select" name="postback.s2s[<?= $i ?>][method]">
                                    <option value="GET" <?= ($s2sMethod === "GET" ? ' selected' : '') ?>> GET
                                    </option>
                                    <option value="POST" <?= ($s2sMethod === "POST" ? ' selected' : '') ?>> POST
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <label class="login2 pull-left pull-left-pro"> Events for which S2S-postback will be sent:
                                </label>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                                <br />
                                <br/>
                                <?php
                                $statuses = ['Lead','Purchase','Reject','Trash'];
                                foreach ($statuses as $status)
                                {?>
                                    <div class="form-check form-switch">
                                        <label for="<?=$status?><?=$i?>" class="form-check-label"><?=$status?></label>
                                        <input id="<?=$status?><?=$i?>" type="checkbox" class="form-check-input" name="postback.s2s[<?= $i ?>][events][]" value="<?=$status?>" <?= (in_array($status, $s2sEvents) ? ' checked' : '') ?> />
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-s2s-item" class="btn btn-primary">+ Add</a>
            </div>
            </div>
            </section>

            <section id="sec-api" class="camp-section">
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                        <img src="img/info.ico" title="API methods are described in docs" />
                        This campaign's API URL:
                    </label>
                    </div>
                    <div class="col-lg-7 col-md-7 col-sm-7 col-xs-12">
                        <div class="input-group custom-go-button">
                            <input type="text" readonly class="form-control" value="<?= $cloakerRoot ?>/phpconnect.php?apikey=<?= $c->apiKey ?>"/>
                        </div>
                    </div>
                </div>
            </div>

            </section>

            <div class="camp-save-bar">
                <button class="btn btn-lg btn-primary" type="submit">
                    <strong>Save settings</strong>
                </button>
            </div>
        </form>
            </div><!-- .camp-content -->
        </div><!-- .camp-layout -->
    </div><!-- .all-content-wrapper -->
    <!--cloneData-->
    <script src="js/cloneData.js"></script>
    <script>
        $('#add-domain-item').cloneData({
            mainContainerId: 'domains_container',
            cloneContainer: 'domains',
            removeButtonClass: 'remove-domain-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });
        
        $('#add-white-folder-item').cloneData({
            mainContainerId: 'white_folder_container',
            cloneContainer: 'white-folder-item',
            removeButtonClass: 'remove-white-folder-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });
        
        $('#add-redirect-item').cloneData({
            mainContainerId: 'redirect_container',
            cloneContainer: 'redirect-item',
            removeButtonClass: 'remove-redirect-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });
       
        $('#add-curl-item').cloneData({
            mainContainerId: 'curl_container',
            cloneContainer: 'curl-item',
            removeButtonClass: 'remove-curl-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-errorcode-item').cloneData({
            mainContainerId: 'errorcodes_container',
            cloneContainer: 'errorcode-item',
            removeButtonClass: 'remove-errorcode-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-domain-specific-item').cloneData({
            mainContainerId: 'domainspecific_container',
            cloneContainer: 'domain-specific-item',
            removeButtonClass: 'remove-domain-specific-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-backfix-url-item').cloneData({
            mainContainerId: 'backfix_urls_container',
            cloneContainer: 'backfix-url-item',
            removeButtonClass: 'remove-backfix-url-item',
            maxLimit: 10,
            minLimit: 0,
            removeConfirm: false
        });

        
        $('#add-sub-item').cloneData({
            mainContainerId: 'subs_container',
            cloneContainer: 'subs',
            removeButtonClass: 'remove-sub-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-stats-sub-item').cloneData({
            mainContainerId: 'stats_subs_container',
            cloneContainer: 'stats_subs',
            removeButtonClass: 'remove-stats-sub-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-s2s-item').cloneData({
            mainContainerId: 's2s_container',
            cloneContainer: 's2s',
            removeButtonClass: 'remove-s2s-item',
            maxLimit: 5,
            minLimit: 1,
            removeConfirm: false
        });
        
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("campsettings")?.addEventListener("submit", async (e) => {
                e.preventDefault();

                const urlParams = new URLSearchParams(window.location.search);
                const campId = urlParams.get('campId');
                if (campId === null) {
                    alert("No campaign ID found!");
                    return false;
                }

                let rules = $('#filtersbuilder').queryBuilder('getRules');
                let flowsJson = window.collectFlowsData ? window.collectFlowsData() : '[]';
                let formData = new FormData(document.getElementById("campsettings"));
                let filteredFormData = new FormData();
                for (let [key, value] of formData.entries()) {
                    if (!key.startsWith("filtersbuilder") && !key.startsWith("flow_")) {
                        filteredFormData.append(key, value);
                    }
                }
                filteredFormData.append("filters", JSON.stringify(rules));
                filteredFormData.append("flows", flowsJson);
                let settingsBody = new URLSearchParams(filteredFormData.entries()).toString();

                let res = await fetch(`campeditor.php?action=save&campId=${campId}`, {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: settingsBody
                });
                let js = await res.json();
                if (js.error)
                    alert(`An error occured: ${js.result}`);
                else
                    alert("Settings saved!");
                return false;
            });
        });
    </script>
    <script src="js/filters.js"></script>
    <script>
        var rules_basic = <?=json_encode($c->white->filters)?>;

        $('#filtersbuilder').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
            filters: tdsFilters,
            <?php
            if (!empty($c->white->filters)) {
                echo 'rules: rules_basic';
            }
            ?>
        });

        <?php foreach ($c->black->flows as $fi => $flow) { ?>
        var flow_rules_<?= $fi ?> = <?= json_encode($flow->filters) ?>;
        $('#flow-filters-<?= $fi ?>').queryBuilder({
            operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat(paramOperators),
            filters: tdsFilters,
            <?php if (!empty($flow->filters) && isset($flow->filters['rules'])) { ?>
            rules: flow_rules_<?= $fi ?>
            <?php } ?>
        });
        <?php } ?>

    </script>
    <script src="js/flows.js"></script>
    <script src="js/campsettings-nav.js"></script>
</body>

<?php
function select_timezone($selectname, $selected = '')
{
    $zones = timezone_identifiers_list();
    $select = "<select name='" . $selectname . "' class='form-select'>";
    foreach ($zones as $zone) {
        $tz = new DateTimeZone($zone);
        $offset = $tz->getOffset(new DateTime) / 3600;
        $select .= '<option value="' . $zone . '"';
        $select .= ($zone == $selected ? ' selected' : '');
        $select .= '>' . $zone . ' ' . $offset . '</option>';
    }
    $select .= '</select>';
    return $select;
}

?>

</html>
