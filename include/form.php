<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
$torrent = Database::getTorrent($_GET['id']);
$tracker = $torrent[0]['tracker'];
$hd = $torrent[0]['hd'];
?>
<div id="notice_sub"></div>
<div align="right"><a href="#" onclick='$(".coverAll").hide();'><img src="img/delete.png" boder="0"></a></div>
<h2 class="monitoring-title">Редактировать</h2>
<?php if ($torrent[0]['closed']) echo '<h3>Тема закрыта на форуме!</h3>'; ?>
<form id="torrent_update">
    <input type="hidden" name="id" value="<?php echo $torrent[0]['id']?>"><br/>
    <input type="hidden" name="tracker" value="<?php echo $tracker?>"><br/>
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name" value="<?php echo $torrent[0]['name']?>"><br/>
        <span class="subinput-text">Необязательно</span>
    </p>
<?php
if (isset($torrent[0]['torrent_id']) && $torrent[0]['torrent_id'] != NULL)
{
?>
    <p>
        <label class="label-name">Ссылка на тему</label>
<?php
if ($tracker == 'rutracker.org' || $tracker == 'nnmclub.to' || $tracker == 'tfile.cc' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    $tracker = 'http://'.$tracker.'/forum/viewtopic.php?t=';
elseif ($tracker == 'booktracker.org')
    $tracker = 'http://'.$tracker.'/viewtopic.php?t=';    
elseif ($tracker == 'casstudio.tv' || $tracker == 'kinozal.me' || $tracker == 'tracker.0day.kiev.ua' || $tracker == 'tv.mekc.info')
    $tracker = 'http://'.$tracker.'/details.php?id=';
elseif ($tracker == 'rutor.org')
    $tracker = 'http://rutor.org/torrent/';
elseif ($tracker == 'anidub.com')
    $tracker = 'http://tr.anidub.com';
elseif ($tracker == 'riperam.org')
    $tracker = 'http://riperam.org';
elseif ($tracker == 'animelayer.ru')
    $tracker = 'http://animelayer.ru/torrent/';
elseif ($tracker == 'baibako.tv_forum')
    $tracker = 'http://baibako.tv/details.php?id=';
?>
        <input type="text" name="url" value="<?php echo $tracker.$torrent[0]['torrent_id']?>">
        <span class="subinput-text">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</span>
    </p>
    <p>
        <label class="label-name">Обновлять заголовок автоматически</label>
        <input type="checkbox" name="update" id="update" <?php if ($torrent[0]['auto_update']) echo 'checked'?> ><br>
    </p>    
<?php
}

if (isset($hd) && $tracker == 'lostfilm.tv' || isset($hd) && $tracker == 'lostfilm-mirror' || isset($hd) && $tracker == 'baibako.tv' || isset($hd) && $tracker == 'newstudio.tv')
{
?>
    <p>
        <label class="label-name"></label>
<?php
if ($hd == 1 && $tracker == 'lostfilm.tv' || $hd == 1 && $tracker == 'lostfilm-mirror')
	$input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="2"> HD 720<br /><input type="radio" name="hd" value="1" checked> HD 1080';
elseif ($hd == 1 && $tracker == 'baibako.tv' || $hd == 1 && $tracker == 'newstudio.tv' || $hd == 1)
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1" checked> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>';
elseif ($hd == 2 && $tracker == 'lostfilm.tv' || $hd == 2 && $tracker == 'lostfilm-mirror')
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="2" checked> HD 720<br /><input type="radio" name="hd" value="1"> HD 1080</span>';
elseif ($hd == 2 && $tracker == 'baibako.tv' || $hd == 2 && $tracker == 'newstudio.tv' || $hd == 2)
    $input = '<input type="radio" name="hd" value="0"> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2" checked> HD 1080</span>';
elseif ($hd == 0 && $tracker == 'lostfilm.tv' || $hd == 0 && $tracker == 'lostfilm-mirror')
	$input = '<input type="radio" name="hd" value="0" checked> SD<br /><input type="radio" name="hd" value="2"> HD 720<br /><input type="radio" name="hd" value="1"> HD 1080';    
else
    $input = '<input type="radio" name="hd" value="0" checked> SD<br /><input type="radio" name="hd" value="1"> HD 720<br /><input type="radio" name="hd" value="2"> HD 1080</span>';
?>
        <span id="changedField"><span class="quality"><?php echo $input?></span></span>
    </p>
<?php
}
?>
    <p>
        <label class="label-name">Директория для скачивания</label>
        <input type="text" name="path" id="path" value="<?php echo $torrent[0]['path']?>"><br>
        <span class="subinput-text">Например: /var/lib/transmission/downloads</span>
    </p>
    <p>
        <label class="label-name">Выполнить скрипт</label>
        <input type="text" name="script" id="script" value="<?php echo $torrent[0]['script']?>"><br>
        <span class="subinput-text">Например: /home/user/check.sh</span>
    </p>
    <p>
        <label class="label-name">Поставить раздачу на паузу</label>
        <input type="checkbox" name="pause" id="pause" <?php if($torrent[0]['pause']) echo 'checked';?> ><br>
    </p>
    <p>
        <label class="label-name">Сбросить время последнего обновления</label>
        <input type="checkbox" name="reset" id="reset"><br>
    </p>
	</div>
    <button class="form-button">Сохранить</button>
</form>
<div class="clear-both"></div>
<script src="js/user-func.js"></script>
<script>
$( function() {
    var availableTags = [
    <?php
	$mainPath = Database::getSetting('pathToDownload');
	echo '"'.$mainPath.'",';
    $paths = Database::getPaths();
    if ( ! empty($paths))
    {
        for ($i=0; $i<count($paths); $i++)
            echo '"'.$paths[$i]['path'].'", ';
    }
    ?>
    ];
    $( "#path" ).autocomplete({
		appendTo: "#torrent_update",
		source: availableTags
    });
});
</script>