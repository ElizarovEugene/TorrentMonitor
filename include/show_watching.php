<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR."class/Database.class.php";
include_once ROOT_DIR."class/rain.tpl.class.php";

$contents = array();

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$users = Database::getUserToWatch();
if ( ! empty($users))
{
    for ($i=0; $i<count($users); $i++)
    {
        $tpl = new RainTPL;
        $tpl->assign( "user", $users[$i] );
        $tpl->assign( "thremes", $thremes );
        
        $contents[] = $tpl->draw( 'show_watching_user', true );
        $thremes = Database::getThremesFromBuffer($users[$i]['id']);
    }
}
else
{
    $contents[] = "Нет пользователей для мониторинга.";
}

$tpl = new RainTPL;
$tpl->assign( "contents", $contents );

$tpl->draw( 'show_watching' );