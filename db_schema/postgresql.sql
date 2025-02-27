CREATE SEQUENCE auto_id_buffer;

CREATE TABLE "buffer" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_buffer'),
  "user_id" INTEGER NOT NULL,
  "section" varchar(60) NOT NULL DEFAULT '',
  "threme_id" INTEGER NOT NULL,
  "threme" varchar(250) NOT NULL DEFAULT '',
  "timestamp" timestamp,
  "accept" INTEGER NOT NULL DEFAULT '0',
  "downloaded" INTEGER NOT NULL DEFAULT '0',
  "new" INTEGER NOT NULL DEFAULT '1',
  "tracker" varchar(20) DEFAULT NULL
);


CREATE SEQUENCE "auto_id_credentials" START 16;

CREATE TABLE "credentials" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_credentials'),
  "tracker" varchar(30) DEFAULT NULL,
  "log" varchar(30) DEFAULT NULL,
  "pass" varchar(100) DEFAULT NULL,
  "cookie" varchar(255) DEFAULT NULL,
  "passkey" varchar(255) DEFAULT NULL,
  "type" varchar(32) DEFAULT NULL,
  "necessarily" INTEGER NOT NULL DEFAULT '1'
);

INSERT INTO credentials VALUES (1, 'rutracker.org', '', '', '', '','forum',1);
INSERT INTO credentials VALUES (2, 'nnmclub.to', '', '', '', '','forum',1);
INSERT INTO credentials VALUES (3, 'lostfilm.tv', '', '', '', '','RSS',1);
INSERT INTO credentials VALUES (5, 'rutor.is', ' ', ' ', '', '','forum',0);
INSERT INTO credentials VALUES (6, 'tfile.cc', ' ', ' ', '', '','forum',0);
INSERT INTO credentials VALUES (7, 'kinozal.me', '', '', '', '','forum',1);
INSERT INTO credentials VALUES (8, 'anidub.com', '', '', '', '','forum',1);
INSERT INTO credentials VALUES (9, 'baibako.tv', '', '', '', '','RSS',1);
INSERT INTO credentials VALUES (10,'casstudio.tk', '', '','', '','forum',1);
INSERT INTO credentials VALUES (11,'newstudio.tv', '', '','', '','RSS',1);
INSERT INTO credentials VALUES (12,'animelayer.ru', '', '','', '','forum',1);
INSERT INTO credentials VALUES (14,'rustorka.com','','','', '','forum',1);
INSERT INTO credentials VALUES (15,'pornolab.net','','','', '','forum',1);
INSERT INTO credentials VALUES (16,'lostfilm-mirror',' ',' ','', '','RSS',0);
INSERT INTO credentials VALUES (17,'hamsterstudio.org','','','', '','RSS',1);
INSERT INTO credentials VALUES (19,'booktracker.org','','','', '','forum',1);
INSERT INTO credentials VALUES (20,'baibako.tv_forum','','','', '','forum',1);
INSERT INTO credentials VALUES (21,'riperam.org','','','', '','forum',1);
INSERT INTO credentials VALUES (22,'kinozal.tv','','','', '','forum',1);
INSERT INTO credentials VALUES (23,'kinozal.guru','','','', '','forum',1);

CREATE SEQUENCE "auto_id_news" START 22;

CREATE TABLE "news" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_news'),
  "text" TEXT NOT NULL,
  "new" INTEGER NOT NULL DEFAULT '1'
);


CREATE SEQUENCE "auto_id_notifications" START 1;

CREATE TABLE "notifications" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_notifications'),
  "service" varchar(32) NOT NULL,
  "address" varchar(100) NOT NULL,
  "type" varchar(13) NOT NULL
);

INSERT INTO notifications VALUES (1, 'E-mail', '', 'notification');
INSERT INTO notifications VALUES (2, 'E-mail', '', 'warning');
INSERT INTO notifications VALUES (3, 'Prowl', '', 'notification');
INSERT INTO notifications VALUES (4, 'Prowl', '', 'warning');
INSERT INTO notifications VALUES (5, 'Pushbullet', '', 'notification');
INSERT INTO notifications VALUES (6, 'Pushbullet', '', 'warning');
INSERT INTO notifications VALUES (7, 'Pushover', '', 'notification');
INSERT INTO notifications VALUES (8, 'Pushover', '', 'warning');
INSERT INTO notifications VALUES (9, 'Pushall', '', 'notification');
INSERT INTO notifications VALUES (10, 'Pushall', '', 'warning');
INSERT INTO notifications VALUES (11, 'Telegram', '', 'notification');
INSERT INTO notifications VALUES (12, 'Telegram', '', 'warning');

CREATE SEQUENCE "auto_id_settings" START 17;

CREATE TABLE "settings" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_settings'),
  "key" varchar(32) NOT NULL,
  "val" varchar(100) NOT NULL
);

INSERT INTO settings VALUES (3, 'send', '0');
INSERT INTO settings VALUES (4, 'sendWarning', '0');
INSERT INTO settings VALUES (5, 'password', '1f10c9fd49952a7055531975c06c5bd8');
INSERT INTO settings VALUES (6, 'auth', '1');
INSERT INTO settings VALUES (7, 'proxy', '0');
INSERT INTO settings VALUES (8, 'proxyAddress', '127.0.0.1:9050');
INSERT INTO settings VALUES (9, 'useTorrent', '0');
INSERT INTO settings VALUES (10, 'torrentClient', '');
INSERT INTO settings VALUES (11, 'torrentAddress', '127.0.0.1:9091');
INSERT INTO settings VALUES (12, 'torrentLogin', '');
INSERT INTO settings VALUES (13, 'torrentPassword', '');
INSERT INTO settings VALUES (14, 'pathToDownload', '');
INSERT INTO settings VALUES (16, 'deleteOldFiles', '0');
INSERT INTO settings VALUES (19, 'serverAddress', '');
INSERT INTO settings VALUES (20, 'deleteDistribution', '0');
INSERT INTO settings VALUES (24, 'sendUpdate', '0');
INSERT INTO settings VALUES (25, 'sendWarning', '0');
INSERT INTO settings VALUES (29, 'debug', '0');
INSERT INTO settings VALUES (30, 'rss', '1');
INSERT INTO settings VALUES (31, 'debugFor', '');
INSERT INTO settings VALUES (32, 'httpTimeout', '15');
INSERT INTO settings VALUES (33, 'sendUpdateService', '');
INSERT INTO settings VALUES (35, 'sendWarningService', '');
INSERT INTO settings VALUES (37, 'proxyType', '');
INSERT INTO settings VALUES (38, 'autoUpdate', '0');
INSERT INTO settings VALUES (39, 'sentUpdateNotification', '0');
INSERT INTO settings VALUES (40, 'userAgent', 'Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0');

CREATE TABLE "temp" (
  "id" INTEGER PRIMARY KEY NOT NULL,
  "name" varchar(200) DEFAULT NULL,
  "path" varchar(200) DEFAULT NULL,
  "tracker" varchar(30) DEFAULT NULL,
  "date" varchar(120) DEFAULT NULL,
  UNIQUE(id)
);


CREATE SEQUENCE auto_id_torrent;

CREATE TABLE "torrent" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_torrent'),
  "tracker" varchar(30) NOT NULL,
  "name" varchar(500) NOT NULL DEFAULT '',
  "hd" INTEGER NOT NULL DEFAULT '0',
  "path" varchar(200) NOT NULL,
  "torrent_id" varchar(150) NOT NULL DEFAULT '',
  "ep" varchar(10) DEFAULT '',
  "timestamp" timestamp,
  "auto_update" INTEGER NOT NULL DEFAULT '0',
  "hash" varchar(40) NOT NULL DEFAULT '0',
  "script" varchar(100) NOT NULL DEFAULT '0',
  "pause" INTEGER NOT NULL DEFAULT '0',
  "error" INTEGER NOT NULL DEFAULT '0',
  "closed" INTEGER NOT NULL DEFAULT '0'
);


CREATE SEQUENCE auto_id_warning;

CREATE TABLE "warning" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_warning'),
  "time" timestamp,
  "where" varchar(40) NOT NULL,
  "reason" varchar(200) NOT NULL,
  "t_id" INTEGER DEFAULT NULL
);


CREATE SEQUENCE auto_id_watch;

CREATE TABLE "watch" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_watch'),
  "tracker" varchar(30) NOT NULL DEFAULT '',
  "name" varchar(30) NOT NULL DEFAULT ''
);
