/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;



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



DROP TABLE IF EXISTS `credentials`;

CREATE TABLE `credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) DEFAULT NULL,
  `log` varchar(30) DEFAULT NULL,
  `pass` varchar(30) DEFAULT NULL,
  `cookie` varchar(255) DEFAULT NULL,
  `passkey` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



LOCK TABLES `credentials` WRITE;
/*!40000 ALTER TABLE `credentials` DISABLE KEYS */;

INSERT INTO `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`, `passkey`)
VALUES
	(1,'rutracker.org','','','', ''),
	(2,'nnm-club.me','','','', ''),
	(3,'lostfilm.tv','','','', ''),
	(4,'novafilm.tv','','','', ''),
	(5,'rutor.org',' ',' ', '', ''),
	(6,'tfile.me',' ',' ', '', ''),
	(7,'kinozal.tv','','','', ''),
	(8,'anidub.com','','','', ''),
	(9,'casstudio.tv','','','', ''),
	(10,'baibako.tv','','','', ''),
	(11,'newstudio.tv','','','', ''),
	(12,'animelayer.ru','','','', ''),
	(13,'tracker.0day.kiev.ua','','','', ''),
	(15,'pornolab.net','','','', ''),
	(14,'rustorka.com','','','', '');

/*!40000 ALTER TABLE `credentials` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text,
  `new` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



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
	(26,'sendUpdatePushover',''),
	(29,'debug','0'),
	(30,'rss','1');

/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `temp`;

CREATE TABLE `temp` (
  `id` int(11) unsigned NOT NULL,
  `path` varchar(100) DEFAULT NULL,
  `hash` varchar(40) DEFAULT NULL,
  `tracker` varchar(30) DEFAULT NULL,
  `message` varchar(60) DEFAULT NULL,
  `date` varchar(120) DEFAULT NULL,
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `torrent`;

CREATE TABLE `torrent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `hd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `path` varchar(100) DEFAULT NULL,
  `torrent_id` varchar(150) DEFAULT NULL,
  `ep` varchar(10) DEFAULT '',
  `timestamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `auto_update` tinyint(11) unsigned NOT NULL DEFAULT '0',
  `hash` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `warning`;

CREATE TABLE `warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `where` varchar(40) NOT NULL,
  `reason` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `watch`;

CREATE TABLE `watch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tracker` varchar(30) NOT NULL DEFAULT '',
  `name` varchar(30) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
