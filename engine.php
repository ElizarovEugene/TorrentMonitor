<?php
////////////////////////////////////
///////////TorrentMonitor///////////
////////////////////////////////////
$dir = dirname(__FILE__).'/';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';

header('Content-Type: text/html; charset=utf-8');

$debug = Database::getSetting('debug');
$time_start_full = microtime(true);
if (Sys::checkConfig())
{
    if (Sys::checkCurl())
    {
        $torrentsList = Database::getTorrentsList('name');
        $count = count($torrentsList);

		// массив, в котором будут храниться параметры классов
		$torrentClasses = array();
        
        echo 'Опрос новых раздач на трекерах:'."\r\n".'<br />';
        if ($debug)
            $time_start_overall = microtime(true);
        
        for ($i=0; $i<$count; $i++)
        {
            $tracker = $torrentsList[$i]['tracker'];
            if (Database::checkTrackersCredentialsExist($tracker))
            {
                $engineFile = $dir.'trackers/'.$tracker.'.engine.php';
                if (file_exists($engineFile))
                {
                    Database::clearWarnings('system');
                    
					//генерируем ключ для хранения параметров класса
                    $trackerKey = 'engine-'.$tracker;
					//если в массиве классов еще нет данных для текущего треккера, то получаем класс
					if ( empty($torrentClasses[$trackerKey]) )
                        $torrentClasses[$trackerKey] = include_once $engineFile;
 
                    $functionEngine = $torrentClasses[$trackerKey];  // массив параметров класса
                    $functionClass = $functionEngine['class_name'];  // имя класса
                    $functionType  = $functionEngine['tracker_type'];// тип треккера
                    
                    echo $torrentsList[$i]['name'].' на трекере '.$tracker."\r\n".'<br />';
                    
                    if ($debug)
                        $time_start = microtime(true);
                    
                    if ($functionType == 'episodes')
                    {
                        call_user_func($functionClass.'::main', $torrentsList[$i]['id'], $tracker, $torrentsList[$i]['name'], $torrentsList[$i]['hd'], $torrentsList[$i]['ep'], $torrentsList[$i]['timestamp'], $torrentsList[$i]['hash']);
                    }
                    else if ($functionType == 'torrents')
                    {
                        call_user_func($functionClass.'::main', $torrentsList[$i]['id'], $tracker, $torrentsList[$i]['name'], $torrentsList[$i]['torrent_id'], $torrentsList[$i]['timestamp'], $torrentsList[$i]['hash'], $torrentsList[$i]['auto_update']);
                    }
                    
                    if ($debug)
                    {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;
                        echo 'Время выполнения: '.$time."\r\n".'<br />';
                    }
                    
                    $functionClass = NULL;
                    $functionEngine = NULL;
                }
                else
                    Errors::setWarnings('system', 'missing_files');             
            }
            else
                Errors::setWarnings('system', 'credential_miss');
        }
        
        if ($debug)
        {
            $time_end_overall = microtime(true);
            $time = $time_end_overall - $time_start_overall;
            echo 'Общее время опроса трекеров: '.$time."\r\n".'<br />';
        }
        
        $usersList = Database::getUserToWatch();
        $count = count($usersList);
        
        echo 'Опрос новых раздач пользователей на трекерах:'."\r\n".'<br />';
        if ($debug)
            $time_start_overall = microtime(true);
        
        for ($i=0; $i<$count; $i++)
        {
            $tracker = $usersList[$i]['tracker'];
            if (Database::checkTrackersCredentialsExist($tracker))
            {
                $serchFile = $dir.'trackers/'.$tracker.'.search.php';
                if (file_exists($serchFile))
                {
                    Database::clearWarnings('system');
                    
					//генерируем ключ для хранения параметров класса
                    $trackerKey = 'search-'.$tracker;
					//если в массиве классов еще нет данных для текущего треккера, то получаем класс
					if ( empty($torrentClasses[$trackerKey]) )
                        $torrentClasses[$trackerKey] = include_once $engineFile;
 
                    $functionEngine = $torrentClasses[$trackerKey];  // массив параметров класса
                    $functionClass = $functionEngine['class_name'];  // имя класса
                     
                    echo 'Пользователь '.$usersList[$i]['name'].' на трекере '.$tracker."\r\n".'<br />';
                    
                    if ($debug)
                        $time_start = microtime(true);
                    
                    call_user_func($functionClass .'::mainSearch', $usersList[$i]['id'], $tracker, $usersList[$i]['name']);
                    
                    if ($debug)
                    {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;
                        echo 'Время выполнения: '.$time."\r\n".'<br />';
                    }
                    
                    $functionClass = NULL;
                    $functionEngine = NULL;
                }
                else
                    Errors::setWarnings('system', 'missing_files');
            }
            else
                Errors::setWarnings('system', 'credential_miss');
        }
        
        if ($debug)
        {
            $time_end_overall = microtime(true);
            $time = $time_end_overall - $time_start_overall;
            echo 'Общее время опроса пользователей на трекерах: '.$time."\r\n".'<br />';
        }
        
        echo '=================='."\r\n".'<br />';
        echo 'Выполение служебных функций:'."\r\n".'<br />';
        echo 'Добавляем темы из Temp.'."\r\n".'<br />';

        if ($debug)
            $time_start = microtime(true);

        $tempList = Database::getAllFromTemp();
        $count = count($tempList);
        for ($i=0; $i<$count; $i++)
        {
            Sys::addToClient($tempList[$i]['id'], $tempList[$i]['path'], $tempList[$i]['hash'], $tempList[$i]['tracker'], $tempList[$i]['message'], $tempList[$i]['date_str']);
        }
        
        if ($debug)
        {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            echo 'Время выполнения: '.$time."\r\n".'<br />';
        }
        
        echo 'Обновление новостей.'."\r\n".'<br />';
        if ($debug)
            $time_start = microtime(true);
        
        Sys::getNews();

        if ($debug)
        {
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            echo 'Время выполнения: '.$time."\r\n".'<br />';
        }

        echo 'Запись времени последнего запуска ТМ.'."\r\n".'<br />';
        Sys::lastStart();
    }   
    else
        Errors::setWarnings('system', 'curl');
}
else
    echo 'Для корректной работы необходимо внести изменения в конфигурационный файл.';
    
$time_end_full = microtime(true);
$time = $time_end_full - $time_start_full;
if ($debug)
    echo 'Общее время работы скрипта: '.$time."\r\n".'<br />';
?>