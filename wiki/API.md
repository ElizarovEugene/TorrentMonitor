# API

У TorrentMonitor есть REST API для внешних приложений:
добавление, получение списка и удаление отслеживаемых раздач, а также webhook
для [интеграции с Sonarr](Sonarr-Integration).

---

## Получение API-ключа

1. Откройте **Настройки → API**.
2. Нажмите **Сгенерировать ключ**.

> ⚠️ Повторная генерация инвалидирует старый ключ — все клиенты API
> (в том числе webhook в Sonarr) нужно будет обновить.

## Авторизация

Каждый запрос должен содержать ключ одним из способов:

- заголовок `Authorization: Bearer ВАШ_КЛЮЧ` (рекомендуется);
- параметр строки запроса `?token=ВАШ_КЛЮЧ` (удобно для webhook).

Без действительного ключа API отвечает `401`.

## Формат ответов

Все ответы — JSON:

```json
{ "ok": true,  "message": "Тема добавлена.", "data": { } }
{ "ok": false, "message": "Описание ошибки." }
```

| Код | Значение |
|---|---|
| 200 | успех |
| 201 | объект создан |
| 401 | неверный или отсутствующий токен |
| 404 | ресурс/объект не найден |
| 409 | конфликт — тема уже отслеживается |
| 422 | неверные параметры запроса |

---

## GET /api/torrents — список тем

Параметры (необязательные): `tracker` — фильтр по трекеру.

```bash
curl -H "Authorization: Bearer ВАШ_КЛЮЧ" \
  "http://ВАШ_АДРЕС_ТМ/api/torrents?tracker=rutracker.org"
```

Ответ:

```json
{
  "ok": true,
  "message": "2 тем.",
  "data": [
    {
      "id": 15,
      "tracker": "rutracker.org",
      "name": "Название раздачи",
      "torrent_id": "4201572",
      "hd": 0,
      "ep": "",
      "timestamp": "2026-07-01 12:00:00",
      "error": 0,
      "closed": 0
    }
  ]
}
```

## POST /api/torrents — добавить тему или сериал

Тело запроса — JSON (`Content-Type: application/json`) или обычная форма.

### Вариант 1: форумный трекер (по ссылке на тему)

| Параметр | Обязателен | Описание |
|---|---|---|
| `url` | да | ссылка на тему, например `http://rutracker.org/forum/viewtopic.php?t=4201572` |
| `name` | нет | своё название |

```bash
curl -X POST "http://ВАШ_АДРЕС_ТМ/api/torrents" \
  -H "Authorization: Bearer ВАШ_КЛЮЧ" \
  -H "Content-Type: application/json" \
  -d '{"url":"http://rutracker.org/forum/viewtopic.php?t=4201572","name":"Название (необязательно)"}'
```

Успех — `201`:

```json
{ "ok": true, "message": "Тема добавлена.", "data": { "tracker": "rutracker.org", "threme": "4201572" } }
```

Требования: URL должен принадлежать поддерживаемому форумному трекеру, и для этого
трекера должны быть заполнены **Учётные данные**. Ссылки lostfilm.tv и newstudio.tv
не принимаются — это RSS-трекеры, используйте вариант 2.

### Вариант 2: RSS-трекер (сериал по названию)

| Параметр | Обязателен | Описание |
|---|---|---|
| `tracker` | да | `lostfilm.tv`, `lostfilm-mirror`, `newstudio.tv` или `baibako.tv` |
| `name` | да | название сериала на английском, например `House` |
| `hd` | нет | качество: `0` = SD (по умолчанию), `1` = 720p, `2` = 1080p |

```bash
curl -X POST "http://ВАШ_АДРЕС_ТМ/api/torrents" \
  -H "Authorization: Bearer ВАШ_КЛЮЧ" \
  -H "Content-Type: application/json" \
  -d '{"tracker":"lostfilm.tv","name":"House","hd":2}'
```

> ⚠️ У lostfilm.tv значения качества исторически отличаются:
> `1` = FHD 1080, `2` = HD 720 MP4 (см. [трекеры](Supported-Trackers#выбор-качества-у-rss-трекеров)).

## DELETE /api/torrents/{id} — удалить тему

`{id}` — идентификатор из `GET /api/torrents`.

```bash
curl -X DELETE "http://ВАШ_АДРЕС_ТМ/api/torrents/15" \
  -H "Authorization: Bearer ВАШ_КЛЮЧ"
```

## POST /api/sonarr — webhook для Sonarr

Принимает уведомления Sonarr **On Grab**; событие `Test` возвращает
`{"ok":true,"message":"OK"}`. Токен обычно передаётся в URL:
`http://ВАШ_АДРЕС_ТМ/api/sonarr?token=ВАШ_КЛЮЧ`.

Дополнительный параметр `?category=` задаёт категорию qBittorrent для добавляемой темы.

Как это работает и как настроить Sonarr — на странице
[Интеграция с Sonarr](Sonarr-Integration).

---

## Пример на PHP

```php
<?php
$apiKey = 'ВАШ_КЛЮЧ';
$tmUrl  = 'http://ВАШ_АДРЕС_ТМ';

$ch = curl_init("$tmUrl/api/torrents");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
    CURLOPT_POSTFIELDS     => json_encode([
        'url'  => 'http://rutracker.org/forum/viewtopic.php?t=4201572',
        'name' => 'Название (необязательно)',
    ]),
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if ($response['ok']) {
    echo "Добавлено: {$response['data']['tracker']} / {$response['data']['threme']}";
} else {
    echo "Ошибка: {$response['message']}";
}
```

Для RSS-трекера замените `CURLOPT_POSTFIELDS`:

```php
CURLOPT_POSTFIELDS => json_encode([
    'tracker' => 'lostfilm.tv',
    'name'    => 'House',
    'hd'      => 2,
]),
```

---

## Ошибки API в интерфейсе

Проблемы обработки запросов (неверный eventType от Sonarr, не найдены учётные данные
трекера и т.п.) дополнительно отображаются в разделе **Ошибки** веб-интерфейса.
