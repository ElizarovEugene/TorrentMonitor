<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

?>
<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#health" /></svg> Тестирование</div>
</div>

<div class="check">
    <div class="check-title">Основные настройки</div>

<?php if (Sys::checkInternet()) { ?>
    <div class="check-item">Подключение к интернету установлено.</div>
<?php } else { ?>
    <div class="check-item --error">Отсутствует подключение к интернету.</div>
<?php die; } ?>

<?php if (Sys::checkConfigExist()) { ?>
    <div class="check-item">Конфигурационный файл существует.</div>
<?php } else { ?>
    <div class="check-item --error">Для корректной работы необходимо внести изменения в конфигурационный файл.</div>
<?php die; } ?>

<?php if (Sys::checkCurl()) { ?>
    <div class="check-item">Расширение cURL установлено.</div>
<?php } else { ?>
    <div class="check-item --error">Для работы системы необходимо включить <a href="http://php.net/manual/en/book.curl.php">расширение cURL</a>.</div>
<?php die; } ?>

<?php
$torrentPath = str_replace('class/../', '', $dir).'torrents/';
if (Sys::checkWriteToPath($torrentPath)) { ?>
    <div class="check-item">Запись в директорию для torrent-файлов <strong><?= $torrentPath ?></strong> разрешена.</div>
<?php } else { ?>
    <div class="check-item --error">Запись в директорию для torrent-файлов <strong><?= $torrentPath ?></strong> запрещена.</div>
<?php die; } ?>

<?php
$dir = str_replace('include', '', dirname(__FILE__));
if (Sys::checkWriteToPath($dir)) { ?>
    <div class="check-item">Запись в системную директорию <strong><?= $dir ?></strong> разрешена.</div>
<?php } else { ?>
    <div class="check-item --error">Запись в системную директорию <strong><?= $dir ?></strong> запрещена.</div>
<?php die; } ?>

</div>
<div class="check">
    <div class="check-title">Настройки трекеров</div>

<?php
$trackers = Database::getTrackersList();
foreach ($trackers as $tracker)
{
    ?>
    <div class="check-subtitle"><?= $tracker ?></div>
    <?php
    if (file_exists($dir.'trackers/'.$tracker.'.engine.php')) { ?>
    <div class="check-item">Основной файл для работы с трекером <strong><?= $tracker ?></strong> найден.</div>
    <?php } else { ?>
    <div class="check-item --error">Основной файл для работы с трекером <strong><?= $tracker ?></strong> не найден.</div>
    <?php } ?>

    <?php if ($tracker == 'nnmclub.to' || $tracker == 'pornolab.net' || $tracker == 'rutracker.org' || $tracker == 'tapochek.net' || $tracker == 'tfile.cc') {
        if (file_exists($dir.'trackers/'.$tracker.'.search.php')) { ?>
    <div class="check-item">Дополнительный файл для работы с трекером <strong><?= $tracker ?></strong> найден.</div>
        <?php } else { ?>
    <div class="check-item --error">Дополнительный файл для работы с трекером <strong><?= $tracker ?></strong> не найден.</div>
        <?php
        }
    } ?>

    <?php if ($tracker == 'lostfilm-mirror' || $tracker == 'rutor.org' || $tracker == 'tfile.cc') { ?>
    <div class="check-item">Учётные данные для работы с трекером <strong><?= $tracker ?></strong> не требуются.</div>
    <?php } elseif (Database::checkTrackersCredentialsExist($tracker)) { ?>
    <div class="check-item">Учётные данные для работы с трекером <strong><?= $tracker ?></strong> найдены.</div>
    <?php } else { ?>
    <div class="check-item --error">Учётные данные для работы с трекером <strong><?= $tracker ?></strong> не найдены.</div>
    <?php } ?>

    <?php
    if ($tracker == 'baibako.tv_forum')
        $page = 'http://baibako.tv/';
    elseif ($tracker == 'lostfilm.tv')
        $page = 'https://www.lostfilm.tv/';
    elseif ($tracker == 'lostfilm-mirror')
        $page = 'https://rss.bzda.ru/rss.xml';
    elseif ($tracker == 'nnmclub.to')
        $page = 'https://nnmclub.to/forum/index.php';
    elseif ($tracker == 'rutor.org')
        $page = 'http://rutor.info/';
    elseif ($tracker == 'rutracker.org')
        $page = 'http://rutracker.org/forum/index.php';
    else
        $page = 'http://'.$tracker;
    if (Sys::checkavAilability($page))
    {
    ?>
    <div class="check-item">Трекер <strong><?= $tracker ?></strong> доступен.</div>
    <?php } else { ?>
    <div class="check-item --error">Трекер <strong><?= $tracker ?></strong> не доступен.</div>
    <?php
    } ?>
<?php } ?>
</div>
