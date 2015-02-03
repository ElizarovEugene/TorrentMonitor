CREATE SEQUENCE "auto_id_news" START 22;

CREATE TABLE "news" (
  "id" INTEGER  PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_news'),
  "text" TEXT NOT NULL,
  "new" INTEGER NOT NULL DEFAULT '1'
);

DELETE FROM settings WHERE "id" = '1';
DELETE FROM settings WHERE "id" = '2';
DELETE FROM settings WHERE "id" = '4';
DELETE FROM settings WHERE "id" = '15';

INSERT INTO settings VALUES (4, 'sendWarning', '0');
INSERT INTO settings VALUES (19, 'serverAddress', '');
INSERT INTO settings VALUES (20, 'deleteDistribution', '0');
INSERT INTO settings VALUES (24, 'sendUpdate', '0');
INSERT INTO settings VALUES (25, 'sendUpdateEmail', '');
INSERT INTO settings VALUES (26, 'sendUpdatePushover', '');
INSERT INTO settings VALUES (27, 'sendWarningEmail', '');
INSERT INTO settings VALUES (28, 'sendWarningPushover', '');
INSERT INTO settings VALUES (29, 'debug', '0');
INSERT INTO settings VALUES (30, 'rss', '1');

ALTER TABLE torrent ADD COLUMN auto_update INTEGER NOT NULL DEFAULT '0';

ALTER TABLE credentials ADD COLUMN passkey varchar(255) DEFAULT NULL;
