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
	<select onchange="changeDiv();" id="trackers">
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
<div id="<?php echo $credential[$i]['tracker'] ?>_credential_hidden" class="result">
            <?php
           	if ($credential[$i]['necessarily'])
        	{
            ?>
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
            <?php
            }
            else
            {
            ?>
            Учётные данные не требуются.
            <?php
            }
            ?>
</div>
    	<?php
    	}
    	?>
<div class="clear-both"></div>
<script src="js/user-func.js"></script>
<?php
$str = '';
for ($i=0; $i<count($trackers); $i++)
{
    $str .= "'".$trackers[$i]."', ";
}
$str = substr($str, 0, -2);
?>
<script language="javascript">
function changeDiv()
{
    var select = document.getElementById('trackers');
    var selectedText = select.options[select.selectedIndex].text;
    var a = [<?php echo $str?>];
    for (var i=0; i < a.length; i++)
    {
        var e = a[i];
        var d;
        if (selectedText == e)
            d = "block";
        else
            d = "none";
        document.getElementById(e + '_credential_hidden').style.display = d;
    }
}
</script>