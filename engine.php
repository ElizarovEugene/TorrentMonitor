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

function getTimestamp()
{
    return '['.date_format(date_create(), 'Y-m-d H:i:s').'] ';
}

$debug = Database::getSetting('debug');
$autoUpdate = Database::getSetting('autoUpdate');

$is_console = PHP_SAPI == 'cli';
if ($is_console)
    $NL = "\r\n";
else
    $NL = "<br />";

$time_start_full = microtime(true);
if (Sys::checkCurl())
{
	$torrentsList = Database::getTorrentsList('name');
	$count = count($torrentsList);
	echo getTimestamp();
	echo 'Опрос новых раздач на трекерах:'.$NL;
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
				
				$functionEngine = include_once $engineFile;
				$class = explode('.', $tracker);
				$class = $class[0];
				$functionClass = str_replace('-', '', $class);
				
				if ($tracker == 'tracker.0day.kiev.ua')
				    $functionClass = 'kiev';
				    
                if ($tracker == 'tv.mekc.info')
				    $functionClass = 'mekc';
				    
				if ($tracker == 'baibako.tv_forum')
				    $functionClass = 'baibako_f';

                echo getTimestamp();
				echo $torrentsList[$i]['name'].' на трекере '.$tracker.$NL;
				if ($torrentsList[$i]['pause'])
				{
    				echo getTimestamp();
    				echo 'Наблюдение за данной темой приостановлено.'.$NL;
    				continue;
				}
				if ($torrentsList[$i]['type'] == 'RSS')
				{
				    $time_start = microtime(true);
				    call_user_func($functionClass.'::main', $torrentsList[$i]);
				    $time_end = microtime(true);
				    $time = $time_end - $time_start;
				    if ($debug)
				    {
    				    echo getTimestamp();
				        echo 'Время выполнения: '.$time.$NL;
				    }
				}
				if ($torrentsList[$i]['type'] == 'forum')
				{
				    $time_start = microtime(true);
					call_user_func($functionClass.'::main', $torrentsList[$i]);
					$time_end = microtime(true);
					$time = $time_end - $time_start;
					if ($debug)
					{
    					echo getTimestamp();
				        echo 'Время выполнения: '.$time.$NL;
				    }
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
    $time_end_overall = microtime(true);
    $time = $time_end_overall - $time_start_overall;
    if ($debug)
    {
        echo getTimestamp();
        echo 'Общее время опроса трекеров: '.$time.$NL;
    }
			
	$usersList = Database::getUserToWatch();
	$count = count($usersList);
	echo getTimestamp();
    echo 'Опрос новых раздач пользователей на трекерах:'.$NL;
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

				$functionEngine = include_once $serchFile;
				$class = explode('.', $tracker);
				$class = $class[0];
				$class = str_replace('-', '', $class);
				$functionClass = $class.'Search';
				echo getTimestamp();
                echo 'Пользователь '.$usersList[$i]['name'].' на трекере '.$tracker.$NL;
                $time_start = microtime(true);
				call_user_func($functionClass .'::mainSearch', $usersList[$i]);
				$time_end = microtime(true);
				$time = $time_end - $time_start;
				if ($debug)
				{
    				echo getTimestamp();
				    echo 'Время выполнения: '.$time.$NL;
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
    $time_end_overall = microtime(true);
    $time = $time_end_overall - $time_start_overall;
    if ($debug)
    {
        echo getTimestamp();
        echo 'Общее время опроса пользователей на трекерах: '.$time.$NL;
    }
    echo getTimestamp();
	echo '=================='.$NL;
	echo getTimestamp();
	echo 'Выполение служебных функций:'.$NL;
	echo getTimestamp();
	echo 'Добавляем темы из Temp.'.$NL;
	$time_start = microtime(true);
	$tempList = Database::getAllFromTemp();
	if (count($tempList) > 0)
	    Sys::AddFromTemp($tempList);
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	if ($debug)
	{
    	echo getTimestamp();
	    echo 'Время выполнения: '.$time.$NL;
    }
    echo getTimestamp();
	echo 'Обновление новостей.'.$NL;
	$time_start = microtime(true);
	Sys::getNews();
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	if ($debug)
	{
    	echo getTimestamp();
        echo 'Время выполнения: '.$time.$NL;
    }
    echo getTimestamp();
	echo 'Удаление старых torrent-файлов.'.$NL;
	Sys::deleteOldTorrents();
    if ($autoUpdate)
    {
        echo getTimestamp();
        echo 'Установка обновлений.'.$NL;
        include_once $dir.'class/Update.class.php';
        Update::main();
    }
    else
    {
        if (Sys::checkUpdate())
        {
            if ( ! Database::getUpdateNotification())
            {
                $msg = 'Выпущена новая версия ТМ, автоматическое обновление отключено, обновите систему самостоятельно.';
                Notification::sendNotification('news', date('r'), 0, $msg, 0);
                Database::setUpdateNotification(1);
            }
        }
    }
    echo getTimestamp();
	echo 'Запись времени последнего запуска ТМ.'.$NL;
	Sys::lastStart();
}	
else
	Errors::setWarnings('system', 'curl');
	
$time_end_full = microtime(true);
$time = $time_end_full - $time_start_full;
if ($debug)
{
    echo getTimestamp();
    echo 'Общее время работы скрипта: '.$time.$NL;
}
?>