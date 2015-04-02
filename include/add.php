<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Trackers.class.php";

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
            <?php
            $trackers = Trackers::getTrackersByType('series');
            foreach($trackers as $trackerData){
	    	?>        		
	    		<option value='<?php echo $trackerData['tracker'] ?>'><?php echo $trackerData['tracker'] ?></option>
	    	<?php        		
            }
            ?>
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
            <?php
            $trackers = Trackers::getTrackersByType('search');
            foreach($trackers as $trackerData){
	    	?>        		
	    		<option value='<?php echo $trackerData['tracker'] ?>'><?php echo $trackerData['tracker'] ?></option>
	    	<?php        		
            }
            ?>
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