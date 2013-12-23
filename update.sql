INSERT INTO `settings` (`id`, `key`, `val`)
VALUES
	(7,'proxy','0'),
	(8,'proxyAddress','127.0.0.1:9050'),
	(9,'useTorrent','0'),
	(10,'torrentClient',''),
	(11,'torrentAddress',''),
	(12,'torrentLogin',''),
	(13,'torrentPassword',''),
	(14,'pathToDownload',''),
	(15,'deleteTorrent','0'),
	(16,'deleteOldFiles','0');
	
ALTER TABLE `torrent` ADD `hash2` VARCHAR(40) NOT NULL;