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
                $options = array();
                // Handle charset setting for MySQL
                $charset = Config::read('db.charset');
                if (!empty($charset)) {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . $charset;
                }
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
        if (empty($request)) {
            throw new InvalidArgumentException('SQL request cannot be empty');
        }
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
        else
        {
            $error = $stmt->errorInfo();
            return 'Ошибка при выполнении запроса: '.$request.'<br>'.$error[2];
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
            return $stmt->errorInfo();
        $stmt = NULL;
    }

    public static function updateAddress($type, $service, $address)
    {
        $stmt = self::newStatement("UPDATE `notifications` SET `address` = :address WHERE `id` = :service AND `type` = :type");
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':service', $service);
        $stmt->bindParam(':type', $type);
        if ($stmt->execute())
            return TRUE;
        else
            return $stmt->errorInfo();
        $stmt = NULL;
    }

    public static function setUpdateNotification($param)
    {
        $stmt = self::newStatement("UPDATE `settings` SET `val` = :val WHERE `key` = 'sentUpdateNotification'");
        $stmt->bindParam(':val', $param);
        if ($stmt->execute())
            return TRUE;
        else
            return $stmt->errorInfo();
        $stmt = NULL;
    }

    public static function getUpdateNotification()
    {
        $stmt = self::newStatement("SELECT `val` FROM `settings` WHERE `key` = 'sentUpdateNotification'");
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                if ($row['val'] == 1)
                    return TRUE;
                else
                    return FALSE;
            }
        }
        $stmt = NULL;
        $resultArray = NULL;
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
        $stmt = self::newStatement("SELECT `id`, `tracker`, `log`, `pass`, `passkey`, `necessarily` FROM `credentials` ORDER BY `tracker`");
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
                $resultArray[$i]['necessarily'] = $row['necessarily'];
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

    public static function getTorrentsList($order, $dir = 'ASC')
    {
    	if ($order == 'date')
    		$order = 't.timestamp ' . $dir;
    	else
    		$order = 't.tracker, t.name ' . $dir . ', t.hd';

        if (Database::getDbType() == 'pgsql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT t.id, t.tracker, t.name, t.hd, t.path, t.torrent_id, t.ep, t.timestamp, t.auto_update, t.script, t.pause,
                            to_char(t.timestamp, 'dd') AS day,
                            to_char(t.timestamp, 'mm') AS month,
                            to_char(t.timestamp, 'YYYY') AS year,
                            to_char(t.timestamp, 'HH24:MI:SS') AS time,
                            t.hash, t.error, t.closed, c.type
                            FROM torrent t
                            INNER JOIN credentials c ON c.tracker = t.tracker
                            ORDER BY {$order}");
        }
        elseif (Database::getDbType() == 'mysql')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `t`.`id`, `t`.`tracker`, `t`.`name`, `t`.`hd`, `t`.`path`, `t`.`torrent_id`, `t`.`ep`, `t`.`timestamp`, `t`.`auto_update`, `t`.`script`, `t`.`pause`,
                            DATE_FORMAT(`t`.`timestamp`, '%d') AS `day`,
                            DATE_FORMAT(`t`.`timestamp`, '%m') AS `month`,
                            DATE_FORMAT(`t`.`timestamp`, '%Y') AS `year`,
                            DATE_FORMAT(`t`.`timestamp`, '%T') AS `time`,
                            `t`.`hash`, `t`.`error`, `t`.`closed`, `c`.`type`
                            FROM `torrent` t
                            INNER JOIN `credentials` c ON `c`.`tracker` = `t`.`tracker`
                            ORDER BY {$order}");
        }
        elseif (Database::getDbType() == 'sqlite')
        {
            $stmt = Database::getInstance()->dbh->prepare("SELECT `t`.`id`, `t`.`tracker`, `t`.`name`, `t`.`hd`, `t`.`path`, `t`.`torrent_id`, `t`.`ep`, `t`.`timestamp`, `t`.`auto_update`, `t`.`script`, `t`.`pause`,
                            strftime('%d', `t`.`timestamp`) AS `day`,
                            strftime('%m', `t`.`timestamp`) AS `month`,
                            strftime('%Y', `t`.`timestamp`) AS `year`,
                            strftime('%H:%M', `t`.`timestamp`) AS `time`,
                            `t`.`hash`, `t`.`error`, `t`.`closed`, `c`.`type`
                            FROM `torrent` t
                            INNER JOIN `credentials` c ON `c`.`tracker` = `t`.`tracker`
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
                $resultArray[$i]['auto_update'] = $row['auto_update'];
                $resultArray[$i]['script'] = $row['script'];
                $resultArray[$i]['pause'] = $row['pause'];
                $resultArray[$i]['day'] = $row['day'];
                $resultArray[$i]['month'] = $row['month'];
                $resultArray[$i]['year'] = $row['year'];
                $resultArray[$i]['time'] = $row['time'];
                $resultArray[$i]['hash'] = $row['hash'];
                $resultArray[$i]['error'] = $row['error'];
                $resultArray[$i]['closed'] = $row['closed'];
                $resultArray[$i]['type'] = $row['type'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        else
        {
            $error = $stmt->errorInfo();
            return 'Ошибка в функции: '.__CLASS__.'::'.__FUNCTION__.': '.$error[2];
        }
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getTorrent($id)
    {
        $stmt = self::newStatement("SELECT `id`, `tracker`, `name`, `hd`, `path`, `torrent_id`, `auto_update`, `script`, `pause`, `closed` FROM `torrent` WHERE `id` = :id");
        $stmt->bindParam(':id', $id);
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
                $resultArray[$i]['script'] = $row['script'];
                $resultArray[$i]['pause'] = $row['pause'];
                $resultArray[$i]['closed'] = $row['closed'];
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


    public static function addThremeToBuffer($user_id, $section, $threme_id, $threme, $timestamp, $tracker)
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
                    $stmt = self::newStatement("INSERT INTO buffer (user_id, section, threme_id, threme, timestamp, tracker) VALUES (:user_id, :section, :threme_id, :threme, :timestamp, :tracker)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':section', $section);
                    $stmt->bindParam(':threme_id', $threme_id);
                    $threme = preg_replace('/<wbr>/', '', $threme);
                    $stmt->bindParam(':threme', $threme);
                    $stmt->bindParam(':timestamp', $timestamp);
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
        $stmt = self::newStatement("SELECT `id`, `section`, `threme_id`, `threme`, `timestamp`, `tracker` FROM `buffer` WHERE `user_id` = :user_id AND `new` = '1' ORDER BY `threme_id` DESC");
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
                $resultArray[$i]['timestamp'] = $row['timestamp'];
                $resultArray[$i]['tracker'] = $row['tracker'];
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
                Database::setThreme($tracker, $name, '', $threme, 0);
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

    public static function thremesClear($user_id)
    {
        $stmt = self::newStatement("UPDATE `buffer` SET `new` = '0' WHERE `user_id` = :user_id");
        $stmt->bindParam(':user_id', $user_id);
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

    public static function setThreme($tracker, $name, $path, $threme, $update_header)
    {
        $stmt = self::newStatement("INSERT INTO `torrent` (`tracker`, `name`, `path`, `torrent_id`, `auto_update`) VALUES (:tracker, :name, :path, :threme, :update_header)");
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':threme', $threme);
        $stmt->bindParam(':update_header', $update_header);
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

    public static function updateSerial($id, $name, $path, $hd, $reset, $script, $pause)
    {
        if ($reset)
            $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `hd` = :hd, `ep` = '', `timestamp` = '2000-01-01 00:00:00', `hash` = '', `script` = :script, `pause` = :pause WHERE `id` = :id");
        else
            $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `hd` = :hd, `script` = :script, `pause` = :pause WHERE `id` = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':script', $script);
        $stmt->bindParam(':hd', $hd);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':pause', $pause);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }

    public static function updateThreme($id, $name, $path, $threme, $update, $reset, $script, $pause)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `name` = :name, `path` = :path, `torrent_id` = :torrent_id, `auto_update`= :auto_update, `script` = :script, `pause` = :pause WHERE `id` = :id");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':script', $script);
        $stmt->bindParam(':torrent_id', $threme);
	    $stmt->bindParam(':auto_update', $update);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':pause', $pause);
        $stmt->execute();

        if ($reset)
        {
            $stmt = self::newStatement("UPDATE `torrent` SET `timestamp` = '2000-01-01 00:00:00', `hash` = '' WHERE `id` = :id");
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

    public static function getWarningsCountSimple()
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS `count` FROM `warning`");
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $resultArray['count'] = $row['count'];
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getNewsCount()
    {
        $stmt = self::newStatement("SELECT COUNT(*) AS `count` FROM `news` WHERE `new` = 1");
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $resultArray['count'] = $row['count'];
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
            $stmt = Database::getInstance()->dbh->prepare("SELECT \"time\", \"reason\", \"where\", \"t_id\",
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
            $stmt = Database::getInstance()->dbh->prepare("SELECT `time`, `reason`, `where`, `t_id`,
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
            $stmt = Database::getInstance()->dbh->prepare("SELECT `time`, `reason`, `where`, `t_id`,
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
                $resultArray[$i]['id'] = $row['t_id'];
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

    public static function setWarnings($date, $tracker, $message, $id)
    {
        $stmt = self::newStatement("INSERT INTO `warning` (`time`, `where`, `reason`, `t_id`) VALUES (:date, :tracker, :message, :id)");
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }

    public static function setErrorToThreme($id, $value)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `error` = :error WHERE `id` = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':error', $value);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }

    public static function setClosedThreme($id, $closed)
    {
        $stmt = self::newStatement("UPDATE `torrent` SET `closed` = :closed WHERE `id` = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':closed', $closed);
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

    public static function saveToTemp($id, $name, $path, $tracker, $date)
    {
        if (Database::getDbType() == 'pgsql') {
            $sql = "INSERT INTO `temp` (`id`, `name`, `path`, `tracker`, `date`) VALUES (:id, :name, :path, :tracker, :date) ON CONFLICT (`id`) DO UPDATE SET `name`=EXCLUDED.`name`, `path`=EXCLUDED.`path`, `tracker`=EXCLUDED.`tracker`, `date`=EXCLUDED.`date`";
        } elseif (Database::getDbType() == 'mysql') {
            $sql = "INSERT INTO `temp` (`id`, `name`, `path`, `tracker`, `date`) VALUES (:id, :name, :path, :tracker, :date) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `path`=VALUES(`path`), `tracker`=VALUES(`tracker`), `date`=VALUES(`date`)";
        } elseif (Database::getDbType() == 'sqlite') {
            $sql = "INSERT OR REPLACE INTO `temp` (`id`, `name`, `path`, `tracker`, `date`) VALUES (:id, :name, :path, :tracker, :date)";
        }
        $stmt = self::newStatement($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':path', $path);
        $stmt->bindParam(':tracker', $tracker);
        $stmt->bindParam(':date', $date);
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }

    public static function getAllFromTemp()
    {
        $stmt = self::newStatement("SELECT `id`, `name`, `path`, `tracker`, `date` FROM `temp`");
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['name'] = $row['name'];
                $resultArray[$i]['path'] = $row['path'];
                $resultArray[$i]['tracker'] = $row['tracker'];
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
        if (Database::getDbType() == 'pgsql') {
            $sql = "INSERT INTO `news` (`id`, `text`) VALUES (:id, :text) ON CONFLICT (`id`) DO UPDATE SET `text`=EXCLUDED.`text`";
        } elseif (Database::getDbType() == 'mysql') {
            $sql = "INSERT INTO `news` (`id`, `text`) VALUES (:id, :text) ON DUPLICATE KEY UPDATE `text`=VALUES(`text`)";
        } elseif (Database::getDbType() == 'sqlite') {
            $sql = "INSERT OR REPLACE INTO `news` (`id`, `text`) VALUES (:id, :text)";
        }
        $stmt = self::newStatement($sql);
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

    //
    public static function getScript($id)
    {
        $stmt = self::newStatement("SELECT `script` FROM `torrent` WHERE `id` = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $resultArray['script'] = $row['script'];
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getServiceList($type)
    {
        $stmt = self::newStatement("SELECT `id`, `service`, `address` FROM `notifications` WHERE `type` = :type");
        $stmt->bindParam(':type', $type);
        if ($stmt->execute())
        {
            $i = 0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['id'] = $row['id'];
                $resultArray[$i]['service'] = $row['service'];
                $resultArray[$i]['address'] = $row['address'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        else
            return $stmt->errorInfo();
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getService($type)
    {
        $stmt = self::newStatement("SELECT `service`, `address` FROM `settings` LEFT JOIN `notifications` ON `settings`.`val` = ".(Database::getDbType() == "pgsql" ? "cast(`notifications`.`id` as text)" : "`notifications`.`id`")." WHERE `settings`.`key` = :type");
        $stmt->bindParam(':type', $type);
        if ($stmt->execute())
        {
            foreach ($stmt as $row)
            {
                $resultArray['service'] = $row['service'];
                $resultArray['address'] = $row['address'];
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        else
            return $stmt->errorInfo();
        $stmt = NULL;
        $resultArray = NULL;
    }

    public static function getProxy()
    {
        $stmt = self::newStatement("SELECT `key`, `val` FROM `settings` WHERE `key` LIKE 'proxy%' ORDER BY `id`");
        if ($stmt->execute())
        {
            $i=0;
            foreach ($stmt as $row)
            {
                $resultArray[$i]['key'] = $row['key'];
                $resultArray[$i]['val'] = $row['val'];
                $i++;
            }
            if ( ! empty($resultArray))
                return $resultArray;
        }
        else
            return $stmt->errorInfo();
        $stmt = NULL;
        $resultArray = NULL;
    }
    
    public static function clearTemp()
    {
        $stmt = self::newStatement("DELETE FROM `temp`");
        if ($stmt->execute())
            return TRUE;
        else
            return FALSE;
        $stmt = NULL;
    }
}
?>
