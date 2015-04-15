<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Notifier.class.php";
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
        <label class="label-name">Адрес TorrentMonitor</label>
        <input type="text" name="serverAddress" value="<?php echo $serverAddress ?>">
        <span class="subinput-text">Например: http://torrent.test.ru/</span>
    </p>
   <br/>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="auth" <?php if ($auth) echo "checked" ?>> Включить авторизацию</label>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" id="proxy" name="proxy" <?php if ($proxy) echo "checked" ?> onclick="expand('proxySettings')"> Использовать прокси</label>
    </p>
    <div id="proxySettings" <?php if ( ! $proxy) echo 'class="result"' ?>>
        <p>
            <label class="label-name">IP, порт прокси-сервера</label>
            <input type="text" name="proxyAddress" value="<?php echo $proxyAddress ?>">
            <span class="subinput-text">Например: 127.0.0.1:9050</span>
        </p>
    </div>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" id="torrent" name="torrent" <?php if ($useTorrent) echo "checked" ?> onclick="expand('torrentSettings')"> Управлять торрент-клиентом</label>
    </p>
    <div id="torrentSettings" <?php if ( ! $useTorrent) echo 'class="result"' ?>>
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
            <label><input type="checkbox" name="deleteDistribution" <?php if ($deleteDistribution) echo "checked" ?>> Удалять раздачи</label>
            <span class="subinput-text">Только для lostfilm.tv, novafilm.tv, baibako.tv и newstudio.tv</span>
        </p>
        <p>
            <label class="label-name"></label>
            <label><input type="checkbox" name="deleteOldFiles" <?php if ($deleteOldFiles) echo "checked" ?>> Удалять файлы старых раздач</label>
            <span class="subinput-text">Только для lostfilm.tv, novafilm.tv, baibako.tv и newstudio.tv</span>
        </p>
    </div>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="rss" <?php if ($rss) echo "checked" ?>> RSS лента</label>
    </p>
    <p>
        <label class="label-name"></label>
        <label><input type="checkbox" name="debug" <?php if ($debug) echo "checked" ?>> Режим отладки</label>
    </p>
    <button class="form-button">Сохранить</button>
</form>
<br/>
<br/>
<h2 class="settings-title">Настройки уведомлений</h2>
<form id="notifier_settings">
    <label id="notifiers-table-hint"/>
    <table class="notifierSettings" id="notifiers-table">
        <tr class="notifierSettings">
            <th>Сервис</th>
            <th>Адрес</th>
            <th><img src="img/icon9-2.png" title="Отправлять уведомления о добавлении/обновлении раздач"/></th>
            <th><img src="img/icon6.png" title="Отправлять уведомления об ошибках"/></th>
            <th/>
        </tr>

<?php
        foreach (Database::getActivePluginsByType(Notifier::$type) as $plugin)
        {
            $notifier = Notifier::Create($plugin['name'], $plugin['group']);
            if ($notifier == null)
                continue;

            $needSendUpdate = "";
            $needSendWarning = "";
            if ($notifier->SendUpdate() == TRUE)
                $needSendUpdate = 'checked';
            if ($notifier->SendWarning() == TRUE)
                $needSendWarning = 'checked';
            $htmlRow  = '<tr class="notifierSettings" group="'.$notifier->Group().'">';
            $htmlRow .= '<td class="notifierSettings"><select id="sendService" name="sendService" style="width: 150px;">';

            foreach(Sys::getNotifiers() as $notifInfo)
            {
                $selected = "";
                if ($notifInfo["Name"] == $notifier->Name())
                    $selected = "selected";
                $htmlRow .= '<option value="'.$notifInfo["Name"].'" '.$selected.'>'.$notifInfo["VerboseName"].'</option>';
            }

            $htmlRow .= '</select></td>';
            $htmlRow .=
                   '<td class="notifierSettings"><input type="text" name="sendAddress" value="'.$notifier->SendAddress().'" style="width: 300px;"> </td>
                    <td class="notifierSettings"><input type="checkbox" name="sendUpdate" '.$needSendUpdate.' > </td>
                    <td class="notifierSettings"><input type="checkbox" name="sendWarning" '.$needSendWarning.' > </td>
                    <td class="notifierSettings"><img src="img/delete.png" title="Удалить" onclick="removeNotifierSetting(this)"/> </td>
                  </tr>';

            $notifier = null;
            echo $htmlRow;
        }

?>

    </table>
    <h2 class="add-notifier-title" >Добавить службу уведомлений</h2>
    <label id="notifier-list-end"/>
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
