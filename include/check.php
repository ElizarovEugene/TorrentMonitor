<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/CurlMultiFetcher.class.php";

//если БД недоступна, Sys::checkAuth() выбросит исключение — в этом случае
//не можем подтвердить авторизацию и закрываем доступ (как и раньше для всех страниц)
try
{
    $authed = Sys::checkAuth();
    $dbOk = true;
    $dbError = null;
}
catch (Throwable $e)
{
    $authed = false;
    $dbOk = false;
    $dbError = $e->getMessage();
}

if ( ! $authed)
    die(header('Location: ../'));

$credentials = array();
$checkResults = array();
$internetOk = false;
$useTorrent = false;
$torrentClient = $torrentAddress = $pathToDownload = null;

if ($dbOk)
{
    //собираем все проверки доступности (интернет + трекеры + торрент-клиент) в один параллельный пакет
    $fetcher = new CurlMultiFetcher();
    $fetcher->add('internet', Sys::INTERNET_CHECK_URL, Sys::getProxyOptions(Sys::INTERNET_CHECK_URL) + array(CURLOPT_FOLLOWLOCATION => 1));

    $credentials = Database::getAllCredentials();
    if ( ! is_array($credentials))
        $credentials = array();

    foreach ($credentials as $cred)
    {
        $page = Sys::getTrackerCheckUrl($cred['tracker']);
        $fetcher->add($cred['tracker'], $page, Sys::getProxyOptions($page) + array(CURLOPT_FOLLOWLOCATION => 1));
    }

    $useTorrent = Database::getSetting('useTorrent');
    if ($useTorrent)
    {
        $torrentClient = Database::getSetting('torrentClient');
        $torrentAddress = Database::getSetting('torrentAddress');
        $pathToDownload = Database::getSetting('pathToDownload');

        if ( ! empty($torrentAddress))
        {
            $fetcher->add('torrentclient', rtrim($torrentAddress, '/').'/', array(
                CURLOPT_NOBODY         => 1,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ));
        }
    }

    $checkResults = $fetcher->execute();

    $internetOk = isset($checkResults['internet'])
        && $checkResults['internet']['http_code'] >= 200 && $checkResults['internet']['http_code'] < 400
        && ! empty($checkResults['internet']['body'])
        && preg_match('/<title>.*<\/title>/', $checkResults['internet']['body']);
}

?>
<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#health" /></svg> Тестирование</div>
</div>

<div class="check">
    <div class="check-title">Окружение</div>

<?php if (version_compare(PHP_VERSION, '7.4.0', '>=')) { ?>
    <div class="check-item">Версия PHP <strong><?= htmlspecialchars(PHP_VERSION, ENT_QUOTES) ?></strong> поддерживается.</div>
<?php } else { ?>
    <div class="check-item --error">Версия PHP <strong><?= htmlspecialchars(PHP_VERSION, ENT_QUOTES) ?></strong> не поддерживается, требуется не ниже 7.4.0.</div>
<?php } ?>

<?php
$requiredExtensions = array(
    'curl'      => 'cURL',
    'pdo_mysql' => 'PDO MySQL',
    'mbstring'  => 'mbstring',
    'simplexml' => 'SimpleXML',
    'zip'       => 'Zip',
    'json'      => 'JSON',
);
foreach ($requiredExtensions as $ext => $label)
{
    if (extension_loaded($ext)) { ?>
    <div class="check-item">Расширение <strong><?= htmlspecialchars($label, ENT_QUOTES) ?></strong> установлено.</div>
    <?php } else { ?>
    <div class="check-item --error">Расширение <strong><?= htmlspecialchars($label, ENT_QUOTES) ?></strong> не установлено.</div>
    <?php }
} ?>

<?php
$maxExecutionTime = (int) ini_get('max_execution_time');
$httpTimeout = $dbOk ? (int) Database::getSetting('httpTimeout') : null;
$maxExecLow = $maxExecutionTime > 0 && $httpTimeout !== null && $maxExecutionTime <= $httpTimeout;
if ($maxExecLow) { ?>
    <div class="check-item --error">max_execution_time (<?= $maxExecutionTime ?> сек.) меньше или равно таймауту HTTP-запросов (<?= $httpTimeout ?> сек.) — страница может прерываться по таймауту.</div>
<?php } else { ?>
    <div class="check-item">max_execution_time: <strong><?= $maxExecutionTime > 0 ? $maxExecutionTime.' сек.' : 'без ограничения' ?></strong>.</div>
<?php } ?>

<?php
$memoryLimitRaw = ini_get('memory_limit');
$memoryLimitBytes = Sys::iniToBytes($memoryLimitRaw);
if ($memoryLimitBytes > 0 && $memoryLimitBytes < 64 * 1024 * 1024) { ?>
    <div class="check-item --error">memory_limit (<?= htmlspecialchars($memoryLimitRaw, ENT_QUOTES) ?>) может быть недостаточным, рекомендуется не менее 64M.</div>
<?php } else { ?>
    <div class="check-item">memory_limit: <strong><?= $memoryLimitBytes > 0 ? htmlspecialchars($memoryLimitRaw, ENT_QUOTES) : 'без ограничения' ?></strong>.</div>
<?php } ?>

<?php if (ini_get('allow_url_fopen')) { ?>
    <div class="check-item">allow_url_fopen включён.</div>
<?php } else { ?>
    <div class="check-item --error">allow_url_fopen выключен — это необходимо для работы Transmission, Synology DownloadStation и страницы помощи.</div>
<?php } ?>

</div>
<div class="check">
    <div class="check-title">Основные настройки</div>

<?php if ($dbOk) { ?>
    <div class="check-item">Подключение к базе данных установлено.</div>
<?php } else { ?>
    <div class="check-item --error">БД недоступна: <?= htmlspecialchars($dbError, ENT_QUOTES) ?></div>
<?php }

if ($dbOk) {
if ($internetOk) { ?>
    <div class="check-item">Подключение к интернету установлено.</div>
<?php } else { ?>
    <div class="check-item --error">Отсутствует подключение к интернету.</div>
<?php }
} ?>

<?php if (Sys::checkConfigExist()) { ?>
    <div class="check-item">Конфигурационный файл существует.</div>
<?php } else { ?>
    <div class="check-item --error">Для корректной работы необходимо внести изменения в конфигурационный файл.</div>
<?php } ?>

<?php if ( ! empty(Config::read('encryption.key'))) { ?>
    <div class="check-item">Ключ шифрования (encryption.key) задан, пароли трекеров и torrent-клиента хранятся в БД в зашифрованном виде.</div>
<?php } else { ?>
    <div class="check-item --error">Ключ шифрования (encryption.key) не задан в config.php — пароли трекеров и torrent-клиента хранятся в БД в открытом виде. Чтобы включить шифрование, выполните <strong>php -r "echo bin2hex(random_bytes(32));"</strong> и добавьте в config.php строку <strong>Config::write('encryption.key', '&lt;результат&gt;');</strong> — после этого существующие пароли автоматически перешифруются при первом же обращении к ним (открытие «Учётные данные»/«Настройки» или очередной запуск engine.php), без ручного ввода заново.</div>
<?php } ?>

<?php
$torrentPath = str_replace('class/../', '', $dir).'torrents/';
if (Sys::checkWriteToPath($torrentPath)) { ?>
    <div class="check-item">Запись в директорию для torrent-файлов <strong><?= htmlspecialchars($torrentPath, ENT_QUOTES) ?></strong> разрешена.</div>
<?php } else { ?>
    <div class="check-item --error">Запись в директорию для torrent-файлов <strong><?= htmlspecialchars($torrentPath, ENT_QUOTES) ?></strong> запрещена.</div>
<?php } ?>

<?php
$sysDir = str_replace('include', '', dirname(__FILE__));
if (Sys::checkWriteToPath($sysDir)) { ?>
    <div class="check-item">Запись в системную директорию <strong><?= htmlspecialchars($sysDir, ENT_QUOTES) ?></strong> разрешена.</div>
<?php } else { ?>
    <div class="check-item --error">Запись в системную директорию <strong><?= htmlspecialchars($sysDir, ENT_QUOTES) ?></strong> запрещена.</div>
<?php } ?>

</div>
<div class="check">
    <div class="check-title">Права доступа</div>

<?php
$permissions = Sys::checkPermissions($sysDir);
$badDirs = $permissions['dirs'];
$badFiles = $permissions['files'];
$maxShown = 20;

if (empty($badDirs) && empty($badFiles)) { ?>
    <div class="check-item">Права доступа на файлы (644) и директории (755) корректны.</div>
<?php } else {
    if ( ! empty($badDirs)) { ?>
    <div class="check-item --error">Найдены директории с правами, отличными от 755 (<?= count($badDirs) ?>):</div>
        <?php foreach (array_slice($badDirs, 0, $maxShown) as $path => $mode) { ?>
    <div class="check-item --error"><strong><?= htmlspecialchars($path, ENT_QUOTES) ?></strong>: <?= htmlspecialchars($mode, ENT_QUOTES) ?></div>
        <?php }
        if (count($badDirs) > $maxShown) { ?>
    <div class="check-item --error">…и ещё <?= count($badDirs) - $maxShown ?>.</div>
        <?php }
    }

    if ( ! empty($badFiles)) { ?>
    <div class="check-item --error">Найдены файлы с правами, отличными от 644 (<?= count($badFiles) ?>):</div>
        <?php foreach (array_slice($badFiles, 0, $maxShown) as $path => $mode) { ?>
    <div class="check-item --error"><strong><?= htmlspecialchars($path, ENT_QUOTES) ?></strong>: <?= htmlspecialchars($mode, ENT_QUOTES) ?></div>
        <?php }
        if (count($badFiles) > $maxShown) { ?>
    <div class="check-item --error">…и ещё <?= count($badFiles) - $maxShown ?>.</div>
        <?php }
    }
} ?>

</div>
<div class="check">
    <div class="check-title">Диск</div>

<?php
$lowSpaceThreshold = 1024 * 1024 * 1024; // 1 ГБ

$torrentsFree = @disk_free_space($torrentPath);
if ($torrentsFree === false) { ?>
    <div class="check-item --error">Не удалось получить сведения о свободном месте для <strong><?= htmlspecialchars($torrentPath, ENT_QUOTES) ?></strong>.</div>
<?php } elseif ($torrentsFree < $lowSpaceThreshold) { ?>
    <div class="check-item --error">Мало свободного места в <strong><?= htmlspecialchars($torrentPath, ENT_QUOTES) ?></strong>: <?= Sys::formatBytes($torrentsFree) ?>.</div>
<?php } else { ?>
    <div class="check-item">Свободно в <strong><?= htmlspecialchars($torrentPath, ENT_QUOTES) ?></strong>: <?= Sys::formatBytes($torrentsFree) ?>.</div>
<?php }

$rootFree = @disk_free_space('/');
if ($rootFree === false) { ?>
    <div class="check-item --error">Не удалось получить сведения о свободном месте на корневом разделе.</div>
<?php } elseif ($rootFree < $lowSpaceThreshold) { ?>
    <div class="check-item --error">Мало свободного места на корневом разделе: <?= Sys::formatBytes($rootFree) ?>.</div>
<?php } else { ?>
    <div class="check-item">Свободно на корневом разделе: <?= Sys::formatBytes($rootFree) ?>.</div>
<?php } ?>

</div>
<div class="check">
    <div class="check-title">Торрент-клиент</div>

<?php if ( ! $dbOk) { ?>
    <div class="check-item --error">Проверка недоступна: нет подключения к БД.</div>
<?php } elseif ( ! $useTorrent) { ?>
    <div class="check-item">Управление торрент-клиентом отключено в настройках.</div>
<?php } else { ?>

    <div class="check-item">Используется торрент-клиент: <strong><?= htmlspecialchars($torrentClient, ENT_QUOTES) ?></strong>.</div>

    <?php if (empty($torrentAddress)) { ?>
    <div class="check-item --error">Адрес торрент-клиента не задан в настройках.</div>
    <?php } else {
        $result = $checkResults['torrentclient'] ?? null;
        $available = $result && empty($result['error']) && $result['http_code'] > 0;
        if ($available) { ?>
    <div class="check-item">Торрент-клиент по адресу <strong><?= htmlspecialchars($torrentAddress, ENT_QUOTES) ?></strong> отвечает.</div>
        <?php } elseif ($torrentClient == 'Deluge') { ?>
    <div class="check-item --error">Торрент-клиент по адресу <strong><?= htmlspecialchars($torrentAddress, ENT_QUOTES) ?></strong> не отвечает. Для Deluge укажите адрес и порт <strong>deluge-web</strong> (по умолчанию 8112), а не порт демона (58846).</div>
        <?php } else { ?>
    <div class="check-item --error">Торрент-клиент по адресу <strong><?= htmlspecialchars($torrentAddress, ENT_QUOTES) ?></strong> не отвечает.</div>
        <?php }
    } ?>

    <?php if (empty($pathToDownload)) { ?>
    <div class="check-item --error">Директория для скачивания не задана в настройках.</div>
    <?php } elseif (is_dir($pathToDownload) && is_writable($pathToDownload)) { ?>
    <div class="check-item">Запись в директорию для скачивания <strong><?= htmlspecialchars($pathToDownload, ENT_QUOTES) ?></strong> разрешена.</div>
    <?php } else { ?>
    <div class="check-item --error">Директория для скачивания <strong><?= htmlspecialchars($pathToDownload, ENT_QUOTES) ?></strong> не существует или недоступна для записи.</div>
    <?php } ?>

<?php } ?>
</div>
<div class="check">
    <div class="check-title">Уведомления</div>

<?php if ( ! $dbOk) { ?>
    <div class="check-item --error">Проверка недоступна: нет подключения к БД.</div>
<?php } else {
$sendUpdate = Database::getSetting('sendUpdate');
$sendWarning = Database::getSetting('sendWarning');

if ( ! $sendUpdate && ! $sendWarning) { ?>
    <div class="check-item">Уведомления не настроены.</div>
<?php }

if ($sendUpdate) {
    $svc = Database::getService('sendUpdateService');
    if ( ! empty($svc['address'])) { ?>
    <div class="check-item">Уведомления об обновлениях (<strong><?= htmlspecialchars($svc['service'] ?? '', ENT_QUOTES) ?></strong>) настроены.</div>
    <?php } else { ?>
    <div class="check-item --error">Уведомления об обновлениях включены, но адрес/токен не заданы.</div>
    <?php }
}

if ($sendWarning) {
    $svc = Database::getService('sendWarningService');
    if ( ! empty($svc['address'])) { ?>
    <div class="check-item">Уведомления об ошибках (<strong><?= htmlspecialchars($svc['service'] ?? '', ENT_QUOTES) ?></strong>) настроены.</div>
    <?php } else { ?>
    <div class="check-item --error">Уведомления об ошибках включены, но адрес/токен не заданы.</div>
    <?php }
}
} ?>

</div>
<div class="check">
    <div class="check-title">Настройки трекеров</div>

<?php if ( ! $dbOk) { ?>
    <div class="check-item --error">Проверка недоступна: нет подключения к БД.</div>
<?php } elseif ( ! $internetOk) { ?>
    <div class="check-item --error">Проверка трекеров недоступна: отсутствует подключение к интернету.</div>
<?php } else {
foreach ($credentials as $cred)
{
    $tracker = $cred['tracker'];
    ?>
    <div class="check-subtitle"><?= htmlspecialchars($tracker, ENT_QUOTES) ?></div>
    <?php
    if (file_exists($dir.'trackers/'.$tracker.'.engine.php')) { ?>
    <div class="check-item">Основной файл для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> найден.</div>
    <?php } else { ?>
    <div class="check-item --error">Основной файл для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> не найден.</div>
    <?php } ?>

    <?php if ($tracker == 'nnmclub.to' || $tracker == 'pornolab.net' || $tracker == 'rutracker.org' || $tracker == 'tapochek.net' || $tracker == 'tfile.cc') {
        if (file_exists($dir.'trackers/'.$tracker.'.search.php')) { ?>
    <div class="check-item">Дополнительный файл для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> найден.</div>
        <?php } else { ?>
    <div class="check-item --error">Дополнительный файл для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> не найден.</div>
        <?php
        }
    } ?>

    <?php if ($tracker == 'lostfilm-mirror' || $tracker == 'rutor.org' || $tracker == 'tfile.cc') { ?>
    <div class="check-item">Учётные данные для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> не требуются.</div>
    <?php } elseif ( ! empty($cred['login']) && ! empty($cred['password'])) { ?>
    <div class="check-item">Учётные данные для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> найдены.</div>
    <?php } else { ?>
    <div class="check-item --error">Учётные данные для работы с трекером <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> не найдены.</div>
    <?php } ?>

    <?php
    $result = $checkResults[$tracker] ?? null;
    $available = $result && empty($result['error']) && $result['http_code'] >= 200 && $result['http_code'] < 400;
    if ($available)
    {
    ?>
    <div class="check-item">Трекер <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> доступен.</div>
    <?php } else { ?>
    <div class="check-item --error">Трекер <strong><?= htmlspecialchars($tracker, ENT_QUOTES) ?></strong> не доступен.</div>
    <?php
    } ?>
<?php }
} ?>
</div>
