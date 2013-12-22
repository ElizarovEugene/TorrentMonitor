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
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" id="proxy" name="proxy" <?php if ($proxy) echo "checked" ?> onclick="showProxy()"> Использовать прокси</label>
    </p>
    <div id="proxySettings" <?php if (!$proxy) echo 'class="result"' ?>>
    <p>
        <label class="label-name">IP, порт прокси-сервера</label>
        <input type="text" name="proxyAddress" value="<?php echo $proxyAddress ?>"><br>
        <span class="subinput-text">Например: 127.0.0.1:9050</span>
    </p>
    </div>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" id="torrent" name="torrent" <?php if ($useTorrent) echo "checked" ?> onclick="showTorrent()"> Управлять торрент-клиентом</label>
    </p>
    <div id="torrentSettings" <?php if (!$useTorrent) echo 'class="result"' ?>>
    <p>        
        <label class="label-name">Торрент-клиент</label>
        <label>
            <select id="torrentClient" name="torrentClient">
                <option value="Deluge" <?php if ($torrentClient == 'Deluge') echo 'selected';?>>Deluge</option>
                <option value="Transmission" <?php if ($torrentClient == 'Transmission') echo 'selected';?>>Transmission</option>
            </select>
        </label>
    </p>
    <p>
        <label class="label-name">IP, порт торрент-клиента</label>
        <input type="text" name="torrentAddress" value="<?php echo $torrentAddress ?>">
        <span class="subinput-text">Например: 127.0.0.1:58846</span>
    </p>
    <p>
        <label class="label-name">Логин</label>
        <input type="text" name="torrentLogin" value="<?php echo $torrentLogin ?>">
        <span class="subinput-text">Например: KorP</span>
    </p>
    <p>
        <label class="label-name">Пароль</label>
        <input type="password" name="torrentPassword" value="<?php echo $torrentPassword ?>">
        <span class="subinput-text">Например: Pa$$w0rd</span>
    </p>
    <p>
        <label class="label-name">Директория для скачивания</label>
        <input type="text" name="pathToDownload" value="<?php echo $pathToDownload ?>">
        <span class="subinput-text">Например: /var/lib/transmission/downloads</span>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="deleteTorrent" <?php if ($deleteTorrent) echo "checked" ?>> Удалять .torrent файлы после добавления</label>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="deleteOldFiles" <?php if ($deleteOldFiles) echo "checked" ?>> Удалять файлы старых раздач</label>
        <span class="subinput-text">Только для lostfilm.tv и novafilm.tv</span>
    </p>    
    </div>
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