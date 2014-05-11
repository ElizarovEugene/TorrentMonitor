INSERT INTO "credentials" VALUES (13,'tracker.0day.kiev.ua','','','');
INSERT INTO "credentials" VALUES (14,'rustorka.com','','','');
	
CREATE TABLE `temp` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `path` varchar(100) NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '' UNIQUE,
  `tracker` varchar(30) NOT NULL DEFAULT '',
  `message` varchar(60) NOT NULL DEFAULT '',
  `date` varchar(120) NOT NULL DEFAULT '',
  UNIQUE KEY `hash` (`hash`)
)