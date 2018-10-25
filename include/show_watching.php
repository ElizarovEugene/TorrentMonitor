<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>

<h2 class="settings-title">Слежение за пользователями</h2>
<p>
	<label class="label-name">Раздачи </label>
	<select onchange="changeDiv();" id="users">
	    <option value="#">Выберите пользователя</option>
<?php
$thremesArray = array();    
$users = Database::getUserToWatch();

if ( ! empty($users))
{
	for ($i=0; $i<count($users); $i++)
	{
    ?>
		<option value="<?php echo $users[$i]['id']?>"
		<?php
    	if ($_COOKIE['userID'] == $users[$i]['id'])	
    	    echo ' selected="selected"';
        ?>
		>Пользователя <?php echo $users[$i]['name'] ?> на трекере <?php echo $users[$i]['tracker'] ?></option>
	<?php
    	$thremesArray['user_id'][] = $users[$i]['id'];
    	$thremesArray['tracker'][] = $users[$i]['tracker'];
    	$thremesArray['thremes'][] = Database::getThremesFromBuffer($users[$i]['id']);
	}
}
?>
	</select>
</p>
<?php
if ( ! empty($thremesArray))
{
    for ($i=0; $i<count($thremesArray['user_id']); $i++)
    {
        ?>
        <div id="<?php echo $thremesArray['user_id'][$i] ?>_user_hidden" class="result">
        <?php
        if (count($thremesArray['thremes'][$i]) > 0)
        {
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Скачать</th>
                        <th>Раздел</th>
                        <th>Тема</th>
                        <th>Время создания</th>
                        <th>Удалить</th>
                        <th>Добавить для наблюдения</th>
                    </tr>
                </thead>
                <tbody>
    			<?php
                $thremes = $thremesArray['thremes'][$i];
    			for ($x=0; $x<count($thremes); $x++)
    			{
    				$url = 'http://'.$thremesArray['tracker'][$i].'/forum/viewtopic.php?t=';
    				?>
            		<tr>
                        <td><img src="img/icon1.png" onclick="threme_add(<?php echo $thremes[$x]['id']?>, <?php echo $users[$i]['id']?>)"></td>
                        <td class="text-align-left"><?php echo $thremes[$x]['section'] ?></td>
                        <td class="text-align-left"><a href="<?php echo $url.$thremes[$x]['threme_id']?>" target="_blank"><?php echo $thremes[$x]['threme']?></a></td>
                        <td class="text-align-center" nowrap>
                        <?php 
                        $arr = preg_split('/-/', $thremes[$x]['timestamp']);
                        $date = $arr[2].' '.Sys::dateNumToString($arr[1]).' '.$arr[0];
                        echo $date;
                        ?>
                        </td>
                        <td><img src="img/delete.png" onclick="delete_from_buffer(<?php echo $thremes[$x]['id']?>)"></td>
                        <td><img src="img/add.png" onclick="transfer_from_buffer(<?php echo $thremes[$x]['id']?>)"></td>
                    </tr>
    			<?php
    			}
    			?>
            </table>
            <a href="#" class="user-torrent-del" onclick="thremes_clear(<?php echo $users[$i]['id']?>)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Пометить темы как просмотренные</a><br />
        <?php
        }
        else
        {
        ?>
        Не найдено тем для выбранного пользователя.<br />
        <?php
        }
        ?>
        
        <a href="#" class="user-torrent-del" onclick="delete_user(<?php echo $users[$i]['id']?>)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Удалить пользователя</a>
        </div>
        <?php
    }
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
<?php
$str = '';
for ($i=0; $i<count($users); $i++)
{
    $str .= "'".$users[$i]['id']."', ";
}
$str = substr($str, 0, -2);
?>
<script language="javascript">
function changeDiv()
{
    var select = document.getElementById('users');
    var selectedText = select.options[select.selectedIndex].text;
    var a = select.options[select.selectedIndex].value;
    var b = [<?php echo $str?>];
    for (var i=0; i < b.length; i++)
    {
        var e = b[i];
        document.getElementById(e + '_user_hidden').style.display = "none";
    }
    document.getElementById(a + '_user_hidden').style.display = "block";
    document.cookie = 'userID=' + a;
}

function getCookie(name)
{
    var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

var id = getCookie('userID');
if (typeof id !== 'undefined')
    document.getElementById(id + '_user_hidden').style.display = "block";
</script>