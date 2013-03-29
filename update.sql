INSERT INTO credentials VALUES (7, 'kinozal.tv', '', '');
DELETE FROM credentials WHERE id = 8;
INSERT INTO credentials VALUES (8, 'anidub.com', '', '');

ALTER TABLE `credentials` ADD `cookie` VARCHAR(255) NULL;