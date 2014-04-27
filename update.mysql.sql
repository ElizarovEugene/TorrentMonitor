INSERT INTO `credentials` (`id`, `tracker`, `log`, `pass`, `cookie`)
VALUES
	(12,'animelayer.ru', '', '','');
ALTER TABLE `torrent` ADD `path` VARCHAR(100) AFTER `HD`