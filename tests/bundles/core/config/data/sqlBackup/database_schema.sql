CREATE DATABASE IF NOT EXISTS testDB;

USE testDB;

DROP TABLE IF EXISTS `testDB_table`,
 `testDB_table3`,
 `testDB_table2`;

CREATE TABLE `testDB_table2` (
  `id` INT(11) NOT NULL,
  `type` TINYINT(1) UNSIGNED,
  `type_cfg_id` SMALLINT(5) UNSIGNED,
  `aEnfants` TINYINT(1) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

CREATE TABLE `testDB_table3` (
  `id` INT(11) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

CREATE TABLE `testDB_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL,
  `date_creation` TIMESTAMP NOT NULL,
  `fk_id_table2` INT(11) NOT NULL,
  `fk_id_table3` INT(11) NOT NULL,
  PRIMARY KEY(`id`, `fk_id_table2`),
  CONSTRAINT fk_testDB_table2 FOREIGN KEY (fk_id_table2) REFERENCES testDB_table2(id),
  CONSTRAINT fk_testDB_table3 FOREIGN KEY (fk_id_table3) REFERENCES testDB_table3(id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

