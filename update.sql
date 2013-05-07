INSERT INTO credentials VALUES (7, 'kinozal.tv', '', '');
DELETE FROM credentials WHERE id = 8;
INSERT INTO credentials VALUES (8, 'anidub.com', '', '');
DELETE FROM settings WHERE id = 7;
INSERT INTO settings VALUES (7, 'download', '1');

ALTER TABLE `credentials` ADD `cookie` VARCHAR(255) NULL;
