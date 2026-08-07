<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>

<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#settings" /></svg> Настройки</div>
</div>

<?php
$servicesNotify = Database::getServiceList('notification');
$servicesWarn = Database::getServiceList('warning');
$settings = Database::getAllSetting();
$csettings = [];
foreach ($settings as $key => $row)
{
    $csettings[key($row)] = $row[key($row)];
    extract($row);
}
$config = Config::read('ext_filename');
if (file_exists($config))
    $csettings['settings'] = file_get_contents($config);
?>

<div x-data='settings(<?= json_encode($csettings, JSON_NUMERIC_CHECK) ?>)' class="settings row">

    <nav class="tabs col mb-2">
        <button :class="currentTab == 'basic' && '--current'" @click="setTab('basic')">Основные</button>
        <button :class="currentTab == 'auth' && '--current'" @click="setTab('auth')">Смена пароля</button>
        <button x-show="options.send" :class="currentTab == 'notifications' && '--current'" @click="setTab('notifications')">Уведомления</button>
        <button :class="currentTab == 'proxy' && '--current'" @click="setTab('proxy')">Прокси</button>
        <button :class="currentTab == 'torrent' && '--current'" @click="setTab('torrent')">Торрент-клиент</button>
        <button :class="currentTab == 'extended' && '--current'" @click="setTab('extended')">Расширенные</button>
        <button :class="currentTab == 'api' && '--current'" @click="setTab('api')">API</button>
    </nav>

    <div class="form-error mb-2" x-show="error.length > 0" x-text="error" x-transition.opacity></div>

    <form x-show="currentTab == 'basic'" @submit.prevent="update('basic', $el)" action="action.php">
        <label class="row">
            <div class="col --2:lg mb-1">Адрес TM:</div>
            <div class="col --5:lg mb-2">
                <input type="url" name="serverAddress" x-model="options.serverAddress" pattern="https?://.+" required>
                <div class="form-help">Полный адрес с протоколом, например: http://torrent.test.ru/</div>
            </div>
        </label>

        <div class="form-separator mb-2"></div>

        <label class="row">
            <div class="col --2:lg mb-1">User-Agent:</div>
            <div class="col --5:lg mb-2">
                <input type="text" name="userAgent" x-model="options.userAgent" required>
                <div class="form-help">Например: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36</div>
            </div>
        </label>

        <div class="form-separator mb-2"></div>

        <label class="row mt-1">
            <div class="col --2:lg mb-1">Оформление:</div>
            <div class="col --5:lg mb-2">
                <select @change="setTheme($el.value)">
                    <option disabled :selected="theme === null">выберите</option>
                    <template x-for='(ltheme, index) in {"light":{"title":"Светлое"},"dark":{"title":"Тёмное"},"el-classico":{"title":"Классическое"}}'>
                        <option :value="index" x-text="ltheme.title" :selected="index == theme"></option>
                    </template>
                </select>
            </div>
        </label>

        <div class="form-separator mb-2"></div>

        <label class="row" @click="options.auth = !options.auth">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.auth && '--done'"></div> Включить вход по паролю
            </div>
        </label>

        <label class="row" @click="options.send = !options.send">
            <div class="col --2:lg"></div>
            <div class="col --5:lg mb-4 toggler-wrap">
                <div class="toggler" :class="options.send && '--done'"></div> Включить уведомления
            </div>
        </label>

        <label class="row" @click="options.rss = !options.rss">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.rss && '--done'"></div> RSS лента
            </div>
        </label>

        <label class="row" @click="options.autoUpdate = !options.autoUpdate">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.autoUpdate && '--done'"></div> Автоматическое обновление TM
            </div>
        </label>

        <label class="row" @click="options.debug = !options.debug">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.debug && '--done'"></div> Режим отладки
            </div>
        </label>

        <div class="row mt-4">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="submit" class="btn btn--primary">Сохранить</button>
            </div>
        </div>
    </form>


    <form x-show="currentTab == 'auth'" x-effect="checkEqualPass()" @submit.prevent="updateAuth($el)" action="action.php">

        <label class="row">
            <div class="col --2:lg mb-1">Новый пароль:</div>
            <div class="col --5:lg mb-2">
                <input type="password" name="password" x-model="password" :required="options.auth > 0">
            </div>
        </label>

        <label class="row">
            <div class="col --2:lg mb-1">Еще раз:</div>
            <div class="col --5:lg mb-2">
                <input type="password" name="password2" x-model="passwordConfirm" :required="options.auth > 0">
            </div>
        </label>

        <div class="row mt-4">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="submit" class="btn btn--primary">Сменить пароль</button>
            </div>
        </div>
    </form>


    <form x-show="currentTab == 'notifications'" @submit.prevent="update($el)" action="action.php"
        x-data='settingsServices(<?= json_encode([
            'notify'  => $servicesNotify,
            'warn'    => $servicesWarn
            ]) ?>)'
        >

        <label class="row" @click="options.sendUpdate = !options.sendUpdate">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.sendUpdate && '--done'"></div> Отправлять уведомления об обновлениях
            </div>
        </label>

        <div class="row">
            <div x-show="options.sendUpdate" x-collapse>
                <label class="row mt-1">
                    <div class="col --2:lg mb-1">Сервис:</div>
                    <div class="col --5:lg mb-2">
                        <select
                            x-init="$nextTick(() => setServiceNotify());$watch('options.sendUpdateService', value => setServiceNotify())"
                            x-model.number="options.sendUpdateService" :required="options.sendUpdate > 0">
                            <option value="" disabled>выберите</option>
                            <template x-for='service in servicesNotify' :key="service.id">
                                <option :value="service.id" :selected="service.id == options.sendUpdateService" x-text="service.service"></option>
                            </template>
                        </select>
                    </div>
                </label>
                <label class="row">
                    <div class="col --2:lg mb-1">Адрес:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="sendUpdateAddress" x-model="serviceNotify.address" :required="options.sendUpdate > 0">
                    </div>
                </label>
            </div>
        </div>

        <label class="row" @click="options.sendWarning = !options.sendWarning">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.sendWarning && '--done'"></div> Отправлять уведомления об ошибках
            </div>
        </label>

        <div class="row">
            <div x-show="options.sendWarning" x-collapse>
                <label class="row mt-1">
                    <div class="col --2:lg mb-1">Сервис:</div>
                    <div class="col --5:lg mb-2">
                        <select
                            x-init="$nextTick(() => setServiceWarn());$watch('options.sendWarningService', value => setServiceWarn())"
                            x-model.number="options.sendWarningService" :required="options.sendWarning > 0">
                            <option value="" disabled>выберите</option>
                            <template x-for='service in servicesWarn' :key="service.id">
                                <option :value="service.id" :selected="service.id == options.sendWarningService" x-text="service.service"></option>
                            </template>
                        </select>
                    </div>
                </label>
                <label class="row">
                    <div class="col --2:lg mb-1">Адрес:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="sendUpdateAddress" x-model="serviceWarn.address" :required="options.sendWarning > 0">
                    </div>
                </label>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="submit" class="btn btn--primary">Сохранить</button>
            </div>
        </div>
    </form>


    <form x-show="currentTab == 'proxy'" @submit.prevent="update('proxy', $el)" action="action.php">

        <label class="row" @click="options.proxy = !options.proxy">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.proxy && '--done'"></div> Использовать прокси
            </div>
        </label>

        <div class="row">
            <div x-show="options.proxy" x-collapse>
                <label class="row mt-1">
                    <div class="col --2:lg mb-1">Тип прокси:</div>
                    <div class="col --5:lg mb-2">
                        <select x-model="options.proxyType">
                            <template x-for='type in ["HTTP","SOCKS5"]'>
                                <option :value="type" :selected="options.proxyType == type" x-text="type"></option>
                            </template>
                        </select>
                    </div>
                </label>
                <label class="row">
                    <div class="col --2:lg mb-1">IP и порт сервера:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="proxyAddress" x-model="options.proxyAddress" :required="options.proxy > 0" placeholder="127.0.0.1:9050">
                        <div class="form-help">Например: 127.0.0.1:9050</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="submit" class="btn btn--primary">Сохранить</button>
            </div>
        </div>
    </form>


    <form x-show="currentTab == 'torrent'" @submit.prevent="update('torrent', $el)" action="action.php">

        <label class="row" @click="options.useTorrent = !options.useTorrent">
            <div class="col --2:lg"></div>
            <div class="col --10:lg mb-2 toggler-wrap">
                <div class="toggler" :class="options.useTorrent && '--done'"></div> Управлять торрент-клиентом
            </div>
        </label>

        <div class="row">
            <div x-show="options.useTorrent" x-collapse>
                <label class="row mt-1">
                    <div class="col --2:lg mb-1">Торрент-клиент:</div>
                    <div class="col --5:lg mb-2">
                        <select x-model="options.torrentClient" @change="$store.tmApp.torrentClient = options.torrentClient">
                            <template x-for='client in ["Deluge","qBittorrent","SynologyDS","TorrServer","Transmission"]'>
                                <option x-text="client" :selected="options.torrentClient == client"></option>
                            </template>
                        </select>
                    </div>
                </label>

                <label class="row">
                    <div class="col --2:lg mb-1">Адрес торрент-клиента:</div>
                    <div class="col --5:lg mb-2">
                        <input type="url" name="torrentAddress" x-model="options.torrentAddress" :required="options.useTorrent > 0" pattern="https?://.+">
                        <div class="form-help">Полный адрес с протоколом, например: http://127.0.0.1:9091</div>
                    </div>
                </label>

                <label class="row" x-show="options.torrentClient != 'Deluge'">
                    <div class="col --2:lg mb-1">Логин:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="torrentLogin" x-model="options.torrentLogin" placeholder="KorP">
                        <div class="form-help">Например: KorP</div>
                    </div>
                </label>

                <label class="row">
                    <div class="col --2:lg mb-1">Пароль:</div>
                    <div class="col --5:lg mb-2">
                        <input type="password" name="torrentPassword" x-model="options.torrentPassword" placeholder="Pa$$w0rd">
                        <div class="form-help">Например: Pa$$w0rd</div>
                    </div>
                </label>

                <label class="row">
                    <div class="col --2:lg mb-1">Директория для скачивания:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="pathToDownload" x-model="options.pathToDownload" :required="options.useTorrent > 0">
                        <div class="form-help">Например: /var/lib/transmission/downloads или C:/downloads</div>
                    </div>
                </label>

                <label class="row" x-show="options.torrentClient == 'qBittorrent'">
                    <div class="col --2:lg mb-1">Категория по умолчанию:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="qbitCategory" x-model="options.qbitCategory">
                        <div class="form-help">Применяется если для раздачи не задана индивидуальная категория</div>
                    </div>
                </label>

                <label class="row" @click="options.deleteDistribution = !options.deleteDistribution">
                    <div class="col --2:lg"></div>
                    <div class="col --10:lg mb-2 toggler-wrap">
                        <div class="toggler" :class="options.deleteDistribution && '--done'"></div> Удалять раздачи из torrent-клиента
                    </div>
                </label>

                <label class="row" @click="options.deleteOldFiles = !options.deleteOldFiles">
                    <div class="col --2:lg"></div>
                    <div class="col --5:lg toggler-wrap">
                        <div class="toggler" :class="options.deleteOldFiles && '--done'"></div> Удалять файлы старых раздач
                    </div>

                </label>

                <div class="row">
                    <div class="col --2:lg"></div>
                    <div class="col --5:lg mb-2">
                        <div class="form-help">Только для lostfilm.tv, baibako.tv и newstudio.tv</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="submit" class="btn btn--primary">Сохранить</button>
            </div>
        </div>
    </form>


    <form x-show="currentTab == 'extended'" @submit.prevent="update('extended', $el)" action="action.php">

        <label class="row">
            <div class="col --2:lg mb-1">Таймаут HTTP-запросов:</div>
            <div class="col --5:lg mb-2">
                <input type="number" name="httpTimeout" x-model="options.httpTimeout" min="1" required>
                <div class="form-help">В секундах. Используется при опросе трекеров и работе с торрент-клиентом.</div>
            </div>
        </label>

        <label class="row">
            <div class="col --2:lg mb-1">FlareSolverr / Byparr URL:</div>
            <div class="col --5:lg mb-2">
                <input type="url" name="flaresolverrUrl" x-model="options.flaresolverrUrl" placeholder="http://byparr:8191">
                <div class="form-help">Адрес Byparr/FlareSolverr для обхода Cloudflare. Оставьте пустым, если не используется.</div>
            </div>
        </label>

        <div class="form-separator mb-2"></div>

        <div class="row">
            <div class="col --8:lg mb-2">
                <textarea name="settings" x-model="options.settings" cols="30" rows="20"></textarea>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn--primary">Сохранить</button>
            <button type="button" class="btn btn--secondary" @click="showPage('help')">Помощь по этой настройке</button>
        </div>

    </form>


    <div x-show="currentTab == 'api'" x-data="{ apiKey: '<?= addslashes((string)Database::getSetting('ApiKey')) ?>' }">

        <div class="row mb-2">
            <div class="col --2:lg mb-1">API-ключ:</div>
            <div class="col --5:lg mb-2">
                <template x-if="apiKey">
                    <input type="text" :value="apiKey" readonly @click="$el.select()">
                </template>
                <template x-if="!apiKey">
                    <div class="form-help --error">Ключ не сгенерирован. Нажмите кнопку ниже.</div>
                </template>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col --2:lg"></div>
            <div class="col">
                <button type="button" class="btn btn--secondary" @click="let d = $data; $.post('action.php', {action:'api_token_regenerate'}, function(r){ r.error ? notyf.error(r.msg) : (d.apiKey = r.token, notyf.success(r.msg)) }, 'json')">
                    <span x-text="apiKey ? 'Сгенерировать новый ключ' : 'Сгенерировать ключ'"></span>
                </button>
                <div x-show="apiKey" class="form-help mt-1">Генерация нового ключа инвалидирует старый.</div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col --2:lg mb-1"></div>
            <div class="col --10:lg mb-2">
                <div class="form-help">
                    Авторизация: заголовок <code>Authorization: Bearer API_KEY</code><br><br>
                    <b>GET /api/torrents</b> — список тем. Параметр: <code>?tracker=</code> (опционально).<br>
                    <b>GET /api/torrents/{id}</b> — все поля одной темы по ID.<br>
                    <b>POST /api/torrents</b> — добавить. Форумный трекер: <code>url</code>. RSS-трекер: <code>tracker</code> + <code>name</code> [+ <code>hd</code>: 0=SD, 1=720p, 2=1080p].<br>
                    <b>DELETE /api/torrents/{id}</b> — удалить тему по ID.<br>
                    <b>POST /api/run</b> — запустить движок вручную (асинхронно, возвращает 202).<br>
                    <b>GET /api/errors</b> — список ошибок. Параметр: <code>?tracker=</code> (опционально).<br>
                    <b>GET /api/errors/{id}</b> — ошибки конкретной темы по ID.<br>
                    <b>POST /api/sonarr</b> — эндпоинт для добавления раздач из Sonarr. Поддерживает <code>?token=</code> и <code>?category=</code> в URL.
                </div>
            </div>
        </div>
    </div>


</div>
