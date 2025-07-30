# TorrentMonitor
Приложение мониторит изменения на популярных торрент-трекерах рунета и автоматизирует закачку обновлений (сериалы, раздачи которые ведутся *путем добавления новых серий/новых версий*, перезалитые торрент-файлы и т.д.)

### Страница проекта:
http://tormon.ru

### Список возможностей приложения:
- Слежение за темами
  - anidub.com
  - animelayer.ru
  - baibako.tv
  - booktracker.org
  - casstudio.tv
  - hamsterstudio.org
  - kinozal.me
  - lostfilm.tv
  - newstudio.tv
  - nnmclub.to
  - pornolab.net
  - riperam.org
  - rustorka.com
  - rutor.info
  - rutracker.org
  - tfile.cc
- Слежение за релизерами
  - booktracker.org
  - nnm-club.ru
  - pornolab.net
  - rutracker.org
  - tfile.me
- Поиск новых серий (SD/HD 720/HD 1080 версии на выбор)
  - baibako.tv 
  - hamsterstudio.org
  - lostfilm.tv (+ собственное заркало)
  - newstudio.tv
- Работа через proxy (SOCKS5/HTTP)
- Управление торрент-клиентами (добавление/удаление раздач и файлов)
  - Transmission (через XML-RPC)
  - Deluge (через deluge-console)
  - qBittorrent
- Сервисы уведомлений:
  - E-mail
  - Prowl
  - Pushbullet
  - Pushover
  - Pushall
  - Telegram 
- RSS-лента
- Выполенение собственных скриптов после обновления раздачи

### Требования для установки:
* Веб-сервер (Apache, nginx, lighttpd)
* PHP (5.2 или выше) с поддержкой cURL и PDO
* MySQL, PostgreSQL, SQLite

### Technical Details
* Minimum PHP Version: 7.4.0
* Alpine Linux Support: 3.15+ (includes PHP 7.4.33)

### Docker hub:
https://hub.docker.com/r/alfonder/torrentmonitor

### Скриншоты:
 ![Screenshot0](https://habrastorage.org/webt/yy/xq/2g/yyxq2gn8o5-b68zr-m_acdv78w8.png "Screenshot0")
 ![Screenshot1](https://habrastorage.org/webt/do/fl/cd/doflcdnaxhg4elpis4jyg30tzik.png "Screenshot1")
 ![Screenshot2](https://habrastorage.org/webt/ad/m5/tk/adm5tktyrelde8fur565aprrpia.png "Screenshot2")
 ![Screenshot3](https://habrastorage.org/webt/5v/9n/ww/5v9nww4n2ahujooewnichz3emoa.png "Screenshot3")
 ![Screenshot4](https://habrastorage.org/webt/qs/i7/y5/qsi7y53vb8qnl0y0ifcrxbcvv78.png "Screenshot4")
 ![Screenshot5](https://habrastorage.org/webt/nz/n9/zd/nzn9zdlnhje6blm7dsbk7nnzxnk.png "Screenshot5")
 ![Screenshot6](https://habrastorage.org/webt/ta/wl/pz/tawlpzlptcv1frusl8lv_tyyc-u.png "Screenshot6")


