<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR."class/Notifier.class.php";
include_once ROOT_DIR."class/Database.class.php";
include_once ROOT_DIR."class/Deluge.class.php";
include_once ROOT_DIR."class/Transmission.class.php";
include_once ROOT_DIR."class/rain.tpl.class.php";

$settings = Database::getAllSetting();
foreach ($settings as $row)
    extract($row);

$contents = array();

if (Sys::checkInternet())
{
    $contents[] = array('text' => 'Подключение к интернету установлено.', 'error' => false);

    if (Sys::checkConfigExist())
    {
        $contents[] = array('text' => 'Конфигурационный файл существует и заполнен.', 'error' => false);

        if (Sys::checkCurl())
        {
            $contents[] = array('text' => 'Расширение cURL установлено.', 'error' => false);

            $torrentPath = ROOT_DIR.'torrents/';
            if (Sys::checkWriteToPath($torrentPath))
            {
                $contents[] = array('text' => 'Запись в директорию для torrent-файлов "'.$torrentPath.'" разрешена.', 'error' => false);
            }
            else
            {
                $contents[] = array('text' => 'Запись в директорию для torrent-файлов "'.$torrentPath.'" запрещена.',
                                    'error' => true);
            }

            $dir = ROOT_DIR;
            if (Sys::checkWriteToPath($dir))
            {
                $contents[] = array('text' => 'Запись в системную директорию "'.$dir.'" разрешена.', 'error' => false);
            }
            else
            {
                $contents[] = array('text' => 'Запись в системную директорию "'.$dir.'" запрещена.',
                                    'error' => true);
            }

            $contents[] = array('text' => 'Отправка тестовых уведомлений об обновлениях', 'error' => false);
            $result = Notifier::send('notification', date('Y-m-d H:i:s'), 'TorrentMonitor notifier', 'Тест уведомлений об обновлениях', '');
            $contents[] = array('text' => $result, 'error' => false);

            $contents[] = array('text' => 'Отправка тестовых уведомлений об ошибках', 'error' => false);
            $result = Notifier::send('warning', date('Y-m-d H:i:s'), 'TorrentMonitor notifier', 'Тест уведомлений об ошибках', '');
            $contents[] = array('text' => $result, 'error' => false);


            if ($torrentClient == 'Deluge')
            {
                $contents[] = array('text' => 'Проверка настроек Deluge', 'error' => false);
                $contents[] = Deluge::checkSettings();
            }
            elseif ($torrentClient == 'Transmission')
            {
                $contents[] = array('text' => 'Проверка настроек Transmission', 'error' => false);
                $contents[] = Transmission::checkSettings();
            }


            $trackers = Database::getTrackersList();
            foreach ($trackers as $tracker)
            {
                if (file_exists(ROOT_DIR.'trackers/'.$tracker.'.engine.php'))
                {
                    $contents[] = array('text' => 'Основной файл для работы с трекером "'.$tracker.'" найден.', 'error' => false);
                }
                else
                {
                    $contents[] = array('text' => 'Основной файл для работы с трекером "'.$tracker.'" не найден.',
                                        'error' => true);
                }

                if ($tracker == 'nnm-club.me' || $tracker == 'pornolab.net' || $tracker == 'rutracker.org' || $tracker == 'tapochek.net' || $tracker == 'tfile.me')
                {
                    if (file_exists(ROOT_DIR.'trackers/'.$tracker.'.search.php'))
                    {
                        $contents[] = array('text' => 'Дополнительный файл для работы с трекером "'.$tracker.'" найден.', 'error' => false);
                    }
                    else
                    {
                        $contents[] = array('text' => 'Дополнительный файл для работы с трекером "'.$tracker.'" не найден.',
                                            'error' => true);
                    }
                }

                if (Database::checkTrackersCredentialsExist($tracker))
                {
                    $contents[] = array('text' => 'Учётные данные для работы с трекером "'.$tracker.'" найдены.', 'error' => false);
                }
                else
                {
                    $contents[] = array('text' => 'Учётные данные для работы с трекером "'.$tracker.'" не найдены.',
                                        'error' => true);
                }

                if ($tracker == 'lostfilm.tv')
                    $page = 'https://www.lostfilm.tv/';
                elseif ($tracker == 'rutracker.org')
                    $page = 'http://rutracker.org/forum/index.php';
                else
                    $page = 'http://'.$tracker;

                if (Sys::checkavAilability($page))
                {
                    $contents[] = array('text' => 'Трекер "'.$tracker.'" доступен.', 'error' => false);
                }
                else
                {
                    $contents[] = array('text' => 'Трекер "'.$tracker.'" не доступен.',
                                        'error' => true);
                }
            }
        }
        else
        {
            $contents[] = array('text' => 'Для работы системы необходимо включить <a href="http://php.net/manual/en/book.curl.php">расширение cURL</a>.',
                                    'error' => true);
        }
    }
    else
    {
        $contents[] = array('text' => 'Для корректной работы необходимо внести изменения в конфигурационный файл.',
                                'error' => true);
    }
}
else
{
    $contents[] = array('text' => 'Отсутствует подключение к интернету.',
                            'error' => true);
}

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "contents", $contents );

$tpl->draw( 'check' );

?>
