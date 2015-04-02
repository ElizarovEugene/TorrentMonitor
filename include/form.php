<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir.'class/System.class.php';

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/Trackers.class.php";
include_once $dir."class/rain.tpl.class.php";

$torrent = Database::getTorrent($_GET['id']);

foreach ($torrent as $row)
{
    extract($row);
}

$torrent_url = Trackers::generateURL($tracker, $torrent_id);

if ($hd == 1 && $tracker == 'lostfilm.tv')
	$input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1" checked> Автовыбор HD 720/1080<br /><input type="radio" name="hd" value="2"> HD 720 MP4';
elseif ($hd == 1 && $tracker == 'baibako.tv' || $hd == 1 && $tracker == 'newstudio.tv' || $hd == 1 && $tracker == 'novafilm.tv')
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1" checked> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>';
elseif ($hd == 2 && $tracker == 'lostfilm.tv')
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="2" checked> HD 720 MP4<br /><input type="radio" name="hd" value="2"> HD 1080</span>';
elseif ($hd == 2 && $tracker == 'baibako.tv' || $hd == 2 && $tracker == 'newstudio.tv' || $hd == 2 && $tracker == 'novafilm.tv')
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2" checked> HD 1080</span>';
else
    $input = '<input type="radio" name="hd" value="0" checked> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>';

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "id", $id );
$tpl->assign( "tracker", $tracker );
$tpl->assign( "name", $name );
$tpl->assign( "tracker_type", Trackers::getTrackerType($tracker) );
$tpl->assign( "torrent_url", Trackers::generateURL($tracker, $torrent_id) );
$tpl->assign( "auto_update", $auto_update );
$tpl->assign( "input", $input );
$tpl->assign( "path", $path );
$tpl->assign( "paths", Database::getPaths() );

$tpl->draw( 'form' );

?>