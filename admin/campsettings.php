<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/../paths.php';
global $c;
?>
<!doctype html>
<html lang="en">
<?php include __DIR__.'/head.php' ?>

<body>
    <?php include __DIR__.'/header.php' ?>
    < class="all-content-wrapper">

        <form id="campsettings" style="padding:35px;background-color:#1D2A48;">
            <h4>#0 Domains</h4>

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
                            <a href="javascript:void(0)" class="remove-domain-item btn btn-primary">Delete</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <a id="add-domain-item" class="btn btn-primary" href="javascript:;">Add Domain</a>
            <hr />
            <h4>#1 Safe page settings</h4>
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
                                <a href="javascript:void(0)" class="remove-white-folder-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-white-folder-item" class="btn btn-primary" href="javascript:;">Add Safe Page Folder</a>
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
                                <a href="javascript:void(0)" class="remove-redirect-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-redirect-item" class="btn btn-primary" href="javascript:;">Add Redirect</a>

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
                                <a href="javascript:void(0)" class="remove-curl-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-curl-item" class="btn btn-primary" href="javascript:;">Add Curl</a>
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
                                <a href="javascript:void(0)" class="remove-errorcode-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-errorcode-item" class="btn btn-primary" href="javascript:;">Add HTTP Code</a>
            </div>

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
                                <a href="javascript:void(0)" class="remove-domain-specific-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-domain-specific-item" class="btn btn-primary" href="javascript:;">Add Domain-Specific Safe Page</a>
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
            <br />
            <hr />
            <h4>#2 Money page settings</h4>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Choose
                            prelanding(s) loading method: </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->preland->action === 'none' ? 'checked' : '' ?> value="none" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'none')" />
                                            Don't use prelanding
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->preland->action === 'folder' ? 'checked' : '' ?> value="folder" name="black.prelanding.action" onclick="(document.getElementById('b_8').style.display = 'block')" />
                                            Local prelanding(s) from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div id="b_8" style="display:<?= $c->black->preland->action === 'folder' ? 'block' : 'none' ?>;">
                <div id="prelandings_container">
                    <?php for ($j = 0; $j < count($c->black->preland->folderNames); $j++) { ?>
                    <div class="form-group-inner prelanding-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">
                                    Prelanding folder:
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                <div class="input-group custom-go-button">
                                    <input type="text" class="form-control" placeholder="preland1" name="black.prelanding.folders[<?= $j ?>]" value="<?= $c->black->preland->folderNames[$j] ?>" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-prelanding-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-prelanding-item" class="btn btn-primary" href="javascript:;">Add Prelanding</a>
            </div>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">Choose landing(s)
                            loading method:</label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->land->action === 'folder' ? 'checked' : '' ?> value="folder" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'none'); (document.getElementById('b_landings_folder').style.display = 'block')" />
                                            Local landing(s) from folder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->land->action === 'redirect' ? 'checked' : '' ?> value="redirect" name="black.landing.action" onclick="(document.getElementById('b_landings_redirect').style.display = 'block'); (document.getElementById('b_landings_folder').style.display = 'none')" />
                                            Redirect(s)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="b_landings_folder" style="display:<?= $c->black->land->action === 'folder' ? 'block' : 'none' ?>;">
                <div id="landing_folders_container">
                        <?php for ($j = 0; $j < count($c->black->land->folderNames); $j++) { 
                                $lfn = $c->black->land->folderNames[$j];
                        ?>
                        <div class="form-group-inner landing-folder-item">
                            <div class="row">
                                <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                    <label class="login2 pull-left pull-left-pro">
                                        Landing folder:
                                    </label>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                    <div class="input-group custom-go-button">
                                        <input type="text" class="form-control" placeholder="land1" name="black.landing.folders[<?= $j ?>]" value="<?= $lfn ?>" />
                                    </div>
                                </div>
                                <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                    <a href="javascript:void(0)" class="remove-landing-folder-item btn btn-primary">Delete</a>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                </div>
                <a id="add-landing-folder-item" class="btn btn-primary" href="javascript:;">Add Landing</a>
            </div>
            <div id="b_landings_redirect" style="display:<?= $c->black->land->action === 'redirect' ? 'block' : 'none' ?>;">
                <div id="landing_redirects_container">
                    <?php for ($j = 0; $j < count($c->black->land->redirectUrls); $j++) { 
                            $lfn = $c->black->land->redirectUrls[$j];
                    ?>
                    <div class="form-group-inner landing-redirect-item">
                        <div class="row">
                            <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                                <label class="login2 pull-left pull-left-pro">
                                    Landing folder:
                                </label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
                                <div class="input-group custom-go-button">
                                    <input type="text" class="form-control" placeholder="land1" name="black.landing.redirect.urls[<?= $j ?>]" value="<?= $lfn ?>" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a href="javascript:void(0)" class="remove-landing-redirect-item btn btn-primary">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-landing-redirect-item" class="btn btn-primary" href="javascript:;">Add Redirect</a>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect
                                type:</label>
                        </div>
                        <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                            <div class="bt-df-checkbox pull-left">

                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 301 ? 'checked' : '' ?> value="301" name="black.landing.redirect.type" />
                                                301
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 302 ? 'checked' : '' ?> value="302" name="black.landing.redirect.type" />
                                                302
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 303 ? 'checked' : '' ?> value="303" name="black.landing.redirect.type" />
                                                303
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="i-checks pull-left">
                                            <label>
                                                <input type="radio" <?= $c->black->land->redirectType === 307 ? 'checked' : '' ?> value="307" name="black.landing.redirect.type" />
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
            <?php
            $use_js_redirect = ($c->black->preland->action === 'none' && $c->black->land->action === 'redirect');
            ?>
            <div class="form-group-inner" id="black_jsconnect" style="display:<?=$use_js_redirect?'none':'block'?>">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="You can connect any website to the cloaker using <script src='https://yourwebsite.com/js/index.php'></script>" />
                            Javascript Connect Action: 
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">

                            <?php if ($use_js_redirect) { ?>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" checked value="redirect" name="black.jsconnect" />
                                            Redirect
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } else { ?>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->jsconnectAction === 'replace' ? 'checked' : '' ?> value="replace" name="black.jsconnect" />
                                            Content replace
                                            <img src="img/info.ico" title="The original website's content will be totally replaced by the money page html" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->black->jsconnectAction === 'iframe' ? 'checked' : '' ?> value="iframe" name="black.jsconnect" />
                                            IFrame
                                            <img src="img/info.ico" title="Money page will be displayed using an iframe element, shown over the original page" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
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
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->saveUserFlow === false ? 'checked' : '' ?> value="false" name="saveuserflow" /> No
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="i-checks pull-left">
                                        <label>
                                            <input type="radio" <?= $c->saveUserFlow === true ? 'checked' : '' ?> value="true" name="saveuserflow" />
                                            Yes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <hr />
            <h4>#4 Cloaker filters</h4>
            <div class="form-group-inner">
                <p>
                Here you define: which traffic will be ALLOWED to see the money pages.
                </p>
                <div class="row">
                    <div id="filtersbuilder"></div>
                </div>
            </div>
            <hr />
            <h4>#5 Additional scripts settings</h4>
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
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> 
                                <img src="img/info.ico" title="When the user clicks Back the first time he'll be shown this url." />
                                Backfix First URL:</label>
                        </div>
                        <div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.backfix.url" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->backfixAddress?>" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
                            <label class="login2 pull-left pull-left-pro"> 
                                <img src="img/info.ico" title="When the user clicks Back the second time he'll be shown this url." />
                                Backfix Second URL:</label>
                        </div>
                        <div class="col-lg-8 col-md-8 col-sm-8 col-xs-8">
                            <div class="input-group custom-go-button">
                                <input type="text" name="scripts.backfix.second" class="form-control" placeholder="http://ya.ru?pixel={px}&subid={subid}&prelanding={prelanding}" value="<?= $c->scripts->backfixSecondAddress?>" />
                            </div>
                        </div>
                    </div>
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
            <br />
            <hr />
            <h4>#6 Campaign statistics settings</h4>
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
            <br />
            <hr />
            <h4>#7 Postbacks settings</h4>
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
                            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="https://s2s-postback.com" value="<?= $s2sUrl ?>" name="postback.s2s[<?= $i ?>][url]" />
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-1">
                                <a class="remove-s2s-item btn btn-primary">Delete</a>
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
                <a id="add-s2s-item" class="btn btn-primary">Add</a>
                <hr />
            </div>
            <h4>#8 Campaign's API</h4>
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

            <div class="form-group-inner">
                <div class="login-btn-inner">
                    <div class="row">
                        <div class="col-lg-3"></div>
                        <div class="col-lg-9">
                            <div class="login-horizental cancel-wp pull-left">
                                <button class="btn btn-lg btn-primary" type="submit">
                                    <strong>Save
                                        settings</strong>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
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

        $('#add-prelanding-item').cloneData({
            mainContainerId: 'prelandings_container',
            cloneContainer: 'prelanding-item',
            removeButtonClass: 'remove-prelanding-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-landing-folder-item').cloneData({
            mainContainerId: 'landing_folders_container',
            cloneContainer: 'landing-folder-item',
            removeButtonClass: 'remove-landing-folder-item',
            maxLimit: 10,
            minLimit: 1,
            removeConfirm: false
        });

        $('#add-landing-redirect-item').cloneData({
            mainContainerId: 'landing_redirects_container',
            cloneContainer: 'landing-redirect-item',
            removeButtonClass: 'remove-landing-redirect-item',
            maxLimit: 10,
            minLimit: 1,
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
                let formData = new FormData(document.getElementById("campsettings"));
                let filteredFormData = new FormData();
                for (let [key, value] of formData.entries()) {
                    if (!key.startsWith("filtersbuilder")) {
                        filteredFormData.append(key, value);
                    }
                }
                filteredFormData.append("filters", JSON.stringify(rules));
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
        var rules_basic = <?=json_encode($c->filters)?>;

        $('#filtersbuilder').queryBuilder({
            filters: tdsFilters,
            <?php
            if (!empty($c->filters)) {
                echo 'rules: rules_basic';
            }
            ?>
        });

    </script>
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
