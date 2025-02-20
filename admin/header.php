<?php
require_once __DIR__.'/dates.php';
require_once __DIR__.'/../debug.php';

function get_bases_version(): string
{
    $updateFile = __DIR__ . "/../bases/update.txt";
    if (!file_exists($updateFile)) {
        return "Unknown";
    }
    return file_get_contents($updateFile);
}
?>
<div class="header-advance-area">
    <div class="header-top-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <div class="logo-pro">
                        <div class="logo-container">
                            <a href="index.php" class="logo-link">
                                <img class="main-logo" src="<?=get_cloaker_path()?>img/logo.png" alt="" />
                            </a>
                            <div class="geo-version">
                                GeoBases: <a href="#" id="updateBases" title="Update bases"><?= get_bases_version() ?></a>
                                <img style="width:30px; height:30px;display:none;" src="<?=get_cloaker_path()?>img/loading.apng" id="loadingAnimation" />
                                <?php if (DebugMethods::on()): ?>
                                <span style="color: red; margin-left: 10px;">Debug Mode</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5">
                    <div class="header-right-info">
                        <ul class="nav navbar-nav mai-top-nav header-right-menu">
                            <li class="nav-item">
                                <a class="nav-link" id='litepicker'>
                                    <i class="bi bi-calendar"></i>
                                    <span>
                                        Date:&nbsp;&nbsp;<?= Dates::get_calend_date() ?>
                                    </span>
                                </a>
                                <a class="nav-link" href="" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    <span>Refresh</span>
                                </a>
                                <a class="nav-link" href="#" onclick="checkForUpdates(); return false;">
                                    <i class="bi bi-cloud-arrow-down"></i>
                                    <span>Update</span>
                                </a>
                                <a class="nav-link" href="logout.php">
                                    <i class="bi bi-door-closed"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="overlay" id="updateOverlay">
    <canvas id="matrix-rain"></canvas>
    <div class="grid-overlay"></div>
    <div class="updating-text">
        <span id="typing-text"></span><span class="cursor">█</span>
    </div>
</div>