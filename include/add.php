<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR."class/Database.class.php";
include_once ROOT_DIR."class/rain.tpl.class.php";

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;

$paths = Database::getPaths();
$tpl->assign( "series_trackers", Trackers::getTrackersByType('series') );
$tpl->assign( "search_trackers", Trackers::getTrackersByType('search') );
$tpl->assign( "paths", $paths );

$tpl->draw( 'add' );
?>