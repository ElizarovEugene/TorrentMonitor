<?php

$dir = dirname(__FILE__)."/../";
include_once $dir."notifiers/Notifier.class.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
$dir = dirname(__FILE__)."/";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>
<table class="test">
    <thead>
        <tr>
            <th>Основные настройки</th>
        </tr>
    </thead>
    <tbody>
<?php
if (Sys::checkInternet())
{
?>
        <tr>
            <td>Подключение к интернету установлено.</td>
        </tr>
    <?php
    if (Sys::checkConfigExist())
    {
    ?>
        <tr>
            <td>Конфигурационный файл существует и заполнен.</td>
        <tr>
        <?php
        if (Sys::checkCurl())
        {
        ?>
        <tr>
            <td>Расширение cURL установлено.</td>
        <tr>
            <?php
            $torrentPath = str_replace('include/', 'torrents/', $dir);
            if (Sys::checkWriteToPath($torrentPath))
            {
            ?>
        <tr>
            <td>Запись в директорию для torrent-файлов <?php echo $torrentPath?> разрешена.</td>
        <tr>
            <?php
            }
            else
            {
            ?>
        <tr>
            <td class="test-error">Запись в директорию для torrent-файлов <?php echo $torrentPath?> запрещена.</td>
        <tr>
            <?php
            }
            $dir = str_replace('include/', '', $dir);
            if (Sys::checkWriteToPath($dir))
            {
            ?>
        <tr>
            <td>Запись в системную директорию <?php echo $dir?> разрешена.</td>
        <tr>
            <?php
            }
            else
            {
            ?>
        <tr>
            <td class="test-error">Запись в системную директорию <?php echo $dir?> запрещена.</td>
        <tr>
            <?php
            }

            if (Database::getSetting('send'))
            {
                if (Database::getSetting('sendUpdate'))
                {
                    ?>
        <tr>
            <td>Отправляем тестовые уведомления об обновлении.<br />
                    <?php
                    $result = Notifier::send('notification', date('Y-m-d H:i:s'), 'Test', 'Torrent Monitor. Тест уведомления об обновлении.', '');
                    echo $result;
                    ?>
          </td>
        <tr>
                    <?php
                }

                if (Database::getSetting('sendWarning'))
                {
                    ?>
        <tr>
            <td>Отправляем тестовые уведомления об ошибках.<br />
                    <?php
                    $result = Notifier::send('warning', date('Y-m-d H:i:s'), 'Test', 'Torrent Monitor. Тест уведомления об ошибках.');
                    echo $result;
                    ?>
          </td>
        <tr>
                    <?php
                }
            }

            $trackers = Database::getTrackersList();
            foreach ($trackers as $tracker)
            {
                if (file_exists($dir.'trackers/'.$tracker.'.engine.php'))
                {
                ?>
        <tr>
            <td>Основной файл для работы с трекером <?php echo $tracker?> найден.</td>
        <tr>
                <?php
                }
                else
                {
                ?>
        <tr>
            <td class="test-error">Основной файл для работы с трекером <?php echo $tracker?> не найден.</td>
        <tr>
                <?php
                }
                if ($tracker == 'nnm-club.me' || $tracker == 'pornolab.net' || $tracker == 'rutracker.org' || $tracker == 'tapochek.net' || $tracker == 'tfile.me')
                {
                    if (file_exists($dir.'trackers/'.$tracker.'.search.php'))
                    {
                    ?>
        <tr>
            <td>Дополнительный файл для работы с трекером <?php echo $tracker?> найден.</td>
        <tr>
                    <?php
                    }
                    else
                    {
                    ?>
        <tr>
            <td class="test-error">Дополнительный файл для работы с трекером <?php echo $tracker?> не найден.</td>
        <tr>
                    <?php
                    }
                }

                if (Database::checkTrackersCredentialsExist($tracker))
                {
                ?>
        <tr>
            <td>Учётные данные для работы с трекером <?php echo $tracker?> найдены.</td>
        <tr>
                <?php
                }
                else
                {
                ?>
        <tr>
            <td class="test-error">Учётные данные для работы с трекером <?php echo $tracker?> не найдены.</td>
        <tr>
                <?php
                }
                if ($tracker == 'lostfilm.tv')
                    $pre = 'www.';
                else
                    $pre = '';
                $page = 'http://'.$pre.$tracker;

                if (Sys::checkavAilability($page))
                {
                ?>
        <tr>
            <td>Трекер <?php echo $tracker?> доступен.</td>
        <tr>
                <?php
                }
                else
                {
                ?>
        <tr>
            <td class="test-error">Трекер <?php echo $tracker?> не доступен.</td>
        <tr>
                <?php
                }
            }
        }
        else
        {
        ?>
        <tr>
            <td class="test-error">Для работы системы необходимо включить <a href="http://php.net/manual/en/book.curl.php">расширение cURL</a>.</td>
        <tr>
        <?php
        }
    }
    else
    {
    ?>
        <tr>
            <td class="test-error">Для корректной работы необходимо внести изменения в конфигурационный файл.</td>
        <tr>
    <?php
    }
}
else
{
?>
        <tr>
            <td class="test-error">Отсутствует подключение к интернету.</td>
        </tr>
<?php
}
?>
    </tbody>
</table>
<div class="clear-both"></div>
