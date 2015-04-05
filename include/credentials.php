<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$credential = Database::getAllCredentials();
$trackers = Database::getTrackersList();
?>
<h2 class="account-title">Редактировать учетные данные</h2>
<p>
	<label class="label-name">Трекер</label>
	<select onchange="changefunc()" id="selectfunc">
	    <option></option>
        <?php
		for ($i=0; $i<count($trackers); $i++)
		{
		?>
		<option value="<?php echo $trackers[$i] ?>"><?php echo $trackers[$i] ?></option>
		<?php
		}
		?>
	</select>
</p>    
    	<?php
    	for ($i=0; $i<count($credential); $i++)
    	{
    	?>
<div id="<?php echo $credential[$i]['tracker'] ?>_label" class="result">
<form id="credential">
    <input type="hidden" name="id" value="<?php echo $credential[$i]['id'] ?>">
	<p>
	   <label class="label-name">Логин</label>
	   <input type="text" name="log" value="<?php echo $credential[$i]['login'] ?>">
    </p>
	<p>
	   <label class="label-name">Пароль</label>
	   <input type="password" name="pass" value="<?php echo $credential[$i]['password'] ?>">
    </p>
    <?php if ($credential[$i]['tracker'] == 'baibako.tv')
    {
    ?>
    <p>
	   <label class="label-name">Passkey</label>
	   <input type="text" name="passkey" value="<?php echo $credential[$i]['passkey'] ?>">
    </p>    
    <?php
    }
    ?>
	<button class="form-button">Сохранить</button>
</form>
</div>
    	<?php
    	}
    	?>
<div class="clear-both"></div>
<script src="js/user-func.js"></script>