<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

$date_today = date("d-m-Y");

@session_start();
if (isset($_SESSION['order']) && ($_SESSION['order'] == "date"))
{
	$torrents_list = Database::getTorrentsList('date');
	$name_href = true;
	$date_href = false;
}
else
{
	$torrents_list = Database::getTorrentsList('name');
	$name_href = false;
	$date_href = true;
}

$i=0;

if ( ! empty($torrents_list))
{
?>
<table>
    <thead>
        <tr>
            <th>Трекер</th>
            <th>Название &nbsp;&nbsp;&nbsp;<span class="arr"><a href="action.php?action=order&order=name">▲</a></span></th>
            <th>Последнее обновление &nbsp;&nbsp;&nbsp;<span class="arr"><a href="action.php?action=order&order=date">▲</a></span></th>
            <th>Действие</th>
        </tr>
    </thead>
    <tbody>
	<?php
	foreach($torrents_list as $row)
	{
		extract($row);
		if ((++$i % 2)==0)
			$class = "#f1f4fd";
		else
			$class = "#ffffff";
	?>
        <tr>
            <td class="text-align-left"><span class="icon-torrent" style="background-image: url(img/<?php echo $tracker ?>.ico);"></span><?php echo $tracker ?></td>
            <td class="text-align-left">
    	  	<?php 
    		if ($tracker == "rutracker.org" || $tracker == "nnm-club.ru" || $tracker == "tfile.me")
    		{
    		?>
				<a href="http://www.<?php echo $tracker ?>/forum/viewtopic.php?t=<?php echo $torrent_id ?>" target="_blank"><?php echo $name ?></a>
    		<?php
    		}
    		elseif ($tracker == "rutor.org")
    		{
    		?>
    			<a href="http://rutor.org/torrent/<?php echo $torrent_id ?>/" target="_blank"><?php echo $name ?></a>
    		<?php
    		}
    		else
    			echo $name;

    		if ($hd == 1)
    			echo '&nbsp;(HD)';
    		if ($hd == 2)
    			echo '&nbsp;(MP4)';
    		?>
    		</td>
            <td>
            <?php
            if ($timestamp == "0000-00-00 00:00:00" || $timestamp == NULL) {}
            else
            {
            	if ($tracker != "rutracker.org" && $tracker != "nnm-club.ru" && $tracker != "rutor.org")
            	{
            	?>
            	<div onclick="expand('div<?php echo $id ?>')" class="cut" style="cursor: pointer;">
            	<?php
            	}
            	else
            	{
            	?>
            	   <div class="cut">
                	<?php
                	}
                	$date_update = $day." ".Sys::dateNumToString($month)." ".$year." ".$time;
                	$date = $day.'-'.$month.'-'.$year;
                	if (stripos($date, $date_today) !== FALSE)
                		echo "<u>{$date_update}</u>";
                	else
                		echo $date_update;
                	?>			
            		</div>
            	<?php
            	if ($timestamp != "0000-00-00 00:00:00")
            	{
            		$season = substr($ep, 1, 2);
            		$episode = substr($ep, -2);

                	if ($tracker != "rutracker.org" && $tracker != "nnm-club.ru")
                	{
                	?>
            		<div id="div<?php echo $id ?>" class="result">
            			Последняя скачаная серия:<br>
            			Сезон <?php echo $season ?>, эпизод <?php echo $episode ?>
            		</div>
            		<?php
            		}
            	}
            	?>
            	</div>
            <?php	
            }
            ?>          
            </td>
            <td><a href="#" class="delete" onclick="del(<?php echo $id?>)"></td>
        </tr>
<?php 
	} 
?>
	</tbody> 
</table>

<div class="update">Последний запуск:
<?php
$lasrStart = @file_get_contents(__DIR__.'/../laststart.txt');
if ( ! empty($lasrStart))
{
	$date = explode('-', $lasrStart);
	echo $date[0].' '.Sys::dateNumToString($date[1]).' '.$date[2];
}
else
	echo 'Ещё не производился.';
?>
</div>
<?php 
} 
?>