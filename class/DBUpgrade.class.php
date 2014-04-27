<?php 
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once dirname(__FILE__)."/Database.class.php";

class DBUpgrade {
    public static $lasterror;
	public static function DBVersion(){
        return intval(Database::getSetting('dbversion'));
    }
	public static function AddSetting($id, $setting, $defvalue){
        if(Database::getSetting($setting) === NULL){
            $stmt = Database::newStatement("Insert into settings (`id`, `key`, `val`) values (:id, :setting, :val)");
            $stmt->bindParam(':id', $id);
			$stmt->bindParam(':setting', $setting);
			$stmt->bindParam(':val', $defvalue);
			$result = $stmt->execute();
            if(!$result)
                self::$lasterror = $stmt->errorinfo()[2];
            else
                self::$lasterror = "$setting успешно добавлено";
            return $result;
	  }
      return TRUE;
	}
	
	public static function AddCredential($id, $tracker){
        if(Database::getCredentials($tracker) === NULL){
            $stmt = Database::newStatement("Insert into `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`) ".
                                           "values (:id, :tracker, '', '', '')");
            $stmt->bindParam(':id', $id);
			$stmt->bindParam(':tracker', $tracker);
			$result = $stmt->execute();
            if(!$result)
                self::$lasterror = $stmt->errorinfo()[2];
            else
                self::$lasterror = "$tracker успешно добавлен";
            return $result;
	  }
	  return TRUE;
	}
	
	public static function AddColumn($table, $column, $column_definition){
        if(!Database::columnExists($table, $column)){
			$result = Database::addNewColumn($table, $column, $column_definition);
            if(!$result)
                self::$lasterror = "Не смог добавить колонку $column  к таблице $table.";
            return $result;

        }
	    return TRUE;
	}
    
	public static function Upgrade() {
        self::$lasterror = '';
        $version = self::DBVersion();
        if($version < Database::$currentversion) {
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
	            self::AddCredential(9,'baibako.tv') and
	            self::AddCredential(10,'casstudio.tv') and
	            self::AddCredential(11,'newstudio.tv') and
	            self::AddCredential(12,'animelayer.ru' ) and
                self::AddColumn('torrent', 'hash', 'VARCHAR(40)') and //If a column already exists it just does nothing
                self::AddColumn('torrent', 'path', 'VARCHAR(100)'); 
		
            return $OperationResult and Database::updateSettings('dbversion', Database::$currentversion);
        }
        return true;
	}
}
?>