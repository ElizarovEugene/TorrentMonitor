DROP TABLE IF EXISTS `buffer`;

CREATE TABLE `buffer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `section` varchar(60) NOT NULL DEFAULT '',
  `threme_id` int(11) unsigned NOT NULL,
  `threme` varchar(500) NOT NULL DEFAULT '',
  `timestamp` date NOT NULL DEFAULT '2000-01-01',
  `accept` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `downloaded` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `new` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `tracker` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `credentials`;

CREATE TABLE `credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) DEFAULT NULL,
  `log` varchar(30) DEFAULT NULL,
  `pass` varchar(100) DEFAULT NULL,
  `cookie` varchar(255) DEFAULT NULL,
  `passkey` varchar(32) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `necessarily` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `credentials` WRITE;

INSERT INTO `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`, `passkey`, `type`, `necessarily`)
VALUES
	(1,'rutracker.org','','','',NULL,'forum',1),
	(2,'nnmclub.to','','','',NULL,'forum',1),
	(3,'lostfilm.tv','','','',NULL,'RSS',1),
	(5,'rutor.is',' ',' ',NULL,NULL,'forum',0),
	(6,'tfile.cc',' ',' ',NULL,NULL,'forum',0),
	(7,'kinozal.me','','','',NULL,'forum',1),
	(8,'anidub.com','','','',NULL,'forum',1),
	(9,'casstudio.tk','','','',NULL,'forum',1),
	(10,'baibako.tv','','','',NULL,'RSS',1),
	(11,'newstudio.tv','','','',NULL,'RSS',1),
	(12,'animelayer.ru','','','',NULL,'forum',1),
	(14,'rustorka.com','','','',NULL,'forum',1),
	(15,'pornolab.net','','','',NULL,'forum',1),
	(16,'lostfilm-mirror',' ',' ',NULL,'','RSS',0),
	(17,'hamsterstudio.org','','','',NULL,'RSS',1),
	(19,'booktracker.org','','','',NULL,'forum',1),
	(20,'baibako.tv_forum','','','',NULL,'forum',1),
	(21,'riperam.org','','','',NULL,'forum',1),
    (22,'kinozal.tv','','','',NULL,'forum',1),
    (23,'kinozal.guru','','','',NULL,'forum',1);

UNLOCK TABLES;


DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  `new` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(32) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `type` varchar(13) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `notifications` WRITE;

INSERT INTO `notifications` (`id`, `service`, `address`, `type`)
VALUES
	(1,'E-mail','', 'notification'),
	(2,'E-mail','', 'warning'),
	(3,'Prowl','', 'notification'),
	(4,'Prowl','', 'warning'),
	(5,'Pushbullet','', 'notification'),
	(6,'Pushbullet','', 'warning'),
	(7,'Pushover','', 'notification'),
	(8,'Pushover','', 'warning'),
	(9,'Pushall','', 'notification'),
	(10,'Pushall','', 'warning'),
	(11,'Telegram','', 'notification'),
	(12,'Telegram','', 'warning');

UNLOCK TABLES;


DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `id` int(3) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL,
  `val` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


LOCK TABLES `settings` WRITE;

INSERT INTO `settings` (`id`, `key`, `val`)
VALUES
	(3,'send','0'),
	(4,'sendWarning','0'),
	(5,'password','1f10c9fd49952a7055531975c06c5bd8'),
	(6,'auth','1'),
	(7,'proxy','0'),
	(8,'proxyAddress','127.0.0.1:9050'),
	(9,'useTorrent','0'),
	(10,'torrentClient',''),
	(11,'torrentAddress','127.0.0.1:9091'),
	(12,'torrentLogin',''),
	(13,'torrentPassword',''),
	(14,'pathToDownload',''),
	(16,'deleteOldFiles','0'),
	(19,'serverAddress',''),
	(20,'deleteDistribution','0'),
	(24,'sendUpdate','0'),
	(25,'sendWarning','0'),
	(29,'debug','0'),
	(30,'rss','1'),
	(31,'debugFor',''),
	(32,'httpTimeout','15'),
	(33,'sendUpdateService',''),
	(35,'sendWarningService',''),
	(37,'proxyType',''),
	(38,'autoUpdate','0'),
	(39,'sentUpdateNotification','0'),
	(40,'userAgent','Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0');

UNLOCK TABLES;


DROP TABLE IF EXISTS `temp`;

CREATE TABLE `temp` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `path` varchar(200) DEFAULT NULL,
  `tracker` varchar(30) DEFAULT NULL,
  `date` varchar(120) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `torrent`;

CREATE TABLE `torrent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL,
  `name` varchar(500) NOT NULL DEFAULT '',
  `hd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `path` varchar(200) DEFAULT NULL,
  `torrent_id` varchar(150) DEFAULT NULL,
  `ep` varchar(10) DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `auto_update` tinyint(11) unsigned NOT NULL DEFAULT '0',
  `hash` varchar(40) NOT NULL DEFAULT '',
  `script` varchar(100) DEFAULT NULL,
  `pause` int(1) unsigned NOT NULL DEFAULT '0',
  `error` int(1) unsigned NOT NULL DEFAULT '0',
  `closed` int(1) unsigned NOT NULL DEFAULT '0',  
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `warning`;

CREATE TABLE `warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `where` varchar(40) NOT NULL,
  `reason` varchar(200) NOT NULL,
  `t_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `watch`;

CREATE TABLE `watch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
