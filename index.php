<?php
$dir = dirname(__FILE__).'/' ;

session_start();

include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", $dir );
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
$tpl->assign( "title"  , 'Мониторинг torrent трекеров' );
$tpl->assign( "content", $content );
$tpl->draw( "index" );

?>
