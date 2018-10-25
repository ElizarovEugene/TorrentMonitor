<?php
$dir = dirname(__FILE__).'/../';
include_once $dir.'config.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Database.class.php';
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

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

$i=0;

if (empty($torrents_list)) 
    echo 'Нет тем для мониторинга';

if ( ! is_array($torrents_list))
    echo '<div class="dberror">'.$torrents_list.'</div>';
else
{
?>
<table>
    <thead>
        <tr>
            <th>Трекер</th>
            <th>Название &nbsp;&nbsp;&nbsp;<span class='arr'><a href='action.php?action=order&order=name'>&#9650;</a></span></th>
            <th nowrap>Последнее обновление &nbsp;&nbsp;&nbsp;<span class='arr'><a href='action.php?action=order&order=date'>&#9650;</a></span>&nbsp;<span class='arr'><a href='action.php?action=order&order=dateDesc'>&#9660;</a></span></th>
            <th>Действие</th>
        </tr>
    </thead>
    <tbody>
	<?php
	foreach($torrents_list as $row)
	{
		extract($row);
		if ((++$i % 2)==0)
			$class = '#f1f4fd';
		else
			$class = '#ffffff';
	?>
        <tr>
            <td class='text-align-left' nowrap><span class='icon-torrent' style='background-image: url(img/<?php echo $tracker ?>.ico);'></span>
            <?php 
            if ($tracker == 'baibako.tv_forum')
                echo 'baibako.tv';
            else
                echo $tracker;
            ?>
            </td>
            <td class='text-align-left'>
    	  	<?php
            if ($pause)
                echo '<img src="img/pause.png" alt="Раздача на паузе">&nbsp;';
            if ($error)
                echo '<img src="img/error.png" alt="Есть ошибки">&nbsp;';
            if ($closed)
                echo '[Тема закрыта]&nbsp;';
    		if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    		{
    		?>
				<a href='
                <?php 
                if ($tracker == 'nnmclub.to')
                    echo 'https://';
                else
                    echo 'http://';
                echo $tracker ?>/forum/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
    		<?php
    		}
    		elseif ($tracker == 'kinozal.me'  || $tracker == 'tracker.0day.kiev.ua' || $tracker == 'tv.mekc.info')
    		{
            ?>
        	    <a href='http://<?php echo $tracker ?>/details.php?id=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
        	<?php	
    		}
    		elseif ($tracker == 'animelayer.ru' || $tracker == 'rutor.org')
    		{
        		if ($tracker == 'rutor.org')
        		    $tracker = 'rutor.info';
    		?>
    			<a href='http://<?php echo $tracker ?>/torrent/<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
    		<?php
    		}
    		elseif ($tracker == 'anidub.com')
    		{
	    	?>        		
	    		<a href='http://tr.anidub.com<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>	    	
	    	<?php        		
    		}
    		elseif ($tracker == 'riperam.org')
    		{
	    	?>        		
	    		<a href='http://riperam.org<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>	    	
	    	<?php        		
    		}
    		elseif ($tracker == 'baibako.tv_forum')
    		{
	    	?>        		
	    		<a href='http://baibako.tv/details.php?id=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>	    	
	    	<?php        		
    		}
    		elseif ($tracker == 'casstudio.tv' || $tracker == 'booktracker.org')
    		{
	    	?>        		
	    		<a href='http://<?php echo $tracker ?>/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
	    	<?php        		
    		}
    		else
    		{
                if ($hd == 1 && $tracker == 'lostfilm.tv' || $hd == 1 && $tracker == 'lostfilm-mirror')
                	echo '<img src="img/1080.png">';
                elseif ($hd == 1 && $tracker == 'baibako.tv' || $hd == 1 && $tracker == 'hamsterstudio.org' || $hd == 1 && $tracker == 'newstudio.tv')
                	echo '<img src="img/720.png">';
                elseif ($hd == 2 && $tracker == 'lostfilm.tv' || $hd == 2 && $tracker == 'lostfilm-mirror')
                	echo '<img src="img/720mp4.png">';
                elseif ($hd == 2 && $tracker == 'baibako.tv' || $hd == 2 && $tracker == 'hamsterstudio.org' || $hd == 2 && $tracker == 'newstudio.tv')
                    echo '<img src="img/1080.png">';
                else
                    echo '<img src="img/sd.png">';
    			echo '&nbsp;'.$name;
            }
    		?>
            <?php
            if (! empty($path)) echo '<br /><font size="1">Путь сохранения: '.$path.'</font>';
            ?>
    		</td>
            <td nowrap>
            <?php
            if ($timestamp == '0000-00-00 00:00:00' || $timestamp == NULL || $timestamp == '2000-01-01 00:00:00'  || $timestamp == '1970-01-01 00:00:00') {}
            else
            {
            	if ($type == 'RSS')
            	{
            	?>
            	<div onclick='expand("div<?php echo $id ?>")' class='cut' style='cursor: pointer;'>
            	<?php
            	}
            	else
            	{
            	?>
            	   <div class='cut'>
                	<?php
                	}
                	$date_update = $day.' '.Sys::dateNumToString($month).' '.$year.' '.$time;
                	$date = $day.'-'.$month.'-'.$year;
                	if (stripos($date, $date_today) !== FALSE)
                		echo '<u>'.$date_update.'</u>';
                	else
                		echo $date_update;
                	?>			
            		</div>
            	<?php
            	if ($timestamp != '0000-00-00 00:00:00' || $timestamp == NULL || $timestamp == '2000-01-01 00:00:00')
            	{
            		$season = substr($ep, 1, 2);
            		$episode = substr($ep, -2);

                	if ($type == 'RSS')
                	{
                	?>
            		<div id='div<?php echo $id ?>' class='result'>
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
            <td>
               <a href='#' class='edit' onclick='showForm(<?php echo $id?>)'></a>
               <a href='#' class='delete' onclick='del(<?php echo $id.',"'.htmlspecialchars($name, ENT_QUOTES).'"'?>)'></a>
            </td>
        </tr>
<?php 
	} 
?>
	</tbody> 
</table>

<div class='updatetime'>Последний запуск:
<?php
$lasrStart = @file_get_contents(dirname(__FILE__).'/../laststart.txt');
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
