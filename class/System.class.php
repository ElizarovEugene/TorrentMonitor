<?php
class Sys
{
    //проверяем есть ли интернет
    public static function checkInternet()
    {
        $page = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'header'         => 1,
                'follow'         => 1,
                'returntransfer' => 1,
                'url'            => 'https://www.google.ru/',
            )
        );
        if (!empty($page) && preg_match('/<title>.*<\/title>/', $page))
            return TRUE;
        else
            return FALSE;
    }

    //проверяем есть ли конфигурационный файл
    public static function checkConfigExist()
    {
        $dir = dirname(__FILE__);
        $dir = str_replace('class', '', $dir);
        if (file_exists($dir.'/config.php'))
            return TRUE;
        else
            return FALSE;
    }

    //проверяем установлено ли расширение CURL
    public static function checkCurl()
    {
        if (in_array('curl', get_loaded_extensions()))
            return TRUE;
        else
            return FALSE;
    }

    //проверяем версию cURL
    public static function checkCurlVersion()
    {
        $version = '7.30';
        $curl = curl_version();
        if ($curl['version'] <= $version)
            return 'old';
        else
            return 'new';
    }

    //проверяем есть ли на конце пути /
    public static function checkPath($path)
    {
        $torrentClient = Database::getSetting('torrentClient');
        
        if (substr($path, -1) == '/')
            $path = $path;
        elseif ($torrentClient == 'SynologyDS')
            $path = $path;
        else
            $path = $path.'/';
        return $path;
    }

    //проверка на возхможность записи в директорию
    public static function checkWriteToPath($path)
    {
        return is_writable($path);
    }

    //версия системы
    public static function version()
    {
        $ROOTPATH = dirname(__FILE__)."/../";
        $version = json_decode(file_get_contents($ROOTPATH.'version.txt'));
        $ver['system'] = $version->system;
        $ver['database'] = $version->database;
        return $ver;
    }

    //проверка обновлений системы
    public static function checkUpdate()
    {
        //получаем страницу
        $page = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'returntransfer' => 1,
                'url'            => 'https://xml.tormon.ru/version.xml',
            )
        );

        //читаем xml
        $xml = @simplexml_load_string($page);
        $dir = dirname(__FILE__);
        $dir = str_replace('class', '', $dir);
        $version = json_decode(file_get_contents($dir.'version.txt'));

        if (false !== $xml)
        {
            if ($version->system < $xml->current_version)
                return TRUE;
            elseif ($version->database < $xml->current_version)
                return TRUE;
            else
                return FALSE;
        }
    }

    //обёртка для CURL, для более удобного использования
    public static function getUrlContent($param = null)
    {
        if (is_array($param))
        {
            $ch = curl_init();
            if ($param['type'] == 'POST')
                curl_setopt($ch, CURLOPT_POST, 1);

            if ($param['type'] == 'GET')
                curl_setopt($ch, CURLOPT_HTTPGET, 1);

            curl_setopt($ch, CURLOPT_USERAGENT, Database::getSetting('userAgent'));
            if (isset($param['follow']))
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

            curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 0);

            if (isset($param['encoding']))
                curl_setopt($ch, CURLOPT_ENCODING, '');

            if (isset($param['header']))
                curl_setopt($ch, CURLOPT_HEADER, 1);

            if (isset($param['ssl_false']))
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($ch, CURLOPT_TIMEOUT, Database::getSetting('httpTimeout'));

            if (isset($param['returntransfer']))
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_URL, $param['url']);

            if (isset($param['postfields']))
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param['postfields']);

            if (isset($param['cookie']))
                curl_setopt($ch, CURLOPT_COOKIE, $param['cookie']);

            if (Sys::checkCurlVersion() == 'old')
            {
                if (isset($param['sendHeader']))
                {
                    foreach ($param['sendHeader'] as $k => $v)
                    {
                        $header[] = $k.': '.$v."\r\n";
                    }
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                }
            }

            if (isset($param['referer']))
                curl_setopt($ch, CURLOPT_REFERER, $param['referer']);

            if (isset($param['userpwd']))
                curl_setopt($ch, CURLOPT_USERPWD, $param['userpwd']);

            $settingProxy = Database::getProxy();
            if (is_array($settingProxy))
            {
                $proxy = $settingProxy[0]['val'];
                $proxyAddress = $settingProxy[1]['val'];
                $proxyType = $settingProxy[2]['val'];
            }

            $ext_proxy = Config::read('ext_proxy');
            if ( ! empty($ext_proxy))
            {
                $url = parse_url($param['url']);
                $tracker = preg_replace('/www\./', '', $url['host']);
                if (isset($ext_proxy[$tracker]))
                {
                    if ($ext_proxy[$tracker]['use'] == 'yes')
                    {
                        $proxy = TRUE;
                        if (count($ext_proxy[$tracker]) == 3)
                        {
                            $proxyAddress = $ext_proxy[$tracker]['address'];
                            $proxyType = $ext_proxy[$tracker]['type'];
                        }
                        else
                        {
                            $proxyAddress = $settingProxy[1]['val'];
                            $proxyType = $settingProxy[2]['val'];
                        }
                        echo 'Use proxy: '.$proxyAddress;
                    }
                    else
                        $proxy = FALSE;
                }
            }
            if ($proxy)
            {
                curl_setopt($ch, CURLOPT_PROXY, $proxyAddress);
                if ($proxyType == 'SOCKS5')
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                elseif ($proxyType == 'HTTP')
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }

            if (Database::getSetting('debug'))
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

            $result = curl_exec($ch);
            curl_close($ch);

            if (isset($param['convert']) && $param['convert'] != NULL)
                $result = iconv($param['convert'][0], $param['convert'][1], $result);

            return $result;
        }
    }

    //Проверяем доступность трекера
    public static function checkavAilability($tracker)
    {
        $page = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'header'         => 1,
                'follow'         => 1,
                'returntransfer' => 1,
                'url'            => $tracker,
            )
        );

        if (preg_match('/HTTP\/1\.1 200 OK/', $page))
            return true;
        else
            return false;
    }

    public static function parseHeader($tracker, $page)
    {
        preg_match('/<title>(.*)<\/title>/', $page, $array);
        if ( ! empty($array[1]))
        {
            if ($tracker == 'anidub.com')
                $name = substr($array[1], 0, -114);
            elseif ($tracker == 'animelayer.ru')
                $name = substr($array[1], 0, -15);
            elseif ($tracker == 'baibako.tv')
                $name = substr($array[1], 0, -13);
            elseif ($tracker == 'booktracker.org')
                $name = substr($array[1], 0, -31);
            elseif ($tracker == 'casstudio.tk')
                $name = substr($array[1], 48);
            elseif ($tracker == 'kinozal.me' || $tracker == 'kinozal.tv' || $tracker == 'kinozal.guru')
                $name = substr($array[1], 0, -22);
            elseif ($tracker == 'nnmclub.to')
                $name = substr($array[1], 0, -12);
            elseif ($tracker == 'rutracker.org')
                $name = substr($array[1], 0, -17);
            elseif ($tracker == 'tfile.cc')
                $name = substr($array[1], 15, -25);
            elseif ($tracker == 'pornolab.net')
                $name = substr($array[1], 0, -16);
            elseif ($tracker == 'riperam.org')
            {
                preg_match('/(.*) \&bull\; Riper\.AM/', $array[1], $array2);
                if ( ! empty($array2[1]))
                    $name = $array2[1];

            }
            elseif ($tracker == 'rutor.is')
                $name = substr($array[1], 14);
            else
                $name = $array[1];
        }
        return $name;
    }

    //Получаем заголовок страницы
    public static function getHeader($url)
    {
        $Purl = parse_url($url);
        $tracker = $Purl['host'];
        $tracker = preg_replace('/www\./', '', $tracker);

        if ($tracker == 'rustorka.com'  || $tracker == 'booktracker.org' || $tracker == 'kinozal.guru')
        {
            $dir = str_replace('class', '', dirname(__FILE__));
            $engineFile = $dir.'trackers/'.$tracker.'.engine.php';
            if (file_exists($engineFile))
            {
                Database::clearWarnings('system');

                $functionEngine = include_once $engineFile;
                $class = explode('.', $tracker);
                $class = $class[0];
                $functionClass = str_replace('-', '', $class);
            }

            $cookie = Database::getCookie($tracker);
            $exucution = FALSE;
            if (call_user_func($functionClass.'::checkCookie', $cookie))
            {
                $sess_cookie = $cookie;
                //запускам процесс выполнения
                $exucution = TRUE;
            }
            else
            {
                $sess_cookie = call_user_func($functionClass.'::getCookie', $tracker);
                //запускам процесс выполнения
                $exucution = TRUE;
            }

            if ($exucution)
            {
                //получаем страницу для парсинга
                if ($tracker == 'rustorka.com')
                    $convert = array('windows-1251', 'utf-8//IGNORE');
                else
                    $convert = NULL;

                $forumPage = Sys::getUrlContent(
                    array(
                        'type'           => 'POST',
                        'header'         => 0,
                        'returntransfer' => 1,
                        'url'            => $url,
                        'cookie'         => $sess_cookie,
                        'sendHeader'     => array('Host' => $tracker, 'Content-length' => strlen($sess_cookie)),
                        'convert'        => $convert,
                    )
                );
            }
        }
        else
        {
            $forumPage = Sys::getUrlContent(
                array(
                    'type'           => 'GET',
                    'header'         => 0,
                    'follow'         => 1,
                    'returntransfer' => 1,
                    'url'            => $url,
                )
            );
        }

        if ($tracker != 'animelayer.ru' && $tracker != 'booktracker.org' && $tracker != 'casstudio.tk' && $tracker != 'riperam.org' && $tracker != 'rustorka.com' && $tracker != 'rutor.is' && $tracker != 'tr.anidub.com')
            $forumPage = iconv('windows-1251', 'utf-8//IGNORE', $forumPage);

        if ($tracker == 'tr.anidub.com')
            $tracker = 'anidub.com';

        $name = Sys::parseHeader($tracker, $forumPage);
        if (empty($name))
            $name = 'Неизвестный';

        return $name;
    }

    //выполняем пользовательский скрипт
    public static function runScript($id, $tracker, $name, $hash, $message, $date_str)
    {
        $script = Database::getScript($id);
        if ( ! empty($script['script']))
            print(`{$script['script']} '{$tracker}' '{$name}' '{$hash}' '{$message}' '{$date_str}'`);

    }

    //добавляем в torrent-клиент
    public static function addToClient($id, $name, $path, $hash, $tracker, $date_str)
    {
        $torrentClient = Database::getSetting('torrentClient');
        $dir = dirname(__FILE__).'/';
        include_once $dir.$torrentClient.'.class.php';
        $server = Database::getSetting('serverAddress');
        $dir = str_replace('class/', '', $dir);
        $filename = str_replace($dir.'torrents/', '', $path);
        $filename = urlencode($filename);
        $url = $server.'torrents/'.$filename;
        $status = call_user_func($torrentClient.'::addNew', $id, $url, $hash, $tracker);
        if ($status['status'])
        {
            Database::deleteFromTemp($id);
            $return['status'] = TRUE;
            $return['hash'] = $status['hash'];
        }
        else
        {
            Database::saveToTemp($id, $name, $path, $tracker, $date_str);
            Errors::setWarnings($torrentClient, $status['msg']);
            $return['status'] = FALSE;
        }
        return $return;
    }

    //сохраняем torrent файл
    public static function saveTorrent($tracker, $file, $torrent, $id, $hash, $message, $date_str, $name)
    {
        $t_id = $file;
        $file = str_replace("'", '', $file);
        $file = str_replace(":", '', $file);
        $file = '['.$tracker.']_'.$file.'.torrent';
        $dir = dirname(__FILE__).'/';
        $path = str_replace('class/', '', $dir).'torrents/'.$file;
        if (file_exists($path))
            unlink($path);
        if (file_put_contents($path, $torrent))
        {
            $useTorrent = Database::getSetting('useTorrent');
            if ($useTorrent)
            {
                $status = Sys::addToClient($id, $name, $path, $hash, $tracker, $date_str);
                if ($status['status'])
                    $message = $message.' И добавлен в torrent-клиент.';
                else
                    $message = $message.' Но не добавлен в torrent-клиент и сохранён.';
                //выполняем кастомный скрипт
                if ($status['status'])
                    Sys::runScript($id, $tracker, $name, $status['hash'], $message, $date_str);
            }
            else
                $message = $message.' И сохранён.';

            if ($tracker == 'anidub.com')
            {
                $torrent = Database::getTorrent($id);
                $t_id = $torrent[0]['torrent_id'];
            }
        }
        else
            $message = $message.' Но не удалось сохранить torrent-файл.';

        //отправляем уведомлении об обновлении
        Notification::sendNotification('notification', $date_str, $tracker, $message, $name, $t_id);
    }

    //добавляем раздачи из Temp в torrent-клиент
    public static function AddFromTemp($list)
    {
        for ($i=0; $i<count($list); $i++)
        {
    	    $status = Sys::addToClient($list[$i]['id'], $list[$i]['name'], $list[$i]['path'], '', $list[$i]['tracker'], $list[$i]['date_str']);
    	    if ($status['status'])
    	    {
        	    $message = 'Torrent-клиент доступен и раздача '.$list[$i]['name'].' добавлена.';
    	        Database::updateHash($list[$i]['id'], $status['hash']);
    	        Notification::sendNotification('notification', $list[$i]['date_str'], $list[$i]['tracker'], $message, $list[$i]['name']);
                Sys::runScript($list[$i]['id'], $list[$i]['tracker'], $list[$i]['name'], $status['hash'], $message, $list[$i]['date_str']);
            }
        }
    }

    //преобразуем месяц из числового в текстовый
    public static function dateNumToString($date)
    {
        $monthes_num = array('/10/', '/11/', '/12/', '/0?1/', '/0?2/', '/0?3/', '/0?4/', '/0?5/', '/0?6/', '/0?7/', '/0?8/', '/0?9/');
        $monthes_ru = array('Окт', 'Ноя', 'Дек', 'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен');
        $month = preg_replace($monthes_num, $monthes_ru, $date);

        return $month;
    }

    //преобразуем месяц из текстового в числовый
    public static function dateStringToNum($date)
    {
        $monthes = array('/января|янв|Янв|Jan/i', '/февраля|фев|Фев|Feb/i', '/марта|мар|Мар|Mar/i', '/апреля|апр|Апр|Apr/i', '/мая|май|Мая|Май|May/i', '/июня|июн|Июн|Jun/i', '/июля|июл|Июл|Jul/i', '/августа|авг|Авг|Aug/i', '/сентября|сен|Сен|Sep/i', '/октября|окт|Окт|Oct/i', '/ноября|ноя|Ноя|Nov/i', '/декабря|дек|Дек|Dec/i');
        $monthes_num = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
        $month = preg_replace($monthes, $monthes_num, $date);

        return $month;
    }

    //записываем время последнего запуска системы
    public static function lastStart()
    {
        $dir = dirname(__FILE__);
        $dir = str_replace('class', '', $dir);
        $date = date('d-m-Y H:i:s');
        file_put_contents($dir.'/laststart.txt', $date);
    }

    //проверяем что файл является torrent-файлом (ну пытаемся)
    public static function checkTorrentFile($torrent)
    {
        if (strlen($torrent) > 100)
        {
            if (preg_match('/announce/', $torrent))
                return TRUE;
            else
                return FALSE;
        }
        else
            return FALSE;
    }

    //удаляем старые torrent-файлы
    public static function deleteOldTorrents()
    {
        $dir = dirname(__FILE__).'/../torrents/';
        $files = array();

        if ($handle = opendir($dir))
        {
            while (false !== ($file = readdir($handle)))
        	{
                if ($file != '.' && $file != '..' && $file != '.htaccess')
                   $files[filemtime($dir.$file)] = $file;
            }
            closedir($handle);
            ksort($files);

            $end = count($files) - 20;
        	$i = 0;
            foreach($files as $file)
        	{
        		if ($i < $end)
        			unlink($dir.$file);
        		$i++;
            }
        }
    }

    //получаем важные новости и кладём в БД
    public static function getNews()
    {
        //получаем страницу
        $page = Sys::getUrlContent(
            array(
                'type'           => 'GET',
                'returntransfer' => 1,
                'url'            => 'https://xml.tormon.ru/news.xml',
            )
        );

        //читаем xml
        $page = @simplexml_load_string($page);
        if (!empty($page) && isset($page->news->id) && is_countable($page->news->id))
        {
            for ($i=0; $i<count($page->news->id); $i++)
            {
                if ( ! Database::checkNewsExist($page->news->id[$i]))
                {
                    Database::insertNews($page->news->id[$i], $page->news->text[$i]);
                    Notification::sendNotification('news', date('r'), 0, $page->news->text[$i], 0);
                }
            }
            Database::clearWarnings('TorrentMonitor');
        }
        else
            Errors::setWarnings('TorrentMonitor', 'update_news');
    }

    //ф-ция преобразования true/false в int
    public static function strBoolToInt($value)
    {
        if ($value == 'true' or $value == 1)
            return 1;
        else
            return 0;
    }

    //проверяем авторизован пользователь или нет (если авторизация включена)
    public static function checkAuth()
    {
        if (session_id() == '')
            session_start();
        include_once "Database.class.php";
        $auth = Database::getSetting('auth');

        if ($auth)
        {
            if (isset($_COOKIE['TM']))
                $_SESSION['TM'] = $_COOKIE['TM'];

            if (empty($_SESSION['TM']))
                return FALSE;

            if ( ! empty($_SESSION['TM']))
            {
                $hash_pass = Database::getSetting('password');
                if ($_SESSION['TM'] != $hash_pass)
                    return FALSE;
                else
                    return TRUE;
            }

            if ( ! empty($_COOKIE['hash_pass']))
            {
                $hash_pass = Database::getSetting('password');
                if ($_COOKIE['hash_pass'] != $hash_pass)
                    return FALSE;
                else
                    return TRUE;
            }
        }
        if ( ! $auth)
            return TRUE;
    }
}
?>
