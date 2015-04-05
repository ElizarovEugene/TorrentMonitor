<?php
define('ROOT_DIR', dirname(__FILE__).'/' );

session_start();

include_once ROOT_DIR."config.php";
include_once ROOT_DIR."class/System.class.php";
include_once ROOT_DIR."class/Database.class.php";
include_once ROOT_DIR."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

if (Sys::checkAuth())
{
    $errors = Database::getWarningsCount();
    
    $count = 0;
    if ( ! empty($errors))
        for ($i=0; $i<count($errors); $i++)
            $count += $errors[$i]['count'];
    
    $tpl = new RainTPL;
    $tpl->assign( "update"     , Sys::checkUpdate() );
    $tpl->assign( "version"    , Sys::version() );
    $tpl->assign( "error_count", $count );

    $content = $tpl->draw( 'main', true );
}
else
{
    $tpl = new RainTPL;
    $content = $tpl->draw( 'auth', true );
}

$tpl = new RainTPL;
$tpl->assign( "content", $content );
$tpl->assign( "title", 'TorrentMonitor' );
$tpl->draw( "index" );

?>
