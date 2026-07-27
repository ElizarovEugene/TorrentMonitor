# Установка в LAMP

Классическая установка TorrentMonitor на собственный веб-сервер.
Если вы предпочитаете контейнеры — смотрите [Установку в Docker](Installation-Docker),
это проще и быстрее.

---

## Системные требования

| Компонент | Варианты |
|---|---|
| Веб-сервер | Apache, nginx или lighttpd |
| PHP | 7.4 или новее |
| СУБД | MySQL/MariaDB, PostgreSQL или SQLite |

### Необходимые модули PHP

```
php-ctype php-curl php-iconv php-mbstring php-pdo php-simplexml php-xml php-zip
```

Плюс PDO-драйвер для выбранной СУБД:

- MySQL — `php-pdo_mysql`
- PostgreSQL — `php-pdo_pgsql`
- SQLite — `php-pdo_sqlite`

Пример установки в Debian/Ubuntu (PHP 8.x + MySQL):

```bash
sudo apt install nginx php-fpm php-curl php-mbstring php-xml php-zip php-mysql mariadb-server
```

---

## Шаг 1 — Скачать TorrentMonitor

Скачайте последний релиз с GitHub и распакуйте в каталог веб-сервера:

```bash
cd /var/www
wget https://github.com/ElizarovEugene/TorrentMonitor/archive/refs/heads/master.zip
unzip master.zip
mv TorrentMonitor-master torrentmonitor
```

## Шаг 2 — Создать базу данных

Схемы для всех трёх СУБД лежат в каталоге `db_schema/`.

**MySQL:**

```bash
mysql -u root -p -e "CREATE DATABASE torrentmonitor CHARACTER SET utf8; \
  CREATE USER 'torrentmonitor'@'localhost' IDENTIFIED BY 'ВАШ_ПАРОЛЬ'; \
  GRANT ALL ON torrentmonitor.* TO 'torrentmonitor'@'localhost';"
mysql -u torrentmonitor -p torrentmonitor < /var/www/torrentmonitor/db_schema/mysql.sql
```

**PostgreSQL:**

```bash
sudo -u postgres createuser -P torrentmonitor
sudo -u postgres createdb -O torrentmonitor torrentmonitor
psql -U torrentmonitor torrentmonitor < /var/www/torrentmonitor/db_schema/postgresql.sql
```

**SQLite** (самый простой вариант, отдельный сервер БД не нужен):

```bash
sqlite3 /var/www/torrentmonitor/torrentmonitor.sqlite < /var/www/torrentmonitor/db_schema/sqlite.sql
```

## Шаг 3 — Настроить подключение к БД

Скопируйте пример конфигурации и раскомментируйте блок своей СУБД:

```bash
cd /var/www/torrentmonitor
cp config.php.example config.php
```

**MySQL:**

```php
Config::write('db.host', 'localhost');
Config::write('db.type', 'mysql');
Config::write('db.charset', 'utf8');
Config::write('db.port', '3306');
Config::write('db.basename', 'torrentmonitor');
Config::write('db.user', 'torrentmonitor');
Config::write('db.password', 'ВАШ_ПАРОЛЬ');
```

**PostgreSQL:**

```php
Config::write('db.host', 'localhost');
Config::write('db.type', 'pgsql');
Config::write('db.port', '5432');
Config::write('db.basename', 'torrentmonitor');
Config::write('db.user', 'torrentmonitor');
Config::write('db.password', 'ВАШ_ПАРОЛЬ');
```

**SQLite** (указывайте **абсолютный** путь к файлу БД):

```php
Config::write('db.type', 'sqlite');
Config::write('db.basename', '/var/www/torrentmonitor/torrentmonitor.sqlite');
```

### Шифрование учётных данных (по желанию)

Если в `config.php` задать ключ шифрования, пароли трекеров и торрент-клиента будут
храниться в базе в зашифрованном виде (AES-256-GCM):

```php
Config::write('encryption.key', 'HEX_СТРОКА_64_СИМВОЛА');
```

Ключ можно сгенерировать командой `openssl rand -hex 32`. Без ключа приложение работает
как обычно, храня значения в открытом виде. Ключ не меняйте и не теряйте — без него
зашифрованные учётные данные прочитать нельзя.

## Шаг 4 — Права на файлы

Каталог должен принадлежать пользователю, от которого работает веб-сервер и PHP
(`www-data` в Debian/Ubuntu, `apache` в RHEL/CentOS, `nginx` в некоторых сборках):

```bash
chown -R www-data:www-data /var/www/torrentmonitor
```

## Шаг 5 — Настроить веб-сервер

Никаких особых правил переписывания адресов TorrentMonitor не требует —
достаточно обычного виртуального хоста с обработкой PHP.

Пример server-блока nginx (PHP-FPM):

```nginx
server {
    listen 80;
    server_name tm.example.com;
    root /var/www/torrentmonitor;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Для Apache достаточно обычного VirtualHost с `DocumentRoot /var/www/torrentmonitor`.

> ⚠️ Не открывайте TorrentMonitor напрямую в интернет без включённого входа по паролю
> (и желательно HTTPS): интерфейс управляет вашим торрент-клиентом и хранит учётные данные
> трекеров.

## Шаг 6 — Первый вход

Откройте адрес TorrentMonitor в браузере. Пароль по умолчанию:

```
torrentmonitor
```

**Сразу смените его** в «Настройки → Смена пароля».

## Шаг 7 — Настроить cron

Проверку трекеров выполняет скрипт `engine.php` — его нужно запускать по расписанию.
Добавьте в crontab пользователя веб-сервера (например, `sudo crontab -u www-data -e`):

```cron
*/10 * * * * php /var/www/torrentmonitor/engine.php >> /var/www/torrentmonitor/torrent_monitor_error.log 2>&1
```

Каждые 10 минут — разумный интервал; слишком частые запросы могут привести к бану
на трекерах. Ошибки будут копиться в `torrent_monitor_error.log`.

Проверить работу движка можно и вручную — из веб-интерфейса
(раздел «Запуск») или из консоли:

```bash
sudo -u www-data php /var/www/torrentmonitor/engine.php
```

---

## Что дальше

Продолжайте с [Первоначальной настройкой](Getting-Started): учётные данные трекеров,
подключение торрент-клиента и добавление первой раздачи.
