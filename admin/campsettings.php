<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../abtest.php';
global $c, $db, $campId;
?>
<!doctype html>
<html lang="en">
<?php include __DIR__.'/head.php' ?>
<link rel="stylesheet" href="<?=get_cloaker_path()?>css/campsettings.css?v=<?=filemtime(__DIR__.'/css/campsettings.css')?>">
<link rel="stylesheet" href="<?=get_cloaker_path()?>css/fileeditor.css?v=<?=filemtime(__DIR__.'/css/fileeditor.css')?>">

<body>
    <?php include __DIR__.'/header.php' ?>
    <div class="all-content-wrapper">
        <div class="camp-layout">
            <nav class="camp-sidebar">
                <ul>
                    <li><a href="#sec-domains" class="active">Domains</a></li>
                    <li><a href="#sec-safepage">Safe Page</a></li>
                    <?php if ($c->white->domainFilterEnabled) foreach ($c->domains as $di => $domainName) { ?>
                    <li class="dws-nav-item" data-domain="<?= htmlspecialchars($domainName) ?>"><a href="#sec-dws-<?= $di ?>">&nbsp;&nbsp;<?= htmlspecialchars($domainName) ?></a></li>
                    <?php } ?>
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
                <?php foreach ($c->domains as $dn) { ?>
                <div class="form-group-inner domain-item">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Domain:</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control domain-name" value="<?= htmlspecialchars($dn) ?>" placeholder="domain.com" readonly /></div>
                        <div class="col-lg-1 domain-status-col"><i class="bi bi-hourglass-split domain-status" style="color:#94a3b8" title="Checking..."></i></div>
                        <div class="col-lg-2"><a href="javascript:void(0)" class="btn btn-danger btn-sm remove-domain-item" title="Delete"><i class="bi bi-trash"></i></a></div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <a id="add-domain-item" class="btn btn-primary btn-sm" href="javascript:void(0)"><i class="bi bi-plus-circle"></i> Add Domain</a>
            </section>

            <section id="sec-safepage" class="camp-section">
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

            <div class="flow-group">
            <span class="flow-group-title">Scope</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">White page mode:</label></div>
                    <div class="col-lg-9">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= !$c->white->domainFilterEnabled ? 'checked' : '' ?> value="false" name="white.domainfilter.use" class="white-scope-radio" /> Global (same white page for all domains)
                            </label></div></div></div>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $c->white->domainFilterEnabled ? 'checked' : '' ?> value="true" name="white.domainfilter.use" class="white-scope-radio" /> Domain-Specific (each domain gets its own config)
                            </label></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
            </div>

            <div id="global-white-config" style="display:<?= !$c->white->domainFilterEnabled ? 'block' : 'none' ?>">
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
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Safe page folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control white-folder-name" value="<?= htmlspecialchars($fn) ?>" placeholder="white1" readonly /></div>
                            <div class="col-lg-4"><div class="btn-group btn-group-sm"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($c->white->getLoadMode($fn)) ?>" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi <?= match($c->white->getLoadMode($fn)) { 'rewrite' => 'bi-arrow-repeat', 'direct' => 'bi-hdd-network', default => 'bi-house-door' } ?>"></i></a><a href="javascript:void(0)" class="btn btn-warning white-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a><a href="javascript:void(0)" class="btn btn-danger remove-white-folder-item" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm white-add-existing"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info btn-sm white-upload-zip"><i class="bi bi-upload"></i> Upload ZIP</a>
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
                                <a href="javascript:void(0)" class="remove-redirect-item btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-redirect-item" class="btn btn-primary" href="javascript:;">+ Add Redirect</a>

                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <label class="login2 pull-left pull-left-pro">Redirect type:</label>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                            <select class="form-select" name="white.redirect.type">
                                <?php foreach ([301,302,303,307] as $rt) { ?>
                                <option value="<?= $rt ?>" <?= $c->white->redirectType === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                <?php } ?>
                            </select>
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
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Curl address:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control white-curl-url" placeholder="https://ya.ru" value="<?=$cu?>" name="white.curls[<?= $i ?>]" /></div>
                            <div class="col-lg-2"><div class="btn-group btn-group-sm"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($c->white->getLoadMode($cu)) ?>" data-modes="rewrite,direct" title="Loading mode"><i class="bi <?= $c->white->getLoadMode($cu) === 'direct' ? 'bi-hdd-network' : 'bi-arrow-repeat' ?>"></i></a><a href="javascript:void(0)" class="btn btn-danger remove-curl-item" title="Delete"><i class="bi bi-trash"></i></a></div></div>
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
                                <a href="javascript:void(0)" class="remove-errorcode-item btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <a id="add-errorcode-item" class="btn btn-primary" href="javascript:;">+ Add HTTP Code</a>
            </div>
            </div>

            </div><!-- /#global-white-config -->

            </section>

            <?php
            // Build a lookup map: domain => DomainWhiteSettings
            $dwsMap = [];
            foreach ($c->white->domainSpecific as $dws) {
                $dwsMap[$dws->domain] = $dws;
            }
            foreach ($c->domains as $di => $domainName) {
                $dws = $dwsMap[$domainName] ?? null;
                $dwAction = $dws ? $dws->action : 'folder';
            ?>
            <section id="sec-dws-<?= $di ?>" class="camp-section dws-section" data-domain="<?= htmlspecialchars($domainName) ?>">
            <h5><?= htmlspecialchars($domainName) ?> — Safe Page</h5>

            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Method:</label></div>
                    <div class="col-lg-9">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $dwAction === 'folder' ? 'checked' : '' ?> value="folder" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Local folder
                            </label></div></div></div>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $dwAction === 'redirect' ? 'checked' : '' ?> value="redirect" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> Redirect
                            </label></div></div></div>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $dwAction === 'curl' ? 'checked' : '' ?> value="curl" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> CURL
                            </label></div></div></div>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $dwAction === 'error' ? 'checked' : '' ?> value="error" name="dws_action_<?= $di ?>" class="dws-action" data-di="<?= $di ?>" /> HTTP Code
                            </label></div></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dws-folder-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'folder' ? 'block' : 'none' ?>">
                <div class="dws-folder-items">
                <?php if ($dws) foreach ($dws->folderNames as $fn) { ?>
                    <div class="form-group-inner dws-folder-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Folder:</label></div>
                            <div class="col-lg-3"><input type="text" class="form-control dws-folder-name" value="<?= htmlspecialchars($fn) ?>" readonly /></div>
                            <div class="col-lg-4"><div class="btn-group btn-group-sm">
                                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($dws->getLoadMode($fn)) ?>" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi <?= match($dws->getLoadMode($fn)) { 'rewrite' => 'bi-arrow-repeat', 'direct' => 'bi-hdd-network', default => 'bi-house-door' } ?>"></i></a>
                                <a href="javascript:void(0)" class="btn btn-warning dws-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>
                                <a href="javascript:void(0)" class="btn btn-danger dws-remove-folder" title="Delete"><i class="bi bi-trash"></i></a>
                            </div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm dws-add-existing" data-di="<?= $di ?>"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info btn-sm dws-upload-zip" data-di="<?= $di ?>"><i class="bi bi-upload"></i> Upload ZIP</a>
            </div>

            <div class="dws-redirect-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'redirect' ? 'block' : 'none' ?>">
                <div class="dws-redirect-items">
                <?php if ($dws) foreach ($dws->redirectUrls as $ru) { ?>
                    <div class="form-group-inner dws-redirect-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>
                            <div class="col-lg-5"><input type="text" class="form-control dws-redirect-url" value="<?= htmlspecialchars($ru) ?>" placeholder="https://example.com" /></div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm dws-remove-redirect"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm dws-add-redirect" data-di="<?= $di ?>">+ Add URL</a>
                <div class="form-group-inner" style="margin-top:10px">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                        <div class="col-lg-3"><select class="form-select dws-redirect-type">
                            <?php $dwRt = $dws ? $dws->redirectType : 302; foreach ([301,302,303,307] as $rt) { ?>
                            <option value="<?= $rt ?>" <?= $dwRt === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                            <?php } ?>
                        </select></div>
                    </div>
                </div>
            </div>

            <div class="dws-curl-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'curl' ? 'block' : 'none' ?>">
                <div class="dws-curl-items">
                <?php if ($dws) foreach ($dws->curlUrls as $cu) { ?>
                    <div class="form-group-inner dws-curl-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">CURL URL:</label></div>
                            <div class="col-lg-5"><input type="text" class="form-control dws-curl-url" value="<?= htmlspecialchars($cu) ?>" placeholder="https://example.com" /></div>
                            <div class="col-lg-2"><div class="btn-group btn-group-sm">
                                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="<?= htmlspecialchars($dws->getLoadMode($cu)) ?>" data-modes="rewrite,direct" title="Loading mode"><i class="bi <?= $dws->getLoadMode($cu) === 'direct' ? 'bi-hdd-network' : 'bi-arrow-repeat' ?>"></i></a>
                                <a href="javascript:void(0)" class="btn btn-danger dws-remove-curl"><i class="bi bi-trash"></i></a>
                            </div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm dws-add-curl" data-di="<?= $di ?>">+ Add CURL</a>
            </div>

            <div class="dws-error-block" data-di="<?= $di ?>" style="display:<?= $dwAction === 'error' ? 'block' : 'none' ?>">
                <div class="dws-error-items">
                <?php if ($dws) foreach ($dws->errorCodes as $ec) { ?>
                    <div class="form-group-inner dws-error-item">
                        <div class="row">
                            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">HTTP Code:</label></div>
                            <div class="col-lg-2"><input type="text" class="form-control dws-error-code" value="<?= htmlspecialchars($ec) ?>" placeholder="404" /></div>
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm dws-remove-error"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm dws-add-error" data-di="<?= $di ?>">+ Add Code</a>
            </div>

            </section><!-- /sec-dws -->
            <?php } ?>

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
                            <img src="img/info.ico" title="If Yes then the user will always be shown the same content on every visit" />
                            Save user flow (Sticky):
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

            <?php $jbd = $c->black->jsBotDetection; ?>
            <div class="flow-group">
            <span class="flow-group-title">JS Bot Detection</span>
            <div class="form-group-inner">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                        <label class="login2 pull-left pull-left-pro">
                            <img src="img/info.ico" title="If enabled, the user will first see a safe page. Only after browser-side checks confirm a real human will they see the money page." />
                            Enable JS Bot Detection:
                        </label>
                    </div>
                    <div class="col-lg-9 col-md-6 col-sm-6 col-xs-12">
                        <div class="bt-df-checkbox pull-left">
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= !$jbd->enabled ? 'checked' : '' ?> value="false" name="black.jsbotdetection.enabled" onclick="(document.getElementById('jbd-settings').style.display = 'none')" /> No
                            </label></div></div></div>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="radio" <?= $jbd->enabled ? 'checked' : '' ?> value="true" name="black.jsbotdetection.enabled" onclick="(document.getElementById('jbd-settings').style.display = 'block')" /> Yes
                            </label></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="jbd-settings" style="display:<?= $jbd->enabled ? 'block' : 'none' ?>;">
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Timeout (msec):</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="10000" name="black.jsbotdetection.timeout" value="<?= $jbd->timeout ?>" /></div>
                    </div>
                </div>
                <div class="form-group-inner">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Tests:</label></div>
                        <div class="col-lg-9"><div class="bt-df-checkbox pull-left">
                            <?php foreach (['pointerdown' => 'Mouse click / Touch start', 'keydown' => 'Text typing', 'devicemotion' => 'Device motion (Android only)', 'deviceorientation' => 'Device orientation (Android only)', 'audiocontext' => 'Audio engine existence', 'timezone' => 'Time zone'] as $ev => $evLabel) { ?>
                            <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                                <input type="checkbox" name="black.jsbotdetection.events[]" value="<?= $ev ?>" <?= in_array($ev, $jbd->events) ? 'checked' : '' ?>
                                    <?= $ev === 'timezone' ? 'onchange="(document.getElementById(\'jbd-tz\').style.display = this.checked ? \'block\' : \'none\')"' : '' ?> />
                                <?= $evLabel ?>
                            </label></div></div></div>
                            <?php } ?>
                        </div></div>
                    </div>
                </div>
                <div id="jbd-tz" class="form-group-inner" style="display:<?= in_array('timezone', $jbd->events) ? 'block' : 'none' ?>;">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Minimum allowed timezone</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="-3" name="black.jsbotdetection.timezone.min" value="<?= $jbd->tzMin ?>" /></div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Maximum allowed timezone</label></div>
                        <div class="col-lg-3"><input type="text" class="form-control" placeholder="3" name="black.jsbotdetection.timezone.max" value="<?= $jbd->tzMax ?>" /></div>
                    </div>
                </div>
            </div>
            </div>

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
            </section>

            <?php foreach ($c->black->flows as $fi => $flow) { ?>
            <section id="sec-flow-<?= $fi ?>" class="camp-section flow-section" data-flow-index="<?= $fi ?>">
            <h5 class="flow-section-title"><?= htmlspecialchars($flow->name) ?></h5>

            <div class="flow-group">
            <span class="flow-group-title">Flow Filters</span>
            <div class="form-group-inner">
                <div class="row">
                    <div id="flow-filters-<?= $fi ?>"></div>
                </div>
            </div>
            </div>

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
                <?php
                // ── Win Probability computation ──
                if ($flow->distribution === 'thompson') {
                    $winProbData = [];
                    $totalImp = 0;
                    $isFunnel = $flow->hasPrelanding() && $flow->optimize_mode === 'funnels';

                    // Current variants from flow settings
                    $curPrelands = $flow->hasPrelanding() ? $flow->preland->folderNames : [];
                    $curLands = $flow->land->action === 'redirect' ? $flow->land->redirectUrls : $flow->land->folderNames;

                    if ($isFunnel) {
                        $stats = $db->get_funnel_stats($campId, $flow->name, $flow->optimize_for);
                        $statsMap = [];
                        foreach ($stats as $row) {
                            if (!in_array($row['preland'], $curPrelands, true) || !in_array($row['land'], $curLands, true)) continue;
                            $key = $row['preland'] . ' + ' . $row['land'];
                            $statsMap[$key] = ['imp' => (int)$row['impressions'], 'conv' => (int)$row['conversions']];
                            $totalImp += (int)$row['impressions'];
                        }
                        $winProbData = AbTest::compute_win_probabilities($statsMap);
                    } else {
                        // Separate mode: show preland probabilities (if any) + land probabilities
                        if ($flow->hasPrelanding()) {
                            $pStats = $db->get_variant_stats($campId, $flow->name, 'preland', $flow->optimize_for);
                            $pMap = [];
                            foreach ($pStats as $row) {
                                if (!in_array($row['variant'], $curPrelands, true)) continue;
                                $pMap[$row['variant']] = ['imp' => (int)$row['impressions'], 'conv' => (int)$row['conversions']];
                                $totalImp += (int)$row['impressions'];
                            }
                            $winProbPreland = AbTest::compute_win_probabilities($pMap);
                        }
                        $lStats = $db->get_variant_stats($campId, $flow->name, 'land', $flow->optimize_for);
                        $lMap = [];
                        foreach ($lStats as $row) {
                            if (!in_array($row['variant'], $curLands, true)) continue;
                            $lMap[$row['variant']] = ['imp' => (int)$row['impressions'], 'conv' => (int)$row['conversions']];
                            $totalImp += (int)$row['impressions'];
                        }
                        $winProbLand = AbTest::compute_win_probabilities($lMap);
                    }
                ?>
                <div class="flow-winprob">
                    <?php if ($totalImp < 10) { ?>
                        <div class="winprob-empty">Not enough data yet (<?= $totalImp ?> impressions)</div>
                    <?php } elseif ($isFunnel) { ?>
                        <div class="winprob-section-title">Funnel Win Probability</div>
                        <?php $isFirst = true; foreach ($winProbData as $variant => $prob) { ?>
                        <div class="winprob-row <?= $isFirst ? 'winprob-leader' : '' ?>">
                            <span class="winprob-name"><?= htmlspecialchars($variant) ?></span>
                            <div class="winprob-bar-wrap">
                                <div class="winprob-bar" style="width: <?= max($prob, 2) ?>%"></div>
                            </div>
                            <span class="winprob-pct"><?= $prob ?>%</span>
                        </div>
                        <?php $isFirst = false; } ?>
                    <?php } else { ?>
                        <?php if ($flow->hasPrelanding() && !empty($winProbPreland)) { ?>
                        <div class="winprob-section-title">Prelanding Win Probability</div>
                        <?php $isFirst = true; foreach ($winProbPreland as $variant => $prob) { ?>
                        <div class="winprob-row <?= $isFirst ? 'winprob-leader' : '' ?>">
                            <span class="winprob-name"><?= htmlspecialchars($variant) ?></span>
                            <div class="winprob-bar-wrap">
                                <div class="winprob-bar" style="width: <?= max($prob, 2) ?>%"></div>
                            </div>
                            <span class="winprob-pct"><?= $prob ?>%</span>
                        </div>
                        <?php $isFirst = false; } ?>
                        <?php } ?>
                        <?php if (!empty($winProbLand)) { ?>
                        <div class="winprob-section-title">Landing Win Probability</div>
                        <?php $isFirst = true; foreach ($winProbLand as $variant => $prob) { ?>
                        <div class="winprob-row <?= $isFirst ? 'winprob-leader' : '' ?>">
                            <span class="winprob-name"><?= htmlspecialchars($variant) ?></span>
                            <div class="winprob-bar-wrap">
                                <div class="winprob-bar" style="width: <?= max($prob, 2) ?>%"></div>
                            </div>
                            <span class="winprob-pct"><?= $prob ?>%</span>
                        </div>
                        <?php $isFirst = false; } ?>
                        <?php } ?>
                        <?php if (empty($winProbPreland ?? []) && empty($winProbLand)) { ?>
                        <div class="winprob-empty">No variant data collected yet</div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <?php } ?>
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
                            <div class="col-lg-3"><input type="text" class="form-control flow-preland-folder" value="<?= htmlspecialchars($pf) ?>" placeholder="preland1" readonly /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-preland-weight" value="<?= $flow->preland->weights[$pi] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-3"><div class="btn-group btn-group-sm"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn flow-preland-mode" data-mode="<?= $flow->preland->isDirectLoad($pf) ? 'direct' : 'base' ?>" data-modes="base,direct" title="Loading mode"><i class="bi <?= $flow->preland->isDirectLoad($pf) ? 'bi-hdd-network' : 'bi-house-door' ?>"></i></a><a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a><a href="javascript:void(0)" class="btn btn-danger flow-remove-preland" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="<?= $fi ?>" data-type="preland"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="<?= $fi ?>" data-type="preland"><i class="bi bi-upload"></i> Upload ZIP</a>
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
                            <div class="col-lg-3"><input type="text" class="form-control flow-land-folder" value="<?= htmlspecialchars($lf) ?>" placeholder="land1" readonly /></div>
                            <div class="col-lg-2 flow-weight-col" style="display:<?= $flow->distribution === 'weighted' ? 'block' : 'none' ?>">
                                <input type="number" step="1" class="form-control flow-land-weight" value="<?= $flow->land->weights[$li] ?? '' ?>" placeholder="%" style="width:70px" />
                            </div>
                            <div class="col-lg-3"><div class="btn-group btn-group-sm"><a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn flow-land-mode" data-mode="<?= $flow->land->isDirectLoad($lf) ? 'direct' : 'base' ?>" data-modes="base,direct" title="Loading mode"><i class="bi <?= $flow->land->isDirectLoad($lf) ? 'bi-hdd-network' : 'bi-house-door' ?>"></i></a><a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a><a href="javascript:void(0)" class="btn btn-danger flow-remove-land-folder" title="Delete"><i class="bi bi-trash"></i></a></div></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="<?= $fi ?>" data-type="land"><i class="bi bi-folder-symlink"></i> Add Existing</a>
                <a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="<?= $fi ?>" data-type="land"><i class="bi bi-upload"></i> Upload ZIP</a>
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
                            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-redirect" title="Delete"><i class="bi bi-trash"></i></a></div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-redirect" data-fi="<?= $fi ?>">+ Add Redirect</a>
                <div class="form-group-inner" style="margin-top:10px">
                    <div class="row">
                        <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                        <div class="col-lg-3">
                            <select class="form-select flow-redirect-type" data-fi="<?= $fi ?>">
                                <?php foreach ([301,302,303,307] as $rt) { ?>
                                <option value="<?= $rt ?>" <?= $flow->land->redirectType === $rt ? 'selected' : '' ?>><?= $rt ?></option>
                                <?php } ?>
                            </select>
                        </div>
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
                                <a href="javascript:void(0)" class="remove-backfix-url-item btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></a>
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
                                <a class="remove-s2s-item btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></a>
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
    <script type="module" src="js/campsettings/load-mode.js"></script>
    <script type="module" src="js/campsettings/white-pages.js"></script>
    <script type="module" src="js/campsettings/domain-specific.js"></script>
    <script>window._dwsCounterInit = <?= count($c->domains) ?>;</script>
    <script type="module" src="js/campsettings/dws-sync.js"></script>
    <script type="module" src="js/campsettings/domains.js"></script>
    <script type="module" src="js/campsettings/form-submit.js"></script>
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
    <!-- Folder Picker Modal -->
    <div id="folderPickerModal" class="ywbmodal" style="max-width:420px !important;">
        <div class="fp-modal-content">
            <div class="fp-modal-header"><h5 style="margin:0;font-size:18px;color:#e2e8f0;">Select Folder</h5></div>
            <div class="fp-modal-body">
                <input type="text" id="fp-search" placeholder="Search..." class="fp-search-input">
                <div id="fp-list" class="fp-list-wrap"></div>
                <div id="fp-empty" style="display:none;color:#94a3b8;text-align:center;padding:20px 0;">No folders found. Upload a ZIP first.</div>
            </div>
            <div class="fp-modal-footer">
                <button type="button" class="btn btn-default btn-sm" id="fp-cancel">Cancel</button>
                <button type="button" class="btn btn-info btn-sm" id="fp-ok">OK</button>
            </div>
        </div>
    </div>
    <style>
        #folderPickerModal{background:#151b2d !important;padding:0 !important;border-radius:12px !important;overflow:hidden !important;}
        .fp-modal-content{display:flex;flex-direction:column;max-height:80vh;}
        .fp-modal-header{padding:12px 16px;border-bottom:1px solid #2a3245;}
        .fp-modal-body{padding:12px 16px;flex:1;overflow:hidden;display:flex;flex-direction:column;}
        .fp-search-input{width:100%;padding:6px 12px;background:#1a2235;border:1px solid #2a3245;border-radius:6px;color:#e2e8f0;font-size:14px;margin-bottom:10px;}
        .fp-search-input:focus{outline:none;border-color:#0084ff;}
        .fp-list-wrap{max-height:45vh;overflow-y:auto;}
        .fp-list-wrap::-webkit-scrollbar{width:8px;}
        .fp-list-wrap::-webkit-scrollbar-track{background:#1a2235;border-radius:4px;}
        .fp-list-wrap::-webkit-scrollbar-thumb{background:#2d3748;border-radius:4px;}
        .fp-modal-footer{padding:12px 16px;border-top:1px solid #2a3245;display:flex;justify-content:flex-end;gap:8px;}
        #fp-list label{display:block;padding:8px 12px;margin:0;border-radius:6px;cursor:pointer;color:#e2e8f0;font-size:14px;transition:background .15s}
        #fp-list label:hover{background:#1e2a3f}
        #fp-list input[type=radio]{margin-right:10px;accent-color:#0084ff}
        #fp-list label.fp-selected{background:#1a2a45}
    </style>

    <!-- Load Mode Modal -->
    <div id="loadModeModal" class="ywbmodal" style="max-width:380px !important;">
        <div class="fp-modal-content">
            <div class="fp-modal-header"><h5 style="margin:0;font-size:18px;color:#e2e8f0;">Loading Mode</h5></div>
            <div class="fp-modal-body" id="lm-body"></div>
            <div class="fp-modal-footer">
                <button type="button" class="btn btn-default btn-sm" id="lm-cancel">Cancel</button>
                <button type="button" class="btn btn-info btn-sm" id="lm-ok">OK</button>
            </div>
        </div>
    </div>
    <style>
        #loadModeModal{background:#151b2d !important;padding:0 !important;border-radius:12px !important;overflow:hidden !important;}
        .lm-option{display:block;padding:10px 14px;margin:0 0 4px;border-radius:6px;cursor:pointer;color:#e2e8f0;font-size:14px;transition:background .15s}
        .lm-option:hover{background:#1e2a3f}
        .lm-option input[type=radio]{margin-right:10px;accent-color:#0084ff}
        .lm-option.lm-selected{background:#1a2a45}
        .lm-desc{display:block;margin-left:26px;font-size:12px;color:#94a3b8;margin-top:2px}
    </style>

    <script>window.LANDING_FOLDER = <?= json_encode(get_cache_path('landingFolder')) ?>;window.WHITE_FOLDER = <?= json_encode(get_cache_path('whiteFolder')) ?>;</script>
    <!-- CodeMirror 6 local bundles -->
    <script src="js/cm6/html.min.js"></script>
    <script>window.CM6_HTML = cm6;</script>
    <script src="js/cm6/css.min.js"></script>
    <script>window.CM6_CSS = cm6;</script>
    <script src="js/cm6/javascript.min.js"></script>
    <script>window.CM6_JS = cm6;</script>
    <script src="js/cm6/php.min.js"></script>
    <script>window.CM6_PHP = cm6;</script>
    <script type="module" src="js/fileeditor.js"></script>
    <script type="module" src="js/flows/index.js"></script>
    <script type="module" src="js/campsettings-nav.js"></script>

    <!-- ── Flow templates (used by js/flows/ modules) ── -->
    <template id="tpl-folder-row">
        <div class="form-group-inner flow-path-item"><div class="row">
            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro" data-role="folder-label">Folder:</label></div>
            <div class="col-lg-3"><input type="text" class="form-control" data-role="folder-input" value="" placeholder="folder" readonly /></div>
            <div class="col-lg-2 flow-weight-col" style="display:none">
                <input type="number" step="1" class="form-control" data-role="weight-input" value="" placeholder="%" style="width:70px" /></div>
            <div class="col-lg-3"><div class="btn-group btn-group-sm">
                <a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-role="mode-btn" data-mode="base" data-modes="base,direct" title="Loading mode"><i class="bi bi-house-door"></i></a>
                <a href="javascript:void(0)" class="btn btn-warning flow-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>
                <a href="javascript:void(0)" class="btn btn-danger" data-role="remove-btn" title="Delete"><i class="bi bi-trash"></i></a>
            </div></div>
        </div></div>
    </template>

    <template id="tpl-redirect-row">
        <div class="form-group-inner flow-path-item"><div class="row">
            <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect URL:</label></div>
            <div class="col-lg-4"><input type="text" class="form-control flow-land-redirect" value="" placeholder="https://..." /></div>
            <div class="col-lg-2 flow-weight-col" style="display:none">
                <input type="number" step="1" class="form-control flow-land-weight" value="" placeholder="%" style="width:70px" /></div>
            <div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger btn-sm flow-remove-land-redirect" title="Delete"><i class="bi bi-trash"></i></a></div>
        </div></div>
    </template>

    <template id="tpl-flow-section">
        <section id="sec-flow-__FI__" class="camp-section flow-section" data-flow-index="__FI__">
        <h5 class="flow-section-title">__FLOWNAME__</h5>

        <div class="flow-group"><span class="flow-group-title">Flow Filters</span>
        <div class="form-group-inner">
            <div class="row"><div id="flow-filters-__FI__"></div></div>
        </div></div>

        <div class="flow-group"><span class="flow-group-title">Distribution</span>
        <div class="form-group-inner">
            <select class="form-select flow-dist" data-fi="__FI__">
                <option value="equal" selected>Equal</option><option value="weighted">Weighted</option><option value="thompson">Thompson Sampling</option></select></div>

        <div class="flow-thompson-opts" id="flow-thompson-opts-__FI__" style="display:none">
            <div class="form-group-inner"><label class="login2 pull-left pull-left-pro">Optimize for:</label>
            <div class="bt-df-checkbox pull-left">
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="Lead" name="flow___FI___optimize_for" class="flow-optimize-for" data-fi="__FI__" /> Lead</label></div></div></div>
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="Purchase" name="flow___FI___optimize_for" class="flow-optimize-for" data-fi="__FI__" /> Purchase</label></div></div></div>
            </div></div>
            <div class="form-group-inner flow-optimize-mode-wrap" id="flow-optimize-mode-wrap-__FI__" style="display:none">
            <label class="login2 pull-left pull-left-pro">Optimize mode:</label>
            <div class="bt-df-checkbox pull-left">
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" checked value="funnels" name="flow___FI___optimize_mode" class="flow-optimize-mode" data-fi="__FI__" /> Funnels (preland+land combos)</label></div></div></div>
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label><input type="radio" value="separate" name="flow___FI___optimize_mode" class="flow-optimize-mode" data-fi="__FI__" /> Separate (independent)</label></div></div></div>
            </div></div></div></div>

        <div class="flow-group"><span class="flow-group-title">Prelanding method</span>
        <div class="form-group-inner">
            <div class="bt-df-checkbox pull-left">
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                    <input type="radio" checked value="none" name="flow___FI___preland_action" class="flow-preland-action" data-fi="__FI__" /> Don't use prelanding
                </label></div></div></div>
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                    <input type="radio" value="folder" name="flow___FI___preland_action" class="flow-preland-action" data-fi="__FI__" /> Local prelanding(s) from folder
                </label></div></div></div>
            </div></div>

        <div class="flow-preland-folders" id="flow-preland-folders-__FI__" style="display:none">
            <div class="flow-preland-items" id="flow-preland-items-__FI__"></div>
            <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="__FI__" data-type="preland"><i class="bi bi-folder-symlink"></i> Add Existing</a>
            <a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="__FI__" data-type="preland"><i class="bi bi-upload"></i> Upload ZIP</a>
        </div></div>

        <div class="flow-group"><span class="flow-group-title">Landing method</span>
        <div class="form-group-inner">
            <div class="bt-df-checkbox pull-left">
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                    <input type="radio" checked value="folder" name="flow___FI___land_action" class="flow-land-action" data-fi="__FI__" /> Local landing(s) from folder
                </label></div></div></div>
                <div class="row"><div class="col-lg-12"><div class="i-checks pull-left"><label>
                    <input type="radio" value="redirect" name="flow___FI___land_action" class="flow-land-action" data-fi="__FI__" /> Redirect(s)
                </label></div></div></div>
            </div></div>

        <div class="flow-land-folders" id="flow-land-folders-__FI__" style="display:block">
            <div class="flow-land-folder-items" id="flow-land-folder-items-__FI__"></div>
            <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-existing" data-fi="__FI__" data-type="land"><i class="bi bi-folder-symlink"></i> Add Existing</a>
            <a href="javascript:void(0)" class="btn btn-info btn-sm flow-upload-zip" data-fi="__FI__" data-type="land"><i class="bi bi-upload"></i> Upload ZIP</a>
        </div>

        <div class="flow-land-redirects" id="flow-land-redirects-__FI__" style="display:none">
            <div class="flow-land-redirect-items" id="flow-land-redirect-items-__FI__"></div>
            <a href="javascript:void(0)" class="btn btn-primary btn-sm flow-add-land-redirect" data-fi="__FI__">+ Add Redirect</a>
            <div class="form-group-inner" style="margin-top:10px"><div class="row">
                <div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect type:</label></div>
                <div class="col-lg-3"><select class="form-select flow-redirect-type" data-fi="__FI__">
                    <option value="301">301</option><option value="302" selected>302</option><option value="303">303</option><option value="307">307</option>
                </select></div></div></div></div></div>

        </section>
    </template>

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
