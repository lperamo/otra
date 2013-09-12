CREATE DATABASE IF NOT EXISTS lpcms;

USE lpcms;

DROP TABLE IF EXISTS `lpcms_header`;
CREATE TABLE `lpcms_header` (
  `id_header` INT NOT NULL AUTO_INCREMENT,
  `fichierImage` VARCHAR(255) NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_header`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_module`;
CREATE TABLE `lpcms_module` (
  `id_module` INT NOT NULL AUTO_INCREMENT,
  `type_module` INT NOT NULL,
  `position` INT NOT NULL,
  `ordre` INT NOT NULL,
  `droit` INT(1) NOT NULL,
  `contenu` VARCHAR(255),
  PRIMARY KEY(`id_module`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_footer`;
CREATE TABLE `lpcms_footer` (
  `id_footer` INT NOT NULL AUTO_INCREMENT,
  `fichierImage` VARCHAR(255),
  `texte` VARCHAR(255),
  PRIMARY KEY(`id_footer`, `fichierImage`, `texte`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_mailing_list`;
CREATE TABLE `lpcms_mailing_list` (
  `id_mailing_list` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `descr` VARCHAR(255),
  PRIMARY KEY(`id_mailing_list`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_user`;
CREATE TABLE `lpcms_user` (
  `id_user` INT NOT NULL AUTO_INCREMENT,
  `mail` VARCHAR(255) NOT NULL,
  `pwd` VARCHAR(40) NOT NULL,
  `pseudo` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_user`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_role`;
CREATE TABLE `lpcms_role` (
  `id_role` INT NOT NULL AUTO_INCREMENT,
  `mask` INT NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_role`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_article`;
CREATE TABLE `lpcms_article` (
  `id_article` INT NOT NULL AUTO_INCREMENT,
  `fk_id_module` INT NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `contenu` VARCHAR(255) NOT NULL,
  `droit` INT(1) NOT NULL,
  `date_creation` TIMESTAMP NOT NULL,
  `cree_par` INT NOT NULL,
  `derniere_modif` TIMESTAMP,
  `der_modif_par` INT,
  `derniere_visualisation` TIMESTAMP,
  `der_visualise_par` INT,
  `nb_vu` INT NOT NULL,
  `date_publication` TIMESTAMP,
  `meta` INT,
  `rank_sum` INT NOT NULL,
  `rank_count` INT NOT NULL,
  PRIMARY KEY(`id_article`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_elements_menu`;
CREATE TABLE `lpcms_elements_menu` (
  `id_elementsmenu` INT NOT NULL AUTO_INCREMENT,
  `fk_id_module` INT NOT NULL,
  `fk_id_article` INT NOT NULL,
  `parent` INT NOT NULL,
  `aEnfants` BOOLEAN NOT NULL,
  `droit` INT(1) NOT NULL,
  `ordre` INT NOT NULL,
  `contenu` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_elementsmenu`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_mailing_list_user`;
CREATE TABLE `lpcms_mailing_list_user` (
  `fk_id_mailing_list` INT NOT NULL,
  `fk_id_user` INT NOT NULL,
  PRIMARY KEY(`fk_id_mailing_list`, `fk_id_user`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_user_role`;
CREATE TABLE `lpcms_user_role` (
  `fk_id_user` INT NOT NULL,
  `fk_id_role` INT NOT NULL,
  PRIMARY KEY(`fk_id_user`, `fk_id_role`) 
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

