<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));
    
$opts = stream_context_create(array(
    'http' => array(
        'timeout' => 1
        )
    ));
$xmlstr = @file_get_contents('http://korphome.ru/torrent_monitor/help.xml', false, $opts);
$xml = @simplexml_load_string($xmlstr);
if (false !== $xml)
{
    ?>
    <h2 class="settings-title">Помощь</h2>
    <div><?php echo $xml->help ?></div>
    <h2 class="settings-title">О проекте</h2>
    <div><?php echo $xml->about ?></div>
    <h2 class="settings-title">Разработчики</h2>
    <div><?php echo $xml->developers ?></div>
    <?php
}
else
{
    ?>
    <div>Не удалось загрузить файл help.xml</div>
    <?php
}
?>
<div class="clear-both"></div>