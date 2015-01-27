CREATE TABLE `news` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `text` TEXT,
  `new` INTEGER NOT NULL DEFAULT '1'
);

INSERT INTO "settings" VALUES (3, 'send', '1');
INSERT INTO "settings" VALUES (5, 'password', '1f10c9fd49952a7055531975c06c5bd8');
INSERT INTO "settings" VALUES (6, 'auth', '1');
INSERT INTO "settings" VALUES (4, 'sendWarning', '0');
INSERT INTO "settings" VALUES (7, 'proxy', '0');
INSERT INTO "settings" VALUES (8, 'proxyAddress', '127.0.0.1:9050');
INSERT INTO "settings" VALUES (11, 'torrentAddress', '127.0.0.1:9091');
INSERT INTO "settings" VALUES (12, 'torrentLogin', '');
INSERT INTO "settings" VALUES (13, 'torrentPassword', '');
INSERT INTO "settings" VALUES (14, 'pathToDownload', '');
INSERT INTO "settings" VALUES (16, 'deleteOldFiles', '0');
INSERT INTO "settings" VALUES (10, 'torrentClient', '');
INSERT INTO "settings" VALUES (9, 'useTorrent', '0');
INSERT INTO "settings" VALUES (28, 'sendWarningPushover', '');
INSERT INTO "settings" VALUES (19, 'serverAddress', '');
INSERT INTO "settings" VALUES (20, 'deleteDistribution', '0');
INSERT INTO "settings" VALUES (27, 'sendWarningEmail', '');
INSERT INTO "settings" VALUES (24, 'sendUpdate', '0');
INSERT INTO "settings" VALUES (25, 'sendUpdateEmail', '');
INSERT INTO "settings" VALUES (26, 'sendUpdatePushover', '');