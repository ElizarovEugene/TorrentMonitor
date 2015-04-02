<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR.'class/System.class.php';

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR.'class/Database.class.php';
include_once ROOT_DIR.'class/Trackers.class.php';
include_once ROOT_DIR."class/rain.tpl.class.php";

$date_today = date('d-m-Y');

if (isset($_COOKIE['order']))
{
    if ($_COOKIE['order'] == 'date')
        $torrents_list = Database::getTorrentsList('date');
    elseif ($_COOKIE['order'] == 'dateDesc')
        $torrents_list = Database::getTorrentsList('dateDesc');
}
else
    $torrents_list = Database::getTorrentsList('name');

$contents = array();

if ( ! empty($torrents_list))
{
    foreach($torrents_list as $row)
    {
        extract($row);
        $tracker_type = Trackers::getTrackerType($tracker);
        $quality_icon = '';
        
        if ($tracker_type == 'series') {
            if ($hd == 1 && $tracker == 'lostfilm.tv')
                $quality_icon = '<img src="img/720.png">&nbsp;<img src="img/1080.png">';
            elseif ($hd == 1 && $tracker == 'baibako.tv' || $hd == 1 && $tracker == 'newstudio.tv' || $hd == 1 && $tracker == 'novafilm.tv')
                $quality_icon = '<img src="img/720.png">';
            elseif ($hd == 2 && $tracker == 'lostfilm.tv')
                $quality_icon = '<img src="img/720.png">';
            elseif ($hd == 2 && $tracker == 'baibako.tv' || $hd == 2 && $tracker == 'newstudio.tv' || $hd == 2 && $tracker == 'novafilm.tv')
                $quality_icon = '<img src="img/1080.png">';
            else
                $quality_icon = '<img src="img/sd.png">';
        }
    
        if ( !($timestamp == '0000-00-00 00:00:00' || $timestamp == NULL)) {
            $date_update = $day.' '.Sys::dateNumToString($month).' '.$year.' '.$time;
            $date = $day.'-'.$month.'-'.$year;
            if (stripos($date, $date_today) !== FALSE)
                $date_update = '<u>'.$date_update.'</u>';
                
            if ($timestamp != '0000-00-00 00:00:00')
            {
                $season = substr($ep, 1, 2);
                $episode = substr($ep, -2);
            }
        }
        else {
            $date_update = '';
            $season = '';
            $episode = '';
        }
    
        $contents[] = array('tracker' => $tracker,
                            'name' => $name,
                            'id' => $id,
                            'torrent_url' => Trackers::generateURL($tracker, $torrent_id),
                            'quality_icon' => $quality_icon,
                            'path' => $path,
                            'tracker_type' => $tracker_type,
                            'date_update' => $date_update,
                            'season' => $season,
                            'episode' => $episode,
                      );
    }
}

$lasrStart = @file_get_contents(ROOT_DIR.'laststart.txt');
if ( ! empty($lasrStart))
{
    $date = explode('-', $lasrStart);
    $lasrStart = $date[0].' '.Sys::dateNumToString($date[1]).' '.$date[2];
}
else
    $lasrStart = 'Ещё не производился.';

// заполнение шаблона
raintpl::configure("root_dir", ROOT_DIR );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "lasrStart", $lasrStart );
$tpl->assign( "contents", $contents );

$tpl->draw( 'show_table' );
?>
