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
    </nav>

    <div class="form-error mb-2" x-show="error.length > 0" x-text="error" x-transition.opacity></div>

    <form x-show="currentTab == 'basic'" @submit.prevent="update('basic', $el)" action="action.php">
        <label class="row">
            <div class="col --2:lg mb-1">Адрес TM:</div>
            <div class="col --5:lg mb-2">
                <input type="text" name="serverAddress" x-model="options.serverAddress" required>
                <div class="form-help">Например: http://torrent.test.ru/</div>
            </div>
        </label>

        <div class="form-separator mb-2"></div>

        <label class="row">
            <div class="col --2:lg mb-1">User-Agent:</div>
            <div class="col --5:lg mb-2">
                <input type="text" name="userAgent" x-model="options.userAgent" required>
                <div class="form-help">Например: Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0 </div>
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
                        <select x-model="options.torrentClient">
                            <template x-for='client in ["Deluge","Transmission","qBittorrent","TorrServer","SynologyDS"]'>
                                <option x-text="client" :selected="options.torrentClient == client"></option>
                            </template>
                        </select>
                    </div>
                </label>

                <label class="row">
                    <div class="col --2:lg mb-1">IP, порт торрент-клиента:</div>
                    <div class="col --5:lg mb-2">
                        <input type="text" name="torrentAddress" x-model="options.torrentAddress" :required="options.useTorrent > 0" placeholder="127.0.0.1:58846">
                        <div class="form-help">Например: 127.0.0.1:58846</div>
                    </div>
                </label>

                <label class="row">
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


</div>
