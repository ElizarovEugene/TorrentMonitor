<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>

<h2 class="monitoring-title">Добавить тему</h2>
<form id="torrent_add">
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name"><br/>
        <span class="subinput-text">Необязательно</span>
    </p>
    <p>
        <label class="label-name">Ссылка на тему</label>
        <input type="text" name="url" >
        <span class="subinput-text">Пример: http://rutracker.org/forum/viewtopic.php?t=4201572</span>
    </p>
    <div onclick='expand("divDop")' class='cutLink' style='cursor: pointer;'>Дополнительные параметры</div>
    <div id='divDop' class='result'>
    <p>
        <label class="label-name">Директория для скачивания</label>
        <input type="text" name="path" id="path"><br>
        <span class="subinput-text">Например: /var/lib/transmission/downloads</span>
    </p>
    <p>
        <label class="label-name">Обновлять заголовок автоматически</label>
        <input type="checkbox" name="update_header" id="update_header"><br>
    </p>
	</div>
    <button class="form-button">Добавить</button>
</form>
<br/>
<br/>

<h2 class="monitoring-title">Добавить сериал</h2>
<form id="serial_add">
    <p>
        <label class="label-name">Трекер</label>
        <select id="tracker" name="tracker" onchange="changeField()">
            <option></option>
            <option value="baibako.tv">baibako.tv</option>
            <option value="hamsterstudio.org">hamsterstudio.org</option>
            <option value="lostfilm.tv">lostfilm.tv</option>
            <option value="lostfilm-mirror">lostfilm-mirror</option>
            <option value="newstudio.tv">newstudio.tv</option>
        </select>
    </p>
    <p>
        <label class="label-name">Название</label>
        <input type="text" name="name"><br/>
        <span class="subinput-text">На английском языке<br/>Пример: House, Lie to me</span>
    </p>
    <p>
        <label class="label-name"></label>
        <span id="changedField"></span>
    </p>
    <div onclick='expand("divDop2")' class='cutLink' style='cursor: pointer;'>Дополнительные параметры</div>
    <div id='divDop2' class='result'>
    <p>
        <label class="label-name">Директория для скачивания</label>
        <input type="text" name="path" id="path2"><br>
        <span class="subinput-text">Например: /var/lib/transmission/downloads</span>
    </p>
	</div>    
    <button class="form-button">Добавить</button>
</form>
<br/>
<br/>

<h2 class="monitoring-title">Добавить пользователя</h2>
<form id="user_add">
    <p>
        <label class="label-name">Трекер</label>
        <select name="tracker">
            <option></option>
            <option>booktracker.org</option>
            <option>nnmclub.to</option>
            <option>pornolab.net</option>
            <option>rutracker.org</option>
            <option>tfile.cc</option>
        </select>
    </p>
    <p>
        <label class="label-name">Имя</label>
        <input type="text" name="name">
        <span class="subinput-text">Пример: KorP</span>
    </p>
    <button class="form-button">Добавить</button>
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
    $( "#path2" ).autocomplete({
      source: availableTags
    });
});
</script>