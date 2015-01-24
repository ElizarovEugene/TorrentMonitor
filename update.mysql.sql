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

INSERT INTO `settings` (`id`, `key`, `val`)
VALUES
	(3,'send','1'),
	(5,'password','1f10c9fd49952a7055531975c06c5bd8'),
	(6,'auth','1'),
	(4,'sendWarning','0'),
	(8,'proxyAddress','127.0.0.1:9050'),
	(7,'proxy','0'),
	(13,'torrentPassword',''),
	(12,'torrentLogin',''),
	(11,'torrentAddress','127.0.0.1:9091'),
	(14,'pathToDownload',''),
	(16,'deleteOldFiles','0'),
	(10,'torrentClient',''),
	(9,'useTorrent','0'),
	(28,'sendWarningPushover',''),
	(19,'serverAddress',''),
	(20,'deleteDistribution','0'),
	(27,'sendWarningEmail',''),
	(24,'sendUpdate','0'),
	(25,'sendUpdateEmail',''),
	(26,'sendUpdatePushover','');

/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;