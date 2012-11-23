<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta charset="utf-8">
    <title>Мониторинг torrent трекеров</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=1000">
    <link rel="stylesheet" href="pages/styles.css">
    <!--[if lt IE 9]><script src="js/html5shiv-3.5.min.js"></script><![endif]-->
</head>
<body>
<?php
//Проверка обновления
$dir = __DIR__.'/';
include_once $dir.'../class/System.class.php';
$update = Sys::checkUpdate();
if ($update)
{
?>
<div class="update">
	Доступна новая версия TorrentMonitor<br>
	Пожалуйста <a href="http://blog.korphome.ru/torrentmonitor/">обновитесь</a>!
</div>
<?php
}
?>
<div id="notice"></div>