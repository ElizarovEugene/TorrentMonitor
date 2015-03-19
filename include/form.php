<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
$torrent = Database::getTorrent($_GET['id']);

foreach ($torrent as $row)
{
	extract($row);
}
?>
<div id="notice_sub"></div>
<div align="right"><a href="#" onclick='$(".coverAll").hide();'><img src="img/delete.png" boder="0"></a></div>
<h2 class="monitoring-title">Редактировать</h2>
<form id="torrent_update">
    <input type="hidden" name="id" value="<?php echo $torrent[0]['id']?>"><br/>
    <input type="hidden" name="tracker" value="<?php echo $tracker?>"><br/>
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name" value="<?php echo $torrent[0]['name']?>"><br/>
        <span class="subinput-text">Необязательно</span>
    </p>
<?php 
if (isset($torrent_id) && $torrent_id != 0)
{
?>
    <p>
        <label class="label-name">Ссылка на тему</label>
<?php
if ($tracker == 'rutracker.org' || $tracker == 'nnm-club.me' || $tracker == 'tfile.me' || $tracker == 'torrents.net.ua' || $tracker == 'pornolab.net' || $tracker == 'rustorka.com')
    $tracker = 'http://'.$tracker.'/forum/viewtopic.php?t=';
elseif ($tracker == 'casstudio.tv' || $tracker == 'kinozal.tv'  || $tracker == 'animelayer.ru' || $tracker == 'tracker.0day.kiev.ua')
    $tracker = 'http://'.$tracker.'/details.php?id=';
elseif ($tracker == 'rutor.org')
    $tracker = 'http://alt.rutor.org/torrent/';
elseif ($tracker == 'anidub.com')
    $tracker = 'http://tr.anidub.com/';
?>
        <input type="text" name="url" value="<?php echo $tracker.$torrent_id?>">
        <span class="subinput-text">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</span>
    </p>
    <p>
        <label class="label-name">Обновлять заголовок автоматически</label>
        <input type="checkbox" name="update" id="update" <?php if ($auto_update) echo 'checked'?> ><br>
    </p>    
<?php
}

if (isset($hd) && $tracker == 'lostfilm.tv' || isset($hd) && $tracker == 'novafilm.tv' || isset($hd) && $tracker == 'baibako.tv' || isset($hd) && $tracker == 'newstudio.tv')
{
?>
    <p>
        <label class="label-name"></label>
<?php
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
?>
        <span id="changedField"><span class="quality"><?php echo $input?></span></span>
    </p>
<?php
}
?>
    <p>
        <label class="label-name">Директория для скачивания</label>
        <input type="text" name="path" id="path" value="<?php echo $path?>"><br>
        <span class="subinput-text">Например: /var/lib/transmission/downloads</span>
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
$(function() {
    var availableTags = [
    <?php
    $paths = Database::getPaths();
    if ( ! empty($paths))
    {
        for ($i=0; $i<count($paths); $i++)
            echo '"'.$paths[$i]['path'].'", ';
    }
    ?>
    ];
    $( "#path" ).autocomplete({
      source: availableTags
    });
});
</script>