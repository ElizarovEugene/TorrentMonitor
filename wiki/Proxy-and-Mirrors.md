# Прокси и зеркала

Трекеры часто блокируются провайдерами. TorrentMonitor предлагает несколько
инструментов обхода: глобальный прокси, индивидуальные прокси и зеркала для
конкретных трекеров и FlareSolverr/Byparr против Cloudflare.

---

## Глобальный прокси

**Настройки → Прокси** → «Использовать прокси»:

- **Тип прокси** — HTTP или SOCKS5;
- **IP и порт** — например `127.0.0.1:9050` (стандартный порт TOR).

Через глобальный прокси идут **все** запросы TorrentMonitor к трекерам.

## Индивидуальные прокси и зеркала (`config.xml`)

Тонкая настройка выполняется в **Настройки → Расширенные** — текстовое поле
содержит XML-конфигурацию (файл `config.xml`).

### Зеркало для трекера

Если трекер заблокирован, но у него есть работающее зеркало, TM может ходить
на зеркало, продолжая работать с раздачами как с оригинальным трекером:

```xml
<body>
    <mirror_address>
        <lostfilm.tv>my-lostfilm-mirror.example.com</lostfilm.tv>
    </mirror_address>
</body>
```

Для lostfilm есть и специальный вариант — трекер `lostfilm-mirror`
в списке трекеров: добавьте сериалы через него и укажите домен зеркала
в `mirror_address`.

### Прокси только для одного трекера

```xml
<body>
    <proxy>
        <lostfilm.tv>
            <use>yes</use>
            <type>socks5</type>
            <address>192.168.1.220:8118</address>
            <mirror_address>google.ru</mirror_address>
        </lostfilm.tv>
    </proxy>
</body>
```

- `use` — `yes`/`no`;
- `type` — `http` или `socks5`;
- `address` — IP:порт прокси;
- `mirror_address` (необязательно) — зеркало, на которое ходить через этот прокси.

Секций может быть несколько — по одной на трекер. Индивидуальная настройка
имеет приоритет над глобальным прокси.

## TOR

Классическая связка для доступа к заблокированным трекерам — TOR как SOCKS5-прокси:

1. Установите TOR (пакет `tor`; в Docker — отдельный контейнер).
2. Убедитесь, что SOCKS-порт 9050 доступен для TorrentMonitor.
3. Укажите в TM прокси SOCKS5 `127.0.0.1:9050` (или адрес контейнера TOR).

Готовый рецепт для Docker (TOR + Transmission + TM в одной связке) описан в вики
Docker-проекта: [TOR-and-Transmission](https://github.com/alfonder/torrentmonitor-dockerized/wiki/TOR-and-Transmission).

Учтите, что через TOR трекеры отвечают заметно медленнее — при таймаутах
увеличьте «Таймаут HTTP-запросов» в **Настройки → Расширенные**.

## FlareSolverr

Некоторые трекеры прячутся за Cloudflare, который блокирует автоматические запросы.
[FlareSolverr](https://github.com/FlareSolverr/FlareSolverr) решает задачу Cloudflare в реальном
браузере и отдают TM готовые cookies.

1. Запустите FlareSolverr — обычно это Docker-контейнер, порт 8191:

   ```yaml
   services:
     byparr:
       image: ghcr.io/flaresolverr/flaresolverr:latest
       container_name: flaresolverr
       restart: unless-stopped
       ports:
         - "8191:8191"
   ```

2. В TM: **Настройки → Расширенные → FlareSolverr URL** →
   `http://byparr:8191` (или `http://127.0.0.1:8191`).

Если поле пустое, обход Cloudflare не используется.

---

## Что выбрать

| Ситуация | Решение |
|---|---|
| Провайдер блокирует трекер | зеркало в `config.xml` или прокси/TOR |
| Заблокировано несколько трекеров | глобальный прокси или TOR |
| Нужен прокси только для одного трекера | индивидуальный прокси в `config.xml` |
| Трекер за Cloudflare («проверка браузера») | FlareSolverr/Byparr |
