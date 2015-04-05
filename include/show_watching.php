<script language="javascript">
// возвращает cookie с именем name, если есть, если нет, то undefined
function getCookie(name)
{
    var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

$(document).ready(function(){
    var id = getCookie('id');
    if (typeof id !== 'undefined')
    {
        var div = "#"+id;
        $(this).toggleClass("active");
        $(div).next().toggle();
    }
});
</script>

<form id="threme_clear" method="post">
<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

$users = Database::getUserToWatch();

if ( ! empty($users))
{
	for ($i=0; $i<count($users); $i++)
	{
		$thremes = Database::getThremesFromBuffer($users[$i]['id']);
		?>
<div class="user-torrent-del" onclick="delete_user(<?php echo $users[$i]['id']?>)"></div>
<div class="user-torrent" id="<?php echo $users[$i]['id']?>">Раздачи пользователя <strong><?php echo $users[$i]['name']?></strong> на трекере <b><?php echo $users[$i]['tracker']?></b>:</div>
		<?php
		if (count($thremes) > 0)
		{
		?>
<div id='<?php echo $users[$i]['id'] ?>' class='result'>
<table>
    <thead>
        <tr>
            <th>Скачать</th>
            <th>Раздел</th>
            <th>Тема</th>
            <th>Удалить</th>
            <th>Добавить для наблюдения</th>
        </tr>
    </thead>
    <tbody>
			<?php
			for ($x=0; $x<count($thremes); $x++)
			{
				$url = 'http://'.$users[$i]['tracker'].'/forum/viewtopic.php?t=';
				?>
		<tr>
            <td ><img src="img/icon1.png" onclick="threme_add(<?php echo $thremes[$x]['id']?>, <?php echo $users[$i]['id']?>)"></td>
            <td class="text-align-left"><?php echo $thremes[$x]['section'] ?></td>
            <td class="text-align-left"><a href="<?php echo $url.$thremes[$x]['threme_id']?>" target="_blank"><?php echo $thremes[$x]['threme']?></a></td>
            <td><a class="delete" onclick="delete_from_buffer(<?php echo $thremes[$x]['id']?>)"></a></td>
            <td><a class="add" onclick="transfer_from_buffer(<?php echo $thremes[$x]['id']?>)"></a></td>
        </tr>
			<?php
			}
			?>
</table>
		<?php	
		}
		else
		{
        ?>
<table class="user-table">
    <tbody>
        <tr>
            <td colspan="5">Нет новых тем от пользователя <strong><?php echo $users[$i]['name']?></strong>.</td>
        </tr>
    </tbody>
</table>
        <?php
		}
		?>
</div>
    <?php
	}
	?>
<br>
<button>Очистить</button>
</form>	
<?php	
}
else
{
?>
Нет пользователей для мониторинга.
<?php
}
?>
<div class="clear-both"></div>
<script src="js/user-func.js"></script>