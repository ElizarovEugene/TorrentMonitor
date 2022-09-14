<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Errors.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$count = Database::getWarningsCount();
if ( $count != NULL)
{
?>
<table class="warning_table" border="0" cellpadding="0" cellspacing="1">
	<thead> 
	<tr>
		<th width="20%">Время</th>
		<th width="15%">Трекер</th>
		<th width="65%">Причина</th>
 	</tr>
	</thead>
	<?php
	for ($i=0; $i<count($count); $i++)
	{
		$errors = Database::getWarningsList($count[$i]['where']);
        if ( $errors != NULL)
        {
            $countErrorsByTracker = $count[$i]['count'];
            if ($countErrorsByTracker > 5)
            {
                for ($x=0; $x<2; $x++)
                {
                    if (($x % 2)==0)
                        $class = "second";
                    else
                        $class = "first";

                    $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                    ?>
                <tr class="<?php echo $class ?>">
                    <td align="center"><?php echo $date ?></td>
                    <td><?php echo $errors[$x]['where'] ?></td>
                    <td><?php echo Errors::getWarning($errors[$x]['reason']) ?></td>
                </tr>
                <?php
                }
                ?>
                <tr class="second">
                    <td colspan="3" align="center">...</td>
                </tr>
                <?php
                $errors = array_slice($errors, $countErrorsByTracker-2, 2);
                for ($x=0; $x<2; $x++)
                {
                    if (($x % 2)==0)
                        $class = "first";
                    else
                        $class = "second";

                    $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                    ?>
                <tr class="<?php echo $class ?>">
                    <td align="center"><?php echo $date ?></td>
                    <td><?php echo $errors[$x]['where'];?></td>
                    <td><?php echo Errors::getWarning($errors[$x]['reason'])?>
                    <?php
                    if ($errors[0]['id'] != NULL)
                    {
                        $torrent = Database::getTorrent($errors[$x]['id']);
                        $tracker = $torrent[0]['tracker'];
                        $name = $torrent[0]['name'];
                        $torrent_id = $torrent[0]['torrent_id'];
                        echo '<br />Раздача: ';
                        if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
                        {
                        ?>
                            <a href='http://<?php echo $tracker ?>/forum/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
                        <?php
                        }
                        elseif ($tracker == 'booktracker.org')
                        {
                        ?>
                            <a href='http://<?php echo $tracker ?>/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
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
                            <a href='http://tr.anidub.com/<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
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
                        elseif ($tracker == 'casstudio.tk' || $tracker == 'booktracker.org')
                        {
                        ?>
                            <a href='http://<?php echo $tracker ?>/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
                        <?php
                        }
                    }?>
                    </td>
                </tr>
                <?php
                }
            }
        }
		else
		{
			for ($x=0; $x<count($errors); $x++)
			{
				if (($x % 2)==0)
					$class = "second";
				else
					$class = "first";
				
				$date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
				?>
			<tr class="<?php echo $class ?>">
				<td align="center"><?php echo $date ?></td>
				<td><?php echo $errors[$x]['where'] ?></td>
				<td><?php echo Errors::getWarning($errors[$x]['reason'])?>
				<?php
				if ($errors[$x]['id'] != NULL)
				{
    				$torrent = Database::getTorrent($errors[$x]['id']);
    				$tracker = $torrent[0]['tracker'];
    				$name = $torrent[0]['name'];
                    $torrent_id = $torrent[0]['torrent_id'];
    				echo '<br />Раздача: ';
            		if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
            		{
            		?>
        				<a href='http://<?php echo $tracker ?>/forum/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
            		<?php
            		}
            		elseif ($tracker == 'kinozal.tv'  || $tracker == 'tracker.0day.kiev.ua' || $tracker == 'tv.mekc.info')
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
        	    		<a href='http://tr.anidub.com/<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>	    	
        	    	<?php        		
            		}
            		elseif ($tracker == 'casstudio.tk' || $tracker == 'booktracker.org')
            		{
        	    	?>        		
        	    		<a href='http://<?php echo $tracker ?>/viewtopic.php?t=<?php echo $torrent_id ?>' target='_blank'><?php echo $name ?></a>
    				<?php
                    }
				}?>				    
				</td>
			</tr>
		 	<?php
			}
		}
	}
}
?>
</table>