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
include_once $dir.'class/Trackers.class.php';

header('Content-Type: text/html; charset=utf-8');

$debug = Database::getSetting('debug');

if ($debug)
    $time_start_full = microtime(true);

if (Sys::checkConfig())
{
    if (Sys::checkCurl())
    {
        $torrentsList = Database::getTorrentsList('name');
        $count = count($torrentsList);
        
        echo 'Опрос новых раздач на трекерах:'."\r\n".'<br />';
        if ($debug)
            $time_start_overall = microtime(true);
        
        for ($i=0; $i<$count; $i++)
        {
            $tracker = $torrentsList[$i]['tracker'];
            if (Database::checkTrackersCredentialsExist($tracker))
            {
                if (Trackers::moduleExist($tracker, 'engine'))
                {
                    Database::clearWarnings('system');
                    
                    echo $torrentsList[$i]['name'].' на трекере '.$tracker."\r\n".'<br />';
                    
                    if ($debug)
                        $time_start = microtime(true);
                    
                    Trackers::checkUpdate($tracker, $torrentsList[$i], 'engine');
                    
                    if ($debug)
                    {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;
                        echo 'Время выполнения: '.$time."\r\n".'<br />';
                    }
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
                if (Trackers::moduleExist($tracker, 'search'))
                {
                    Database::clearWarnings('system');
                    
                    echo 'Пользователь '.$usersList[$i]['name'].' на трекере '.$tracker."\r\n".'<br />';
                    
                    if ($debug)
                        $time_start = microtime(true);
                    
                    Trackers::checkUpdate($tracker, $torrentsList[$i], 'search');
                    
                    if ($debug)
                    {
                        $time_end = microtime(true);
                        $time = $time_end - $time_start;
                        echo 'Время выполнения: '.$time."\r\n".'<br />';
                    }
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

if ($debug)
{
    $time_end_full = microtime(true);
    $time = $time_end_full - $time_start_full;
    echo 'Общее время работы скрипта: '.$time."\r\n".'<br />';
}
?>