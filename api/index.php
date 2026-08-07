<?php
$dir = dirname(__FILE__).'/../';
include_once $dir.'config.php';
include_once $dir.'class/Database.class.php';
include_once $dir.'class/System.class.php';
include_once $dir.'class/Errors.class.php';
include_once $dir.'class/Notification.class.php';
include_once $dir.'class/qBittorrent.class.php';

header('Content-Type: application/json; charset=utf-8');

// ---- Helpers ---------------------------------------------------------------

function api_respond($ok, $message, $data = null, $code = 200)
{
    http_response_code($code);
    $r = ['ok' => $ok, 'message' => $message];
    if ($data !== null)
        $r['data'] = $data;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_token()
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)$/i', $header, $m))
        return trim($m[1]);
    return $_GET['token'] ?? '';
}

function parse_tracker_url($rawUrl)
{
    $url = parse_url($rawUrl);
    if (empty($url['host']))
        return null;

    $tracker = preg_replace('/^www\./', '', $url['host']);

    if (in_array($tracker, ['lostfilm.tv', 'lostfilm-mirror', 'newstudio.tv']))
        return null;

    if ($tracker === 'tr.anidub.com')
        $tracker = 'anidub.com';
    elseif ($tracker === 'baibako.tv')
        $tracker = 'baibako.tv_forum';

    if ($tracker === 'anidub.com' || $tracker === 'riperam.org') {
        $threme = $url['path'];
    } elseif ($tracker === 'animelayer.ru') {
        $path = str_replace('/torrent', '', $url['path']);
        preg_match('/\/(\w*)\/?/', $path, $array);
        $threme = $array[1] ?? null;
    } elseif ($tracker === 'casstudio.tk') {
        $query = explode('t=', $url['query'] ?? '');
        $threme = $query[1] ?? null;
    } elseif ($tracker !== 'rutor.is') {
        $query = explode('=', $url['query'] ?? '');
        $threme = $query[1] ?? null;
    } else {
        preg_match('/\d{4,8}/', $url['path'], $array);
        $threme = $array[0] ?? null;
    }

    if (empty($threme))
        return null;

    return ['tracker' => $tracker, 'threme' => $threme];
}

function body()
{
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($ct, 'application/json') !== false)
        return json_decode(file_get_contents('php://input'), true) ?? [];
    return $_POST;
}

// ---- Auth ------------------------------------------------------------------

$storedToken = Database::getSetting('ApiKey');
$token = get_token();
if (empty($storedToken) || !hash_equals((string)$storedToken, (string)$token))
    api_respond(false, 'Неверный токен.', null, 401);

// ---- Routing ---------------------------------------------------------------

$httpMethod = $_SERVER['REQUEST_METHOD'];

$base = rtrim(str_replace('index.php', '', $_SERVER['SCRIPT_NAME']), '/');
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(substr($uri, strlen($base)), '/');

$segments = $path !== '' ? explode('/', $path) : [];
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) && $segments[1] !== '' ? (int)$segments[1] : null;

// ---- POST /api/sonarr ------------------------------------------------------

if ($resource === 'sonarr' && $httpMethod === 'POST')
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $eventType = $body['eventType'] ?? '';

    if ($eventType === 'Test')
        api_respond(true, 'OK');

    if ($eventType !== 'Grab') {
        Errors::setWarnings('api', 'api_bad_event_type');
        api_respond(false, "Неверный eventType: {$eventType}.");
    }

    $hash = $body['downloadId'] ?? '';
    if (empty($hash)) {
        Errors::setWarnings('api', 'api_no_download_id');
        api_respond(false, 'Нет downloadId в запросе.');
    }

    $comment = qBittorrent::getTorrentComment($hash);
    if (empty($comment)) {
        Errors::setWarnings('api', 'api_qbit_fail');
        api_respond(false, 'Не удалось получить данные из qBittorrent.');
    }

    $parsed = parse_tracker_url($comment);
    if ($parsed === null) {
        Errors::setWarnings('api', 'api_bad_url');
        api_respond(false, 'comment торрента не содержит корректного URL форумной темы.');
    }

    $tracker = $parsed['tracker'];
    $threme  = $parsed['threme'];

    if (!is_array(Database::getCredentials($tracker))) {
        Errors::setWarnings('api', 'api_no_credentials');
        api_respond(false, "Нет учётных данных для трекера: {$tracker}.");
    }

    if (!Database::checkThremExist($tracker, $threme)) {
        Errors::setWarnings('api', 'api_already_tracked');
        api_respond(false, 'Тема уже отслеживается.');
    }

    $category = $_GET['category'] ?? '';
    Database::setThreme($tracker, '', '', $threme, 1, $category);

    $seriesTitle = $body['series']['title'] ?? $comment;
    Notification::sendNotification('notification', date('r'), 'api',
        "Тема «{$seriesTitle}» ({$comment}) добавлена на мониторинг через Sonarr.", $seriesTitle);
    api_respond(true, "Тема добавлена: {$seriesTitle}.");
}

// ---- GET /api/torrents/{id} ------------------------------------------------

elseif ($resource === 'torrents' && $httpMethod === 'GET' && $id !== null)
{
    $torrent = Database::getTorrentFull($id);
    if ($torrent === null)
        api_respond(false, 'Тема не найдена.', null, 404);

    api_respond(true, 'OK', $torrent);
}

// ---- GET /api/torrents -----------------------------------------------------

elseif ($resource === 'torrents' && $httpMethod === 'GET')
{
    $rows = Database::getTorrentsList('tracker');
    $trackerFilter = $_GET['tracker'] ?? '';
    $items = [];

    if (is_array($rows)) {
        foreach ($rows as $row) {
            if ($trackerFilter && $row['tracker'] !== $trackerFilter)
                continue;
            $items[] = [
                'id'         => (int)$row['id'],
                'tracker'    => $row['tracker'],
                'name'       => $row['name'],
                'torrent_id' => $row['torrent_id'],
                'hd'         => (int)$row['hd'],
                'ep'         => $row['ep'],
                'timestamp'  => $row['timestamp'],
                'error'      => (int)$row['error'],
                'closed'     => (int)$row['closed'],
            ];
        }
    }

    api_respond(true, count($items).' тем.', $items);
}

// ---- POST /api/torrents ----------------------------------------------------

elseif ($resource === 'torrents' && $httpMethod === 'POST')
{
    $b       = body();
    $rawUrl  = trim($b['url'] ?? '');
    $name    = trim($b['name'] ?? '');
    $tracker = trim($b['tracker'] ?? '');

    if (!empty($rawUrl))
    {
        $parsed = parse_tracker_url($rawUrl);
        if ($parsed === null)
            api_respond(false, 'Не удалось разобрать URL или трекер не поддерживается.', null, 422);

        $tracker = $parsed['tracker'];
        $threme  = $parsed['threme'];

        if (!is_array(Database::getCredentials($tracker)))
            api_respond(false, "Нет учётных данных для трекера: {$tracker}.", null, 422);

        if (!Database::checkThremExist($tracker, $threme))
            api_respond(false, 'Тема уже отслеживается.', null, 409);

        Database::setThreme($tracker, $name, '', $threme, 1);
        api_respond(true, 'Тема добавлена.', ['tracker' => $tracker, 'threme' => $threme], 201);
    }
    elseif (!empty($tracker) && !empty($name))
    {
        $rssTrackers = ['lostfilm.tv', 'lostfilm-mirror', 'newstudio.tv', 'baibako.tv'];
        if (!in_array($tracker, $rssTrackers))
            api_respond(false, "Трекер {$tracker} не является RSS-трекером. Используйте параметр url.", null, 422);

        if (!is_array(Database::getCredentials($tracker)))
            api_respond(false, "Нет учётных данных для трекера: {$tracker}.", null, 422);

        $hd = (int)($b['hd'] ?? 0);
        if (!in_array($hd, [0, 1, 2]))
            api_respond(false, 'Параметр hd должен быть 0 (SD), 1 (720p) или 2 (1080p).', null, 422);

        if (!Database::checkSerialExist($tracker, $name, $hd))
            api_respond(false, 'Сериал уже отслеживается.', null, 409);

        Database::setSerial($tracker, $name, '', $hd);
        api_respond(true, 'Сериал добавлен.', ['tracker' => $tracker, 'name' => $name, 'hd' => $hd], 201);
    }
    else
    {
        api_respond(false, 'Укажите url (форумный трекер) или tracker + name (RSS-трекер).', null, 422);
    }
}

// ---- DELETE /api/torrents/{id} ---------------------------------------------

elseif ($resource === 'torrents' && $httpMethod === 'DELETE')
{
    if (!$id)
        api_respond(false, 'Укажите ID: DELETE /api/torrents/{id}', null, 422);

    if (!is_array(Database::getTorrent($id)))
        api_respond(false, 'Тема не найдена.', null, 404);

    Database::deletItem($id);
    api_respond(true, 'Тема удалена.');
}

// ---- POST /api/run ---------------------------------------------------------

elseif ($resource === 'run' && $httpMethod === 'POST')
{
    $engineFile = realpath(dirname(__FILE__).'/../engine.php');
    if (!$engineFile || !file_exists($engineFile))
        api_respond(false, 'engine.php не найден.', null, 500);

    exec(PHP_BINARY . ' ' . escapeshellarg($engineFile) . ' > /dev/null 2>&1 &');
    api_respond(true, 'Движок запущен.', null, 202);
}

// ---- GET /api/errors/{id} --------------------------------------------------

elseif ($resource === 'errors' && $httpMethod === 'GET' && $id !== null)
{
    if (!is_array(Database::getTorrent($id)))
        api_respond(false, 'Тема не найдена.', null, 404);

    $rows = Database::getWarningsByTorrentId($id);
    api_respond(true, count($rows).' ошибок.', $rows);
}

// ---- GET /api/errors -------------------------------------------------------

elseif ($resource === 'errors' && $httpMethod === 'GET')
{
    $tracker = $_GET['tracker'] ?? '';
    if ($tracker !== '')
    {
        $rows = Database::getWarningsList($tracker) ?? [];
    }
    else
    {
        $rows = Database::getAllWarningsList();
    }
    api_respond(true, count($rows).' ошибок.', $rows);
}

// ---- 404 -------------------------------------------------------------------

else
{
    api_respond(false, 'Неизвестный ресурс. Доступны: GET/POST /api/torrents, GET /api/torrents/{id}, DELETE /api/torrents/{id}, POST /api/run, GET /api/errors, GET /api/errors/{id}, POST /api/sonarr.', null, 404);
}
