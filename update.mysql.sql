# Дамп таблицы news
# ------------------------------------------------------------

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  `new` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;

DELETE FROM `settings` WHERE `id` = '1';
DELETE FROM `settings` WHERE `id` = '2';
DELETE FROM `settings` WHERE `id` = '4';
DELETE FROM `settings` WHERE `id` = '15';

INSERT INTO `settings` (`id`, `key`, `val`)
VALUES
	(4,'sendWarning','0'),
	(19,'serverAddress',''),
	(20,'deleteDistribution','0'),
	(24,'sendUpdate','0'),
	(25,'sendUpdateEmail',''),
	(26,'sendUpdatePushover',''),
	(27,'sendWarningEmail',''),
	(28,'sendWarningPushover',''),
	(29,'debug','0'),
	(30,'rss','1'),	
    (2001, 'httpTimeout', '15'),
	(3000,'smtp','0'),	
	(3001,'smtpHost',''),	
	(3002,'smtpPort','25'),	
	(3003,'smtpSecure','0'),	
	(3004,'smtpAuth','0'),	
	(3005,'smtpUser',''),	
	(3006,'smtpPassword',''),	
	(3007,'smtpFrom',''),	
	(3008,'smtpDebug','0');	


/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

ALTER TABLE `torrent` ADD auto_update tinyint(1) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `credentials` ADD passkey varchar(255) DEFAULT NULL;

DELETE FROM `credentials` WHERE `id` = '3';
DELETE FROM `torrent` WHERE `tracker` = 'lostfilm.tv';
DELETE FROM `warning` WHERE `tracker` = 'lostfilm.tv';
