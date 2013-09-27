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
include_once $dir.'adapters/ClientAdapterFactory.class.php';
header('Content-Type: text/html; charset=utf-8');

if (Sys::checkConfig())
{
	if (Sys::checkCurl())
	{
		$torrentsList = Database::getTorrentsList('name');
		$count = count($torrentsList);
		
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
					
					if ($tracker == 'lostfilm.tv' || $tracker == 'novafilm.tv')
					{
    				    call_user_func($functionClass .'::main', $torrentsList[$i]['id'], $tracker, $torrentsList[$i]['name'], $torrentsList[$i]['hd'], $torrentsList[$i]['ep'], $torrentsList[$i]['timestamp']);
					}
					if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.me' || $tracker == 'rutor.org' || $tracker == 'tfile.me' || $tracker == 'kinozal.tv' || $tracker == 'anidub.com')
					{
    					call_user_func($functionClass .'::main', $torrentsList[$i]['id'], $tracker, $torrentsList[$i]['name'], $torrentsList[$i]['torrent_id'], $torrentsList[$i]['timestamp']);
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
		
		$usersList = Database::getUserToWatch();
		$count = count($usersList);
		
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
					
					echo $tracker.' - '.$usersList[$i]['name'].'<br>'."\r\n";

					call_user_func($functionClass .'::mainSearch', $usersList[$i]['id'], $tracker, $usersList[$i]['name']);
					
					$functionClass = NULL;
					$functionEngine = NULL;
				}
				else
					Errors::setWarnings('system', 'missing_files');
			}
			else
				Errors::setWarnings('system', 'credential_miss');
		}
		
		Sys::lastStart();
	}	
	else
		Errors::setWarnings('system', 'curl');
}
else
	echo 'Для корректной работы необходимо внести изменения в конфигурационный файл.';
?>