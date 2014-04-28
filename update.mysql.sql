INSERT INTO `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`)
VALUES
	(13,'tracker.0day.kiev.ua','','',''),
	(14,'rustorka.com','','','');
	
CREATE TABLE `temp` (
  `id` int(11) unsigned NOT NULL,
  `path` varchar(100) DEFAULT NULL,
  `hash` varchar(40) DEFAULT NULL,
  `tracker` varchar(30) DEFAULT NULL,
  `message` varchar(60) DEFAULT NULL,
  `date` varchar(120) DEFAULT NULL,
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;