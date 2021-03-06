<?php

use Utils\Database\OcDb;
$rootpath = '../';
require_once($rootpath . 'lib/common.inc.php');
require_once __DIR__ . '/../lib/ClassPathDictionary.php';
require_once($rootpath . 'lib/cache_owners.inc.php');

$db = OcDb::instance();
$db->beginTransaction();
$pco = new OrgCacheOwners($db);
$pco->populateAll();
$db->commit();

