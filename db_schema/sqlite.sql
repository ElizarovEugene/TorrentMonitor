CREATE TABLE `buffer` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `user_id` INTEGER NOT NULL,
  `section` varchar(60) NOT NULL DEFAULT '',
  `threme_id` INTEGER NOT NULL,
  `threme` varchar(250) NOT NULL DEFAULT '',
  `accept` INTEGER NOT NULL DEFAULT '0',
  `downloaded` INTEGER NOT NULL DEFAULT '0',
  `new` INTEGER NOT NULL DEFAULT '1',
  `tracker` varchar(20) DEFAULT NULL
);

CREATE TABLE `credentials` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `tracker` varchar(30) DEFAULT NULL,
  `log` varchar(30) DEFAULT NULL,
  `pass` varchar(30) DEFAULT NULL,
  `cookie` varchar(255) DEFAULT NULL
);

INSERT INTO "credentials" VALUES (1, 'rutracker.org', '', '', '');
INSERT INTO "credentials" VALUES (2, 'nnm-club.me', '', '', '');
INSERT INTO "credentials" VALUES (3, 'lostfilm.tv', '', '', '');
INSERT INTO "credentials" VALUES (4, 'novafilm.tv', '', '', '');
INSERT INTO "credentials" VALUES (5, 'rutor.org', ' ', ' ', '');
INSERT INTO "credentials" VALUES (6, 'tfile.me', ' ', ' ', '');
INSERT INTO "credentials" VALUES (7, 'kinozal.tv', '', '', '');
INSERT INTO "credentials" VALUES (8, 'anidub.com', '', '', '');
INSERT INTO "credentials" VALUES (9, 'baibako.tv', '', '', '');
INSERT INTO "credentials" VALUES (10,'casstudio.tv', '', '','');
INSERT INTO "credentials" VALUES (11,'newstudio.tv', '', '','');
INSERT INTO "credentials" VALUES (12,'animelayer.ru', '', '','');

CREATE TABLE `settings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `key` varchar(32) NOT NULL,
  `val` varchar(100) NOT NULL
);

INSERT INTO "settings" VALUES (1, 'email', '');
INSERT INTO "settings" VALUES (2, 'path', '');
INSERT INTO "settings" VALUES (3, 'send', '1');
INSERT INTO "settings" VALUES (4, 'send_warning', '0');
INSERT INTO "settings" VALUES (5, 'password', '1f10c9fd49952a7055531975c06c5bd8');
INSERT INTO "settings" VALUES (6, 'auth', '1');
INSERT INTO "settings" VALUES (7, 'proxy', '0');
INSERT INTO "settings" VALUES (8, 'proxyAddress', '127.0.0.1:9050');
INSERT INTO "settings" VALUES (9, 'useTorrent', '0');
INSERT INTO "settings" VALUES (10, 'torrentClient', '');
INSERT INTO "settings" VALUES (11, 'torrentAddress', '');
INSERT INTO "settings" VALUES (12, 'torrentLogin', '');
INSERT INTO "settings" VALUES (13, 'torrentPassword', '');
INSERT INTO "settings" VALUES (14, 'pathToDownload', '');
INSERT INTO "settings" VALUES (15, 'deleteTorrent', '0');
INSERT INTO "settings" VALUES (16, 'deleteOldFiles', '0');

CREATE TABLE `torrent` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `tracker` varchar(30) NOT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `hd` INTEGER NOT NULL DEFAULT '0',
  `torrent_id` INTEGER NOT NULL DEFAULT '0',
  `ep` varchar(10) DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hash` varchar(40) NOT NULL DEFAULT ''
);

CREATE TABLE `warning` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `time` datetime NOT NULL,
  `where` varchar(40) NOT NULL,
  `reason` varchar(200) NOT NULL
);

CREATE TABLE `watch` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `tracker` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT ''
);
