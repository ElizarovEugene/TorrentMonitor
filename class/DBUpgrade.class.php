<?php 
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once dirname(__FILE__)."/Database.class.php";

class DBUpgrade {
	public static function AddSetting($id, $setting, $defvalue){
	  if(Database::getSetting($setting) === NULL){
	        echo "inserting $setting";
            $stmt = Database::getInstance()->dbh->prepare("Insert into settings (`id`, `key`, `val`) values (:id, :setting, :val)");
			$stmt->bindParam(':id', $id);
			$stmt->bindParam(':setting', $setting);
			$stmt->bindParam(':val', $defvalue);
			return $stmt->execute() or die(var_dump($stmt->errorinfo()));
			
	  }
	  return TRUE;
	}
	
	public static function Upgrade() {
	    $OperationResult = 
		self::AddSetting(1, 'email', '') and
		self::AddSetting(2, 'path', '') and
		self::AddSetting(3, 'send', '1') and
		self::AddSetting(4, 'send_warning', '0') and
		self::AddSetting(5, 'password', '1f10c9fd49952a7055531975c06c5bd8') and
		self::AddSetting(6, 'auth', '1') and
		self::AddSetting(7, 'proxy', '0') and
		self::AddSetting(8, 'proxyAddress', '127.0.0.1:9050') and
		self::AddSetting(9, 'useTorrent', '0') and
		self::AddSetting(10, 'torrentClient', '') and
		self::AddSetting(11, 'torrentAddress', '') and
		self::AddSetting(12, 'torrentLogin', '') and
		self::AddSetting(13, 'torrentPassword', '') and
		self::AddSetting(14, 'pathToDownload', '') and
		self::AddSetting(15, 'deleteTorrent', '0') and
		self::AddSetting(16, 'deleteOldFiles', '0') and
		self::AddSetting(17, 'dbversion', '0') and
		
		Database::AddNewColumn('torrent', 'hash', 'VARCHAR(40) NOT NULL'); //If a column already exists it just does nothing
		
		return $OperationResult and Database::updateSettings('dbversion', Database::$currentversion);
	}
}

DBUpgrade::upgrade();
?>