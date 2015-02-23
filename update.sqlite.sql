CREATE TABLE `news` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `text` TEXT,
  `new` INTEGER NOT NULL DEFAULT '1'
);

DELETE FROM "settings" WHERE `id` = '1';
DELETE FROM "settings" WHERE `id` = '2';
DELETE FROM "settings" WHERE `id` = '4';
DELETE FROM "settings" WHERE `id` = '15';

INSERT INTO "settings" VALUES (4, 'sendWarning', '0');
INSERT INTO "settings" VALUES (19, 'serverAddress', '');
INSERT INTO "settings" VALUES (20, 'deleteDistribution', '0');
INSERT INTO "settings" VALUES (24, 'sendUpdate', '0');
INSERT INTO "settings" VALUES (25, 'sendUpdateEmail', '');
INSERT INTO "settings" VALUES (26, 'sendUpdatePushover', '');
INSERT INTO "settings" VALUES (27, 'sendWarningEmail', '');
INSERT INTO "settings" VALUES (28, 'sendWarningPushover', '');
INSERT INTO "settings" VALUES (29, 'debug', '0');
INSERT INTO "settings" VALUES (30, 'rss', '1');
INSERT INTO "settings" VALUES (2001, 'httpTimeout', '15');
INSERT INTO "settings" VALUES (3000,'smtp','0');	
INSERT INTO "settings" VALUES (3001,'smtpHost','');	
INSERT INTO "settings" VALUES (3002,'smtpPort','25');	
INSERT INTO "settings" VALUES (3003,'smtpSecure','0');	
INSERT INTO "settings" VALUES (3004,'smtpAuth','0');	
INSERT INTO "settings" VALUES (3005,'smtpUser','');	
INSERT INTO "settings" VALUES (3006,'smtpPassword','');	
INSERT INTO "settings" VALUES (3007,'smtpFrom','');	
INSERT INTO "settings" VALUES (3008,'smtpDebug','0');	

ALTER TABLE "torrent" ADD auto_update INTEGER NOT NULL DEFAULT '0';

ALTER TABLE "credentials" ADD passkey varchar(255) DEFAULT NULL;
