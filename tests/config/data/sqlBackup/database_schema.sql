CREATE DATABASE IF NOT EXISTS testDB;

USE testDB;

DROP TABLE IF EXISTS `testDB_table`,
 `testDB_table3`,
 `testDB_table2`;

CREATE TABLE `testDB_table2` (
  `id` INT NOT NULL,
  `type` TINYINT UNSIGNED,
  `type_cfg_id` SMALLINT UNSIGNED,
  `aEnfants` TINYINT NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

CREATE TABLE `testDB_table3` (
  `id` INT NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

CREATE TABLE `testDB_table` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL,
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
  `fk_id_table2` INT NOT NULL,
  `fk_id_table3` INT NOT NULL,
  PRIMARY KEY(`id`, `fk_id_table2`),
  CONSTRAINT fk_testDB_table2 FOREIGN KEY (fk_id_table2) REFERENCES testDB_table2(id),
  CONSTRAINT fk_testDB_table3 FOREIGN KEY (fk_id_table3) REFERENCES testDB_table3(id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;
