<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR."class/rain.tpl.class.php";

// заполнение раздело помощи
// возможно имеет смысл перенести справку в xml файл
$opts = stream_context_create(array(
    'http' => array(
        'timeout' => 1
        )
    ));
$xmlstr = @file_get_contents('http://korphome.ru/torrent_monitor/help.xml', false, $opts);
$xml = @simplexml_load_string($xmlstr);
if (false !== $xml)
{
    //Помощь
    $contents[] = array( 'title' => 'Помощь', 'content' => $xml->help,);
    //О проекте
    $contents[] = array( 'title' => 'О проекте', 'content' => $xml->about,);
    //Разработчики
    $contents[] = array( 'title' => 'Разработчики', 'content' => $xml->developers,);
}
else
    $contents[] = array( 'title' => 'Помощь', 'content' => 'Не удалось загрузить файл help.xml',);

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "contents", $contents );

$tpl->draw( 'help' );
?>