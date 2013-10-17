# ************************************************************
# Схема: torrentmonitor
# Время создания: 2012-11-03 18:31:11 +0000
# ************************************************************

# Дамп таблицы buffer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `buffer`;

CREATE TABLE `buffer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `section` varchar(60) NOT NULL DEFAULT '',
  `threme_id` int(11) unsigned NOT NULL,
  `threme` varchar(250) NOT NULL DEFAULT '',
  `accept` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `downloaded` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `new` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `tracker` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

# Дамп таблицы credentials
# ------------------------------------------------------------

DROP TABLE IF EXISTS `credentials`;

CREATE TABLE `credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) DEFAULT NULL,
  `log` varchar(30) DEFAULT NULL,
  `pass` varchar(30) DEFAULT NULL,
  `cookie` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `credentials` WRITE;
/*!40000 ALTER TABLE `credentials` DISABLE KEYS */;

INSERT INTO `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`)
VALUES
	(1,'rutracker.org','','',''),
	(2,'nnm-club.me','','',''),
	(3,'lostfilm.tv','','',''),
	(4,'novafilm.tv','','',''),
	(5,'rutor.org', ' ', ' ',''),
	(6,'tfile.me', ' ', ' ',''),
	(7,'kinozal.tv', '', '',''),
	(8,'anidub.com', '', '','');

/*!40000 ALTER TABLE `credentials` ENABLE KEYS */;
UNLOCK TABLES;


# Дамп таблицы settings
# ------------------------------------------------------------

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL,
  `val` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;

INSERT INTO `settings` (`id`, `key`, `val`)
VALUES
	(1,'email',''),
	(2,'path',''),
	(3,'send','1'),
	(5,'password','1f10c9fd49952a7055531975c06c5bd8'),
	(6,'auth','1'),
	(4,'send_warning','0');

/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;


# Дамп таблицы torrent
# ------------------------------------------------------------

DROP TABLE IF EXISTS `torrent`;

CREATE TABLE `torrent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `hd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `torrent_id` int(9) unsigned NOT NULL DEFAULT '0',
  `ep` varchar(10) DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

# Дамп таблицы warning
# ------------------------------------------------------------

DROP TABLE IF EXISTS `warning`;

CREATE TABLE `warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `where` varchar(40) NOT NULL,
  `reason` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Дамп таблицы watch
# ------------------------------------------------------------

DROP TABLE IF EXISTS `watch`;

CREATE TABLE `watch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;