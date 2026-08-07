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
include_once $dir.'class/CurlMultiFetcher.class.php';

header('Content-Type: text/html; charset=utf-8');

set_time_limit(0);
ignore_user_abort(true);

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
    if ($torrentsList != NULL)
        $count = count($torrentsList);
    else
        $count = 0;
    
	echo getTimestamp();
	echo 'Опрос новых раздач на трекерах:'.$NL;
    Sys::lastStart();
    $time_start_overall = microtime(true);

    //Проход 0: один раз получаем список учётных данных вместо запроса на каждой итерации
    $allCredentials = Database::getAllCredentials();
    $credentialSet = array();
    foreach ($allCredentials as $cred)
    {
        if ( ! empty($cred['login']) && ! empty($cred['password']))
            $credentialSet[$cred['tracker']] = TRUE;
    }

    $fetcher = new CurlMultiFetcher();
    $pending = array();

    //Проход 1: резолв кук/авторизации (последовательно) + формирование "проверочных" запросов
	for ($i=0; $i<$count; $i++)
	{
		$tracker = $torrentsList[$i]['tracker'];
		if (isset($credentialSet[$tracker]))
		{
			$engineFile = $dir.'trackers/'.$tracker.'.engine.php';
			if (file_exists($engineFile))
			{
			    try
			    {
				    Database::clearWarnings($tracker);

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

				    if ($tracker == 'kinozal.guru')
				        $functionClass = 'kinozalguru';

				    if ($tracker == 'kinozal.me')
				        $functionClass = 'kinozalme';

				    if ($tracker == 'kinozal.tv')
				        $functionClass = 'kinozaltv';

                    echo getTimestamp();
				    echo $torrentsList[$i]['name'].' на трекере '.$tracker.$NL;
				    if ($torrentsList[$i]['pause'])
				    {
    				    echo getTimestamp();
    				    echo 'Наблюдение за данной темой приостановлено.'.$NL;
    				    continue;
				    }
				    if ($torrentsList[$i]['type'] == 'RSS' || $torrentsList[$i]['type'] == 'forum')
				    {
				        $time_start = microtime(true);
				        $requestParams = call_user_func(array($functionClass, 'getRequestParams'), $torrentsList[$i]);
				        $time_end = microtime(true);
				        $time = $time_end - $time_start;
				        if ($debug)
				        {
    				        echo getTimestamp();
				            echo 'Время выполнения (подготовка запроса): '.$time.$NL;
				        }

				        if ( ! empty($requestParams['url']))
				        {
				            $key = $tracker.'_'.$torrentsList[$i]['id'];
				            $fetcher->add($key, $requestParams['url'], isset($requestParams['options']) ? $requestParams['options'] : array());
				            $pending[$key] = array(
				                'row'   => $torrentsList[$i],
				                'class' => $functionClass,
				            );
				        }
				    }
				}
				catch (Throwable $e)
				{
				    echo getTimestamp().'Ошибка подготовки запроса ('.$tracker.'): '.$e->getMessage().$NL;
				    Errors::setWarnings($tracker, 'engine_error');
				}
				finally
				{
				    $functionClass = NULL;
				    $functionEngine = NULL;
				}
			}
			else
				Errors::setWarnings($tracker, 'missing_files');
		}
		else
			Errors::setWarnings($tracker, 'credential_miss');
	}

    //Выполняем все "проверочные" запросы параллельно
    try {
        $responses = $fetcher->execute();
    } catch (Throwable $e) {
        echo getTimestamp().'Ошибка параллельного запроса: '.$e->getMessage().$NL;
        $responses = array();
    }

    //Проход 2: разбираем ответы и собираем изменения для пакетного обновления
    $pendingUpdates = array();
    foreach ($pending as $key => $info)
    {
        $row     = $info['row'];
        $class   = $info['class'];
        $tracker = $row['tracker'];
        $response = isset($responses[$key]) ? $responses[$key] : array('body' => '', 'http_code' => 0, 'error' => 'no response');

        $time_start = microtime(true);
        try {
            $result = call_user_func(array($class, 'parse'), $row, $response['body']);
        } catch (Throwable $e) {
            echo getTimestamp().'Ошибка parse ('.$tracker.'): '.$e->getMessage().$NL;
            Errors::setWarnings($tracker, 'engine_error');
            $time_end = microtime(true);
            continue;
        }
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        if ($debug)
        {
            echo getTimestamp();
            echo 'Время выполнения (разбор ответа, '.$tracker.'): '.$time.$NL;
        }

        if ( ! empty($result))
            $pendingUpdates = $pendingUpdates + $result;
    }

    if ( ! empty($pendingUpdates))
    {
        try {
            Database::batchUpdateTorrents($pendingUpdates);
        } catch (Throwable $e) {
            echo getTimestamp().'Ошибка сохранения обновлений в БД: '.$e->getMessage().$NL;
        }
    }

    $time_end_overall = microtime(true);
    $time = $time_end_overall - $time_start_overall;
    if ($debug)
    {
        echo getTimestamp();
        echo 'Общее время опроса трекеров: '.$time.$NL;
    }
			
	echo getTimestamp();
    echo 'Опрос новых раздач пользователей на трекерах:'.$NL;
	$time_start_overall = microtime(true);
	$usersList = Database::getUserToWatch();
	if ( ! empty($usersList))
	{
    	$count = count($usersList);
    	for ($i=0; $i<$count; $i++)
    	{
    		$tracker = $usersList[$i]['tracker'];
    		if (Database::checkTrackersCredentialsExist($tracker))
    		{
    			$serchFile = $dir.'trackers/'.$tracker.'.search.php';
    			if (file_exists($serchFile))
    			{
    				try
    				{
    				    Database::clearWarnings($tracker);

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
    				}
    				catch (Throwable $e)
    				{
    				    echo getTimestamp().'Ошибка mainSearch ('.$tracker.'): '.$e->getMessage().$NL;
    				    Errors::setWarnings($tracker, 'engine_error');
    				}
    
    				$functionClass = NULL;
    				$functionEngine = NULL;
    			}
    			else
    				Errors::setWarnings($tracker, 'missing_files');
    		}
    		else
    			Errors::setWarnings($tracker, 'credential_miss');
    	}
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
	if ( ! empty($tempList))
	{
    	if (count($tempList) > 0)
    	    Sys::AddFromTemp($tempList);
    }
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