INSERT INTO credentials VALUES (13,'tracker.0day.kiev.ua','','','');
INSERT INTO credentials VALUES (14,'rustorka.com','','','');
	
CREATE SEQUENCE auto_id_temp;

CREATE TABLE "temp" (
  "id" INTEGER PRIMARY KEY NOT NULL DEFAULT nextval('auto_id_temp'),
  "path" varchar(100) DEFAULT NULL,
  "hash" varchar(40) DEFAULT NULL,
  "tracker" varchar(30) DEFAULT NULL,
  "message" varchar(60) DEFAULT NULL,
  "date" varchar(120) DEFAULT NULL,
  UNIQUE(hash)
)