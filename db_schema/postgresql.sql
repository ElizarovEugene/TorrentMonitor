/* 
Postgres схема: torrentmonitor
*/

-- Дамп таблицы buffer
-- ------------------------------------------------------------

CREATE SEQUENCE auto_id_buffer;

CREATE TABLE "buffer" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_buffer'),
  "user_id" INTEGER NOT NULL,
  "section" varchar(60) NOT NULL DEFAULT '',
  "threme_id" INTEGER NOT NULL,
  "threme" varchar(250) NOT NULL DEFAULT '',
  "accept" INTEGER NOT NULL DEFAULT '0',
  "downloaded" INTEGER NOT NULL DEFAULT '0',
  "new" INTEGER NOT NULL DEFAULT '1',
  "tracker" varchar(20) DEFAULT NULL
);


-- Дамп таблицы credentials
-- ------------------------------------------------------------

CREATE SEQUENCE "auto_id_credentials" START 16;

CREATE TABLE "credentials" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_credentials'),
  "tracker" varchar(30) DEFAULT NULL,
  "log" varchar(30) DEFAULT NULL,
  "pass" varchar(30) DEFAULT NULL,
  "cookie" varchar(255) DEFAULT NULL,
  "passkey" varchar(255) DEFAULT NULL
);

INSERT INTO credentials VALUES (1, 'rutracker.org', '', '', '', '');
INSERT INTO credentials VALUES (2, 'nnm-club.me', '', '', '', '');
INSERT INTO credentials VALUES (3, 'lostfilm.tv', '', '', '', '');
INSERT INTO credentials VALUES (4, 'novafilm.tv', '', '', '', '');
INSERT INTO credentials VALUES (5, 'rutor.org', ' ', ' ', '', '');
INSERT INTO credentials VALUES (6, 'tfile.me', ' ', ' ', '', '');
INSERT INTO credentials VALUES (7, 'kinozal.tv', '', '', '', '');
INSERT INTO credentials VALUES (8, 'anidub.com', '', '', '', '');
INSERT INTO credentials VALUES (9, 'baibako.tv', '', '', '', '');
INSERT INTO credentials VALUES (10,'casstudio.tv', '', '','', '');
INSERT INTO credentials VALUES (11,'newstudio.tv', '', '','', '');
INSERT INTO credentials VALUES (12,'animelayer.ru', '', '','', '');
INSERT INTO credentials VALUES (13,'tracker.0day.kiev.ua','','','', '');
INSERT INTO credentials VALUES (14,'rustorka.com','','','', '');
INSERT INTO credentials VALUES (15,'pornolab.net','','','', '');



-- Дамп таблицы news
-- ------------------------------------------------------------

CREATE SEQUENCE "auto_id_news" START 22;

CREATE TABLE "news" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_news'),
  "text" TEXT NOT NULL,
  "new" INTEGER NOT NULL DEFAULT '1'
);



-- Дамп таблицы settings
-- ------------------------------------------------------------

CREATE SEQUENCE "auto_id_settings" START 17;

CREATE TABLE "settings" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_settings'),
  "key" varchar(32) NOT NULL,
  "val" varchar(100) NOT NULL
);

INSERT INTO settings VALUES (3, 'send', '1');
INSERT INTO settings VALUES (5, 'password', '1f10c9fd49952a7055531975c06c5bd8');
INSERT INTO settings VALUES (6, 'auth', '1');
INSERT INTO settings VALUES (4, 'sendWarning', '0');
INSERT INTO settings VALUES (7, 'proxy', '0');
INSERT INTO settings VALUES (8, 'proxyAddress', '127.0.0.1:9050');
INSERT INTO settings VALUES (11, 'torrentAddress', '127.0.0.1:9091');
INSERT INTO settings VALUES (12, 'torrentLogin', '');
INSERT INTO settings VALUES (13, 'torrentPassword', '');
INSERT INTO settings VALUES (14, 'pathToDownload', '');
INSERT INTO settings VALUES (16, 'deleteOldFiles', '0');
INSERT INTO settings VALUES (10, 'torrentClient', '');
INSERT INTO settings VALUES (9, 'useTorrent', '0');
INSERT INTO settings VALUES (28, 'sendWarningPushover', '');
INSERT INTO settings VALUES (19, 'serverAddress', '');
INSERT INTO settings VALUES (20, 'deleteDistribution', '0');
INSERT INTO settings VALUES (27, 'sendWarningEmail', '');
INSERT INTO settings VALUES (24, 'sendUpdate', '0');
INSERT INTO settings VALUES (25, 'sendUpdateEmail', '');
INSERT INTO settings VALUES (26, 'sendUpdatePushover', '');
INSERT INTO settings VALUES (29, 'debug', '0');
INSERT INTO settings VALUES (30, 'rss', '1');



-- Дамп таблицы temp
-- ------------------------------------------------------------

CREATE SEQUENCE auto_id_temp;

CREATE TABLE "temp" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_temp'),
  "path" varchar(100) DEFAULT NULL,
  "hash" varchar(40) DEFAULT NULL,
  "tracker" varchar(30) DEFAULT NULL,
  "message" varchar(60) DEFAULT NULL,
  "date" varchar(120) DEFAULT NULL,
  UNIQUE(hash)
);


-- Дамп таблицы torrent
-- ------------------------------------------------------------

CREATE SEQUENCE auto_id_torrent;

CREATE TABLE "torrent" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_torrent'),
  "tracker" varchar(30) NOT NULL,
  "name" varchar(250) NOT NULL DEFAULT '',
  "hd" INTEGER NOT NULL DEFAULT '0',
  "path" varchar(100) NOT NULL,
  "torrent_id" varchar(150) NOT NULL DEFAULT '',
  "ep" varchar(10) DEFAULT '',
  "timestamp" timestamp,
  "auto_update" INTEGER NOT NULL DEFAULT '0',
  "hash" varchar(40) NOT NULL DEFAULT '0'
);


-- Дамп таблицы warning
-- ------------------------------------------------------------

CREATE SEQUENCE auto_id_warning;

CREATE TABLE "warning" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_warning'),
  "time" timestamp,
  "where" varchar(40) NOT NULL,
  "reason" varchar(200) NOT NULL
);


-- Дамп таблицы watch
-- ------------------------------------------------------------

CREATE SEQUENCE auto_id_watch;

CREATE TABLE "watch" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_watch'),
  "tracker" varchar(30) NOT NULL DEFAULT '',
  "name" varchar(30) NOT NULL DEFAULT ''
);
