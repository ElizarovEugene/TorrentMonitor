<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<rss version="0.91">
<channel>
<title>TorrentMonitor RSS</title>
<?php
$dir = dirname(__FILE__)."/";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";

//$url = $_SERVER["SERVER_NAME"].preg_replace('rss.php', '', $_SERVER["SCRIPT_NAME"]);
$url = $_SERVER[HTTP_HOST].preg_replace('/\/rss.php/', '', $_SERVER[REQUEST_URI]);
?>
<link>http://<?php echo $url?>/rss.php</link>
<lastBuildDate>Fri, 24 Oct 2014 19:02:54 +0000</lastBuildDate>
<language>ru</language>
<?php
$torrents_list = Database::getTorrentsList('dateDesc');
foreach($torrents_list as $row)
{
    extract($row);
    ?>
<item>
    <title><?php echo $name?></title>
    <pubDate><?php echo $timestamp?></pubDate>
    <?php
    $season = substr($ep, 1, 2);
	$episode = substr($ep, -2);
	if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.me' || $tracker == 'rutor.org' || $tracker == 'tfile.me' || $tracker == 'kinozal.tv' || $tracker == 'anidub.com' || $tracker == 'casstudio.tv'  || $tracker == 'animelayer.ru' || $tracker == 'tracker.0day.kiev.ua' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
	{
	    $name = $torrent_id;
	    $amp = NULL;
	    $app = NULL;
    }
    else
    {
        $name = $name;
        if ($hd == 1 || $hd == 3)
    		$amp = 'HD';
    	elseif ($hd == 2)
    		$amp = 'MP4';
    	else
    		$amp = NULL;
        $app = '.S'.$season.'E'.$episode.'.';
    }
    $file = str_replace(' ', '.', $name).$app.$amp;
    $link = 'http://'.$url.'/files/['.$tracker.']_'.$file.'.torrent';
    ?>
    <link><?php echo $link?></link>
</item>
<?php
}
?>
</channel>
