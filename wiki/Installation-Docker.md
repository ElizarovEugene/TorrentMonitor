# Установка в Docker

Рекомендуемый способ установки TorrentMonitor — готовый Docker-образ
[**alfonder/torrentmonitor**](https://hub.docker.com/r/alfonder/torrentmonitor).
Внутри контейнера уже есть всё необходимое: веб-сервер, PHP, база данных и планировщик —
ничего устанавливать и настраивать на хосте не нужно.

> Docker-образ — отдельный проект
> [torrentmonitor-dockerized](https://github.com/alfonder/torrentmonitor-dockerized),
> который развивает и поддерживает [**Александр Фомичёв** (alfonder)](https://github.com/alfonder) —
> он не входит в состав TorrentMonitor. Спасибо Александру за контейнеризацию проекта!
> Вопросы по самому контейнеру (образ, теги, переменные окружения) задавайте
> в его репозитории.

Минимальный запуск:

```bash
docker run -d \
  --name torrentmonitor \
  --restart unless-stopped \
  -p 8080:80 \
  -v tm_torrents:/data/htdocs/torrents \
  -v tm_db:/data/htdocs/db \
  alfonder/torrentmonitor
```

Откройте `http://IP_СЕРВЕРА:8080` — дальше вся работа идёт в веб-интерфейсе:
[Первоначальная настройка](Getting-Started).

Оба тома обязательно сохраняйте: в `db` живёт база данных (раздачи, учётные данные,
настройки), в `torrents` — скачанные `.torrent`-файлы.

## Подробные инструкции

Всё, что касается самого контейнера, описано в документации Docker-проекта:

- [README](https://github.com/alfonder/torrentmonitor-dockerized) — краткая справка
  по образу;
- [Вики torrentmonitor-dockerized](https://github.com/alfonder/torrentmonitor-dockerized/wiki) —
  установка Docker, запуск через Docker Compose, запуск на NAS (Synology, QNAP),
  переменные окружения, теги и платформы, обновление образа, решение проблем контейнера.
