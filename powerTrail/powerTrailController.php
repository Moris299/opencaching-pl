<?php
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/powerTrailAPI.php';

/**
 * 
 */
class powerTrailController {
	
	private $debug = true;	
	private $action;
	private $user;
	private $userPTs;
	private $ptAPI;
	private $allSeries;
	private $allCachesOfSelectedPt;
	private $powerTrailCachesUserLogsByCache;
	private $powerTrailDbRow;
	private $ptOwners;
	
	function __construct($user) 
	{
		if(isset($_REQUEST['ptAction'])) {
			$this->action = $_REQUEST['ptAction'];
		} else {
			$this->action = 'showAllSeries';
		}
		
		// self::debug($_POST, 'POST', __LINE__);
		if(isset($_POST['createNewPowerTrail'])) $this->action = 'createNewPowerTrail';
		
		$this->ptAPI = new powerTrailApi;
		
		$this->user = $user;
	}
	
	public function run()
	{
		switch ($this->action) {
			case 'selectCaches':
				$this->getUserPTs();
				return $this->getUserCachesToChose();
				break;
			case 'createNewPowerTrail':
				$this->createNewPowerTrail();
				break;
			case 'showAllSeries':
				$this->getAllPowerTrails();
				break;
			case 'showSerie':
				$this->getPowerTrailCaches();
				break;		
			default:
				$this->getAllPowerTrails();
				break;
		}
	}

	private function getAllPowerTrails()
	{
		$q = 'SELECT * FROM `PowerTrail` WHERE `status` = 1 and cacheCount > '.powerTrailApi::minimumCacheCount;
		$db = new dataBase();
		$db->multiVariableQuery($q);
		$this->allSeries = $db->dbResultFetchAll();
	}
	
	private function getPowerTrailCaches()
	{
		$powerTrailId = isset($_REQUEST['ptrail'])?$_REQUEST['ptrail']:0;
		$db = new dataBase(true);
		$ptq = 'SELECT * FROM `PowerTrail` WHERE `id` = :1 LIMIT 1';
		$db->multiVariableQuery($ptq, $powerTrailId);
		$this->powerTrailDbRow = $db->dbResultFetch();
		
		$q = 'SELECT * FROM `caches` WHERE cache_id IN (SELECT `cacheId` FROM `powerTrail_caches` WHERE `PowerTrailId` = :1)';
		$db->multiVariableQuery($q, $powerTrailId);
		$this->allCachesOfSelectedPt = $db->dbResultFetchAll();
		
		$qr = 'SELECT `cache_id`, `date`, `text_html`, `text`  FROM `cache_logs` WHERE `cache_id` IN ( SELECT `cacheId` FROM `powerTrail_caches` WHERE `PowerTrailId` = :1) AND `user_id` = :2 AND `deleted` = 0 AND `type` = 1';
		isset($_SESSION['user_id']) ? $userId = $_SESSION['user_id'] : $userId = 0;
		$db->multiVariableQuery($qr, $powerTrailId, $userId);
		$powerTrailCacheLogsArr = $db->dbResultFetchAll();
		$powerTrailCachesUserLogsByCache = array();
		foreach ($powerTrailCacheLogsArr as $log) {
			$powerTrailCachesUserLogsByCache[$log['cache_id']] = array (
				'date' => $log['date'],
				'text_html' => $log['text_html'],
				'text' => $log['text'],
			);
		}
		// self::debug($powerTrailCacheLogsArr);
		// self::debug($powerTrailCachesUserLogsByCache);
		$this->powerTrailCachesUserLogsByCache = $powerTrailCachesUserLogsByCache;
		$this->findPtOwners($powerTrailId);
	}

	public function getPtOwners()
	{
		return $this->ptOwners;
	}

	public function getPowerTrailDbRow()
	{
		return $this->powerTrailDbRow;
	}

	public function getPowerTrailCachesUserLogsByCache()
	{
		return $this->powerTrailCachesUserLogsByCache;
	}
	
	public function getAllCachesOfPt()
	{
		return $this->allCachesOfSelectedPt;
	}

	public function getUserPowerTrails()
	{
		return $this->userPTs;
	}
		
	public function getActionPerformed()
	{
		return $this->action;
	}
	
	public function getCountCachesAndUserFoundInPT()
	{
		$result['totalCachesCountInPowerTrail']	= count($this->allCachesOfSelectedPt);
		$result['cachesFoundByUser'] = count($this->powerTrailCachesUserLogsByCache);
		return $result;
	}
	
	public function getpowerTrails()
	{
		return $this->allSeries;	
	}
	
	private function createNewPowerTrail()
	{
		$this->action = 'createNewSerie';	
		self::debug($_POST, 'POST', __LINE__);
		if( $_POST['powerTrailName'] != '' && $_POST['type'] != 0 && $_POST['status'] != 0 && $_POST['description'] != '')
		{
			$query = "INSERT INTO `PowerTrail`(`name`, `type`, `status`, `dateCreated`, `cacheCount`, `description`) VALUES (:1,:2,:3,NOW(),0,:4)";
			$db = new dataBase($this->debug);
			$db->multiVariableQuery($query, $_POST['powerTrailName'],$_POST['type'], $_POST['status'], $_POST['description']);
			$newProjectId = $db->lastInsertId();
			$query = "INSERT INTO `PowerTrail_owners`(`PowerTrailId`, `userId`, `privileages`) VALUES (:1,:2,:3)";
			$db->multiVariableQuery($query, $newProjectId, $this->user['userid'], 1);
			$logQuery = 'INSERT INTO `PowerTrail_actionsLog`(`PowerTrailId`, `userId`, `actionDateTime`, `actionType`, `description`) VALUES (:1,:2,NOW(),2,:3)';
			$db->multiVariableQuery($logQuery, $newProjectId,$this->user['userid'] ,$this->ptAPI->logActionTypes[1]['type']);
			return true;
		} 
		else 
		{
			return false;	
		}
		
	}
	
	private function getUserCachesToChose()
	{
		$query = "SELECT cache_id, wp_oc, PowerTrailId, name FROM `caches` LEFT JOIN powerTrail_caches ON powerTrail_caches.cacheId = caches.cache_id WHERE caches.status NOT IN (3,6) AND `user_id` = :1";
		$db = new dataBase;
		$db->multiVariableQuery($query, $this->user['userid']);
		$userCaches = $db->dbResultFetchAll();
		// self::debug($userCaches, 'user Caches', __LINE__);
		return $userCaches;
	}
	
	private function getUserPTs()
	{
		$query = "SELECT * FROM `PowerTrail`, PowerTrail_owners  WHERE  PowerTrail_owners.userId = :1 AND .PowerTrailId = PowerTrail.id";
		$db = new dataBase();
		$db->multiVariableQuery($query, $this->user['userid']);
		$userPTs = $db->dbResultFetchAll();
		//self::debug($userPTs, 'user Power Trails', __LINE__);
		$this->userPTs = $userPTs;
	}
	
	public function findPtOwners($powerTrailId){
		$query = 'SELECT `userId`, `privileages`, username FROM `PowerTrail_owners`, user WHERE `PowerTrailId` = :1 AND PowerTrail_owners.userId = user.user_id';
		$db = new dataBase();
		$db->multiVariableQuery($query, $powerTrailId);
		$owner = $db->dbResultFetchAll();
		foreach ($owner as $user) {
			$owners[$user['userId']] = array (
				'privileages' => $user['privileages'],
				'username' => $user['username'],
			);
		}
		$this->ptOwners = $owners;
	}
	
	public function debug($var, $name=null, $line=null)
	{
		//if($this->debug === false) return;	
		print '<font color=green><b>#'.$line."</b> $name, </font>(".__FILE__.") <pre>";
		print_r($var); 
		print '</pre>';
	}
}



// var_dump($_SESSION);
// var_dump($usr);