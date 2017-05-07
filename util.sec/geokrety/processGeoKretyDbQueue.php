<?php
require_once __DIR__ . '/../../lib/ClassPathDictionary.php';

use Controllers\GeoKretyLogController;

$gkCtrl = new GeoKretyLogController();

// Uncomment if necessary
$gkCtrl->enableDebugMsgs();

$gkCtrl->runQueueProcessing();

// /srv/cron/cron-defs/do-wget-url util.sec/geokrety/logGeokretyCronJob.php logGeokretyCronJob.html
