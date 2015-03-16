<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";

class Database
{
    public $dbh;
    private static $instance;

    private function __construct()
    {
        $this->dbType = Config::read('db.type');
        
        switch ($this->dbType)
        {
            case 'mysql':
                $dsn = "mysql:host=".Config::read('db.host').";dbname=".Config::read('db.basename');
                $username = Config::read('db.user');
                $password = Config::read('db.password');
                $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".Config::read('db.charset'));
                break;
			case 'sqlite':
				$dsn = "sqlite:".Config::read('db.basename');
				$options = array(PDO::ATTR_PERSISTENT => true);
				break;
			case 'sqlite2':
				$dsn = "sqlite2:".Config::read('db.basename');
				$options = array(PDO::ATTR_PERSISTENT => true);
				break;
            case 'pgsql':
                $dsn = "pgsql:host=".Config::read('db.host').";port=".Config::read('db.port').";dbname=".Config::read('db.basename').";user=".Config::read('db.user').";password=".Config::read('db.password');
                break;
        }
        
        try {
            if ($this->dbType == 'pgsql')
               $this->dbh = new PDO($dsn);
            elseif ($this->dbType == 'sqlite')
               $this->dbh = new PDO($dsn, NULL, NULL, $options);
            elseif ($this->dbType == 'mysql')
               $this->dbh = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            print 'Error!: '.$e->getMessage().'<br/>';
            die();
        }
    }

    public static function getInstance()
    {
        if ( ! isset(self::$instance))
        {
            $object = __CLASS__;
            self::$instance = new $object;
        }
        return self::$instance;
    }
    
    public static function getDbType()
    {
        return Database::getInstance()->dbType;
    }
    
    public static function newStatement($request)
    {
        if (self::getDbType() == 'pgsql')
            $request = str_replace('`','"',$request);
	    return self::getInstance()->dbh->prepare($request);
    }
    
    public static function updateQuery($request)
    {
        $stmt = self::newStatement($request);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                return $row['val'];
            }
        }
        $stmt = NULL;        
    }

    public static function getSetting($param)
    {
        $stmt = self::newStatement("SELECT `val` FROM `settings` WHERE `key` = :param");
        $stmt->bindParam(':param', $param);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                return $row['val'];
            }
        }
        $stmt = NULL;
    }
    
    public static function getAllSetting()
    {
        $stmt = self::newStatement("SELECT * FROM `settings`");
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $resultArray[] = array("{$row['key']}" => "{$row['val']}");
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getPaths()
    {
        $stmt = self::newStatement("SELECT DISTINCT(`path`) AS `path` FROM `torrent`");
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                if ( ! empty($row['path']))
                {
                    $resultArray[$i]['path'] = $row['path'];
                    $i++;
                }
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL; 
    }    

    public static function getTorrentDownloadPath($id)
    {
        $stmt = self::newStatement("SELECT `path` FROM `torrent` WHERE `id` = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                return $row['path'];
            }
        }
        $stmt = NULL; 
    }

    public static function updateSettings($setting, $val)
    {
        $stmt = self::newStatement("UPDATE `settings` SET `val` = :val WHERE `key` = :setting");
        $stmt->bindParam(':setting', $setting);
        $stmt->bindParam(':val', $val);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function getCredentials($tracker)
    {
        $stmt = self::newStatement("SELECT `log`, `pass`, `passkey` FROM `credentials` WHERE `tracker` = :tracker");        
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['log'] != "" && $row['pass'] != "")
                {
                    $resultArray['login'] = $row['log'];
                    $resultArray['password'] = $row['pass'];
                    $resultArray['passkey'] = $row['passkey'];
                }
                else
                    return FALSE;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function getAllCredentials()
    {
        $stmt = self::newStatement("SELECT `id`, `tracker`, `log`, `pass`, `passkey` FROM `credentials`");        
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['tracker'] = $row['tracker'];
                $resultArray[$i]['login'] = $row['log'];
                $resultArray[$i]['password'] = $row['pass'];
                $resultArray[$i]['passkey'] = $row['passkey'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }    
    
    public static function countCredentials($password)
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS count FROM settings WHERE `key` = 'password' AND `val` = :password");
        $stmt->bindParam(':password', $password);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['count'] == '1')
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;
    }
    
    public static function updateCredentials($password)
    {
        $stmt = self::newStatement("UPDATE `settings` SET `val` = :password WHERE `key` = 'password'");        
        $stmt->bindParam(':password', $password);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function setCredentials($id, $login, $password, $passkey)
    {
        $stmt = self::newStatement("UPDATE `credentials` SET `log` = :login, `pass` = :password, `passkey` = :passkey WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':login', $login);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':passkey', $passkey);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function checkTrackersCredentialsExist($tracker)
    {
        $stmt = self::newStatement("SELECT `log`, `pass` FROM `credentials` WHERE `tracker` = :tracker");        
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ( ! empty($row['log']) && ! empty($row['pass']))
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;
    }    
    
    public static function getTrackersList()
    {
        $stmt = self::newStatement("SELECT `id`, `tracker` FROM `credentials` ORDER BY `tracker`");        
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i] = $row['tracker'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function getTorrentsList($order)
    {
    	if ($order == 'date')
    		$order = 'timestamp';
    	elseif ($order == 'dateDesc')
    		$order = 'timestamp DESC';
    	else
    		$order = 'tracker, name, hd';
    		
        if (Database::getDbType() == 'pgsql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT id, tracker, name, hd, path, torrent_id, ep, timestamp, auto_update,
                            to_char(timestamp, 'dd') AS day,
                            to_char(timestamp, 'mm') AS month,
                            to_char(timestamp, 'YYYY') AS year,
                            to_char(timestamp, 'HH24:MI:SS') AS time,
                            hash
                            FROM torrent 
                            ORDER BY {$order}");
        }
        elseif (Database::getDbType() == 'mysql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name`, `hd`, `path`, `torrent_id`, `ep`, `timestamp`, `auto_update`,
                            DATE_FORMAT(`timestamp`, '%d') AS `day`, 
                            DATE_FORMAT(`timestamp`, '%m') AS `month`, 
                            DATE_FORMAT(`timestamp`, '%Y') AS `year`, 
                            DATE_FORMAT(`timestamp`, '%T') AS `time`,
                            `hash`
                            FROM `torrent` 
                            ORDER BY {$order}");
        }
        elseif (Database::getDbType() == 'sqlite')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name`, `hd`, `path`, `torrent_id`, `ep`, `timestamp`, `auto_update`,
                            strftime('%d', `timestamp`) AS `day`, 
                            strftime('%m', `timestamp`) AS `month`, 
                            strftime('%Y', `timestamp`) AS `year`, 
                            strftime('%H:%M', `timestamp`) AS `time`,
                            `hash`
                            FROM `torrent` 
                            ORDER BY {$order}");    		
        }
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['tracker'] = $row['tracker'];
                $resultArray[$i]['name'] = $row['name'];
                $resultArray[$i]['hd'] = $row['hd'];
                $resultArray[$i]['path'] = $row['path'];
                $resultArray[$i]['torrent_id'] = $row['torrent_id'];
                $resultArray[$i]['ep'] = $row['ep'];
                $resultArray[$i]['timestamp'] = $row['timestamp'];
                $resultArray[$i]['day'] = $row['day'];
                $resultArray[$i]['month'] = $row['month'];
                $resultArray[$i]['year'] = $row['year'];
                $resultArray[$i]['time'] = $row['time'];
                $resultArray[$i]['hash'] = $row['hash'];
                $resultArray[$i]['auto_update'] = $row['auto_update'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function getTorrent($id)
    {
        if (Database::getDbType() == 'pgsql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT id, tracker, name, hd, path, torrent_id, auto_update FROM torrent WHERE id = {$id}");
        }
        elseif (Database::getDbType() == 'mysql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name`, `hd`, `path`, `torrent_id`, `auto_update` FROM `torrent` WHERE `id` = '{$id}'");
        }
        elseif (Database::getDbType() == 'sqlite')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `id`, `tracker`, `name`, `hd`, `path`, `torrent_id`, `auto_update` FROM `torrent` WHERE `id` = '{$id}'");    		
        }
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['tracker'] = $row['tracker'];
                $resultArray[$i]['name'] = $row['name'];
                $resultArray[$i]['hd'] = $row['hd'];
                $resultArray[$i]['path'] = $row['path'];
                $resultArray[$i]['torrent_id'] = $row['torrent_id'];
                $resultArray[$i]['auto_update'] = $row['auto_update'];
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }    
    
    public static function getTorrentsListByTracker($tracker)
    {
        if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
            $fields = 'hd, ep';
        if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.ru' || $tracker == 'rutor.org')
            $fields = 'torrent_id';
            
        $stmt = self::newStatement("SELECT name, timestamp, ".$fields." FROM torrent WHERE tracker = :tracker ORDER BY id");
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['name'] = $row['name'];
                $resultArray[$i]['timestamp'] = $row['timestamp'];
                if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
                {
                    $resultArray[$i]['hd'] = $row['hd'];
                    $resultArray[$i]['ep'] = $row['ep'];
                }
                if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.ru' || $tracker == 'rutor.org')
                    $resultArray[$i]['torrent_id'] = $row['torrent_id'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function getUserToWatch()
    {
        $stmt = self::newStatement("SELECT `id`, `tracker`, `name` FROM `watch` ORDER BY `tracker`");	        
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['tracker'] = $row['tracker'];
                $resultArray[$i]['name'] = $row['name'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function checkUserExist($tracker, $name)
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS count FROM `watch` WHERE `tracker` = :tracker AND `name` = :name");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['count'] == 0)
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;
    }
    
    public static function setUser($tracker, $name)
    {
        $stmt = self::newStatement("INSERT INTO `watch` (`tracker`, `name`) VALUES (:tracker, :name)");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function deletUser($id)
    {
        $stmt = self::newStatement("DELETE FROM `watch` WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            $stmt = self::newStatement("DELETE FROM `buffer` WHERE `user_id` = :id");
            $stmt->bindParam(':id', $id);		
            if ($stmt->execute())
                return TRUE;
            else
                return FALSE;
            return TRUE;
        }
        else
            return FALSE;
        $stmt = NULL;
    }   
    
     
    public static function addThremeToBuffer($user_id, $section, $threme_id, $threme, $tracker)
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS count FROM `buffer` WHERE `user_id` = :user_id AND `threme_id` = :threme_id");        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':threme_id', $threme_id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['count'] == 0)
                {
                    $stmt = self::newStatement("INSERT INTO buffer (user_id, section, threme_id, threme, tracker) VALUES (:user_id, :section, :threme_id, :threme, :tracker)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':section', $section);
                    $stmt->bindParam(':threme_id', $threme_id);
                    $threme = preg_replace('/<wbr>/', '', $threme);
                    $stmt->bindParam(':threme', $threme);
                    $stmt->bindParam(':tracker', $tracker);
                    if ($stmt->execute())
                        return TRUE;
                    else
                        return FALSE;
                    $stmt = NULL;
                }
            }
        }
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function getThremesFromBuffer($user_id)
    {
        $stmt = self::newStatement("SELECT `id`, `section`, `threme_id`, `threme` FROM `buffer` WHERE `user_id` = :user_id AND `new` = '1' ORDER BY `threme_id` DESC");        
        $stmt->bindParam(':user_id', $user_id);
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['section'] = $row['section'];
                $resultArray[$i]['threme_id'] = $row['threme_id'];
                $resultArray[$i]['threme'] = $row['threme'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;	    
    }
    
    public static function transferFromBuffer($id)
    {
        $stmt = self::newStatement("SELECT `buffer`.`threme_id`, `buffer`.`threme`, `watch`.`tracker` FROM `buffer` LEFT JOIN `watch` ON `buffer`.`user_id` = `watch`.`id` WHERE `buffer`.`id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $threme = $row['threme_id'];
                $name = $row['threme'];
                $tracker = $row['tracker'];
                Database::setThreme($tracker, $name, '', $threme);
                Database::deleteFromBuffer($id);
            }
        }
    }
    
    public static function deleteFromBuffer($id)
    {
        $stmt = self::newStatement("UPDATE `buffer` SET `new` = '0' WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function selectAllFromBuffer()
    {
        $stmt = self::newStatement("SELECT `id` FROM `buffer` WHERE `accept` = '0' AND `new` = '1'");        
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;	    
    }
    
    public static function updateThremesToDownload($id)
    {
        $stmt = self::newStatement("UPDATE `buffer` SET `accept` = '1', `new` = '0' WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;	    
    }

    public static function takeToDownload($tracker)
    {
        $stmt = self::newStatement("SELECT `id`, `threme_id`, `threme` FROM `buffer` WHERE `accept` = '1' AND `downloaded` = '0' AND `tracker` = :tracker");        
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['threme_id'] = $row['threme_id'];
                $resultArray[$i]['threme'] = $row['threme'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL; 
    }
    
    public static function setDownloaded($id)
    {
        $stmt = self::newStatement("UPDATE `buffer` SET `downloaded` = '1' WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;        
    }
    
    public static function checkSerialExist($tracker, $name, $hd)
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS `count` FROM `torrent` WHERE `tracker` = :tracker AND `name` = :name AND `hd` = :hd");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':hd', $hd);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['count'] == 0)
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;    
    }
    
    public static function checkThremExist($tracker, $id)
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS `count` FROM `torrent` WHERE `tracker` = :tracker AND `torrent_id` = :id");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['count'] == 0)
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;
    }
    
    public static function setSerial($tracker, $name, $path, $hd=FALSE)
    {
        $stmt = self::newStatement("INSERT INTO `torrent` (`tracker`, `name`, `path`, `hd`) VALUES (:tracker, :name, :path, :hd)");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':hd', $hd);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function setThreme($tracker, $name, $path, $threme)
    {
        $stmt = self::newStatement("INSERT INTO `torrent` (`tracker`, `name`, `path`, `torrent_id`) VALUES (:tracker, :name, :path, :threme)");        
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':threme', $threme);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function setNewDate($id, $date)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `timestamp` = :date WHERE `id` = :id");        
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function setNewName($id, $name)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name WHERE `id` = :id");        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function setNewEpisode($id, $ep)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `ep` = :ep WHERE `id` = :id");        
        $stmt->bindParam(':ep', $ep);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function updateSerial($id, $name, $path, $hd, $reset)
    {
        if ($reset)
            $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `hd` = :hd, `ep` = '', `timestamp` = '0000-00-00 00:00:00' WHERE `id` = :id");
        else
            $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `hd` = :hd WHERE `id` = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':hd', $hd);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function updateThreme($id, $name, $path, $threme, $update, $reset)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `torrent_id` = :torrent_id, `auto_update`=:auto_update WHERE `id` = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':torrent_id', $threme);
	    $stmt->bindParam(':auto_update', $update);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if ($reset)
        {
            $stmt = self::newStatement("UPDATE `torrent` SET `timestamp` = '0000-00-00 00:00:00' WHERE `id` = :id");
            $stmt->bindParam(':id', $id);
        }
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;

    }    
    
    public static function updateHash($id, $hash)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `hash` = :hash WHERE `id` = :id");        
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }   
    
    public static function deletItem($id)
    {
        $stmt = self::newStatement("DELETE FROM `torrent` WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function getWarningsCount()
    {
        $stmt = self::newStatement("SELECT `where`, COUNT(*) AS `count` FROM `warning` GROUP BY `where`");        
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['where'] = $row['where'];
                $resultArray[$i]['count'] = $row['count'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function getWarningsList($tracker)
    {
        if (Database::getDbType() == 'pgsql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT time, reason, \"where\",
                            to_char(time, 'dd') AS day,
                            to_char(time, 'mm') AS month,
                            to_char(time, 'YYYY') AS year,
                            to_char(time, 'HH24:MI') AS hours
                            FROM warning
                            WHERE \"where\" = :tracker
                            ORDER BY time DESC");
        }
        elseif (Database::getDbType() == 'mysql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `time`, `reason`, `where`,
                            DATE_FORMAT(`time`, '%d') as 'day',
                            DATE_FORMAT(`time`, '%m') as 'month', 
                            DATE_FORMAT(`time`, '%Y') as 'year', 
                            DATE_FORMAT(`time`, '%H:%i') as 'hours'
                            FROM `warning` 
                            WHERE `where` = :tracker
                            ORDER BY `time` DESC");
        }
        elseif (Database::getDbType() == 'sqlite')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `time`, `reason`, `where`,
                            strftime('%d', `time`) as 'day',
                            strftime('%m', `time`) as 'month', 
                            strftime('%Y', `time`) as 'year', 
                            strftime('%H:%M', `time`) as 'hours'
                            FROM `warning` 
                            WHERE `where` = :tracker
                            ORDER BY `time` DESC");
        }
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['time'] = $row['time'];
                $resultArray[$i]['reason'] = $row['reason'];
                $resultArray[$i]['where'] = $row['where'];
                $resultArray[$i]['day'] = $row['day'];
                $resultArray[$i]['month'] = $row['month'];
                $resultArray[$i]['year'] = $row['year'];
                $resultArray[$i]['time'] = $row['hours'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }        
    
    public static function setWarnings($date, $tracker, $message)
    {
        $stmt = self::newStatement("INSERT INTO `warning` (`time`, `where`, `reason`) VALUES (:date, :tracker, :message)");        
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':message', $message);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function clearWarnings($tracker)
    {
        $stmt = self::newStatement("DELETE FROM `warning` WHERE `where` = :tracker");        
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;    
    }
    
    public static function getCookie($tracker)
    {
        $stmt = self::newStatement("SELECT `cookie` FROM `credentials` WHERE `tracker` = :tracker");
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                return $row['cookie'];
            }
            return NULL;
        }
        $stmt = NULL;
    }
    
    public static function setCookie($tracker, $cookie)
    {
        $stmt = self::newStatement("UPDATE `credentials` SET `cookie` = :cookie WHERE `tracker` = :tracker");
        $stmt->bindParam(':cookie', $cookie);
        $stmt->bindParam(':tracker', $tracker);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    public static function saveToTemp($id, $path, $hash, $tracker, $message, $date)
    {
        $stmt = self::newStatement("INSERT INTO `temp` (`id`, `path`, `hash`, `tracker`, `message`, `date`) VALUES (:id, :path, :hash, :tracker, :message, :date)");        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':hash', $hash);
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':date', $date);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;        
    }
    
    public static function getAllFromTemp()
    {
        $stmt = self::newStatement("SELECT `id`, `path`, `hash`, `tracker`, `message`, `date` FROM `temp`");
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['path'] = $row['path'];
                $resultArray[$i]['hash'] = $row['hash'];
                $resultArray[$i]['tracker'] = $row['tracker'];
                $resultArray[$i]['message'] = $row['message'];
                $resultArray[$i]['date_str'] = $row['date'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
    }
    
    public static function deleteFromTemp($id)
    {
        $stmt = self::newStatement("DELETE FROM `temp` WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;        
    }
    
    public static function getNews()
    {
        $stmt = self::newStatement("SELECT `id`, `text`, `new` FROM `news` ORDER BY `id` DESC");        
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['text'] = $row['text'];
                $resultArray[$i]['new'] = $row['new'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }   
    
    public static function checkNewsExist($id)
    {
        $stmt = self::newStatement("SELECT `id` FROM `news` WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ( ! empty($row['id']))
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;        
    }
    
    public static function insertNews($id, $text)
    {
        $stmt = self::newStatement("INSERT INTO `news` (`id`, `text`) VALUES (:id, :text)");        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':text', $text);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
    
    //Помечаем новость как прочитанную
    public static function markNews($id)
    {
        $stmt = self::newStatement("UPDATE `news` SET `new` = 0 WHERE `id` = :id");        
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
}
?>