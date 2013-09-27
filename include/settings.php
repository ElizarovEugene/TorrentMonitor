<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/Database.class.php";

$settings = Database::getAllSetting();
foreach ($settings as $row)
{
	extract($row);
}
?>
<h2 class="settings-title">Настройки монитора</h2>

<form id="setting">
    <p>
        <label class="label-name">Путь сохранения .torrent файлов</label>
        <input type="text" name="path" value="<?php echo $path ?>"><br>
        <span class="subinput-text">Например: /var/torrent/upload/</span>
    </p>
    <p>
        <label class="label-name">Эл. ящик для уведомлений</label>
        <input type="text" name="email" value="<?php echo $email ?>">
        <span class="subinput-text">Например: vasia@mail.ru</span>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="send" <?php if ($send) echo "checked" ?>> Отправлять уведомления</label>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="send_warning" <?php if ($send_warning) echo "checked" ?>> Отправлять уведомления об ошибках</label>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="auth" <?php if ($auth) echo "checked" ?>> Включить авторизацию</label>
    </p>
    <button class="form-button">Сохранить</button>
</form>
<br/>
<br/>
<h2 class="settings-title">Смена пароля</h2>
<form id="change_pass">
    <p>
        <label class="label-name">Новый пароль</label>
        <input type="password" name="password">
    </p>
    <p>
        <label class="label-name">Еще раз</label>
        <input type="password" name="password2">
    </p>
    <button class="form-button">Сменить</button>
</form>
<script src="js/user-func.js"></script>