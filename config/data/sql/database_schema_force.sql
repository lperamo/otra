CREATE DATABASE lpcms;

USE lpcms;

DROP TABLE IF EXISTS `lpcms_article`;
CREATE TABLE `lpcms_article` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL,
  `contenu` VARCHAR(255) NOT NULL,
  `droit` INT(1) NOT NULL,
  `date_creation` TIMESTAMP NOT NULL,
  `cree_par` INT(11) NOT NULL,
  `derniere_modif` TIMESTAMP,
  `der_modif_par` INT(11),
  `derniere_visualisation` TIMESTAMP,
  `der_visualise_par` INT(11),
  `nb_vu` INT(11) NOT NULL,
  `date_publication` TIMESTAMP,
  `meta` INT(11),
  `rank_sum` INT(11) NOT NULL,
  `rank_count` INT(11) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_footer`;
CREATE TABLE `lpcms_footer` (
  `id_footer` INT(11) NOT NULL AUTO_INCREMENT,
  `fichierImage` VARCHAR(255) NOT NULL,
  `texte` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_footer`, `fichierImage`, `texte`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_header`;
CREATE TABLE `lpcms_header` (
  `id_header` INT(11) NOT NULL AUTO_INCREMENT,
  `fichierImage` VARCHAR(255) NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_header`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_mailing_list`;
CREATE TABLE `lpcms_mailing_list` (
  `id_mailing_list` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `descr` VARCHAR(255),
  PRIMARY KEY(`id_mailing_list`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_module`;
CREATE TABLE `lpcms_module` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` INT(11) NOT NULL,
  `position` INT(11) NOT NULL,
  `ordre` INT(11) NOT NULL,
  `droit` INT(1) NOT NULL,
  `contenu` VARCHAR(255),
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_role`;
CREATE TABLE `lpcms_role` (
  `id_role` INT(11) NOT NULL AUTO_INCREMENT,
  `mask` INT(11) NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id_role`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_elements_menu`;
CREATE TABLE `lpcms_elements_menu` (
  `id` INT(11) NOT NULL,
  `fk_id_article` INT(11) NOT NULL,
  `parent` INT(11) NOT NULL,
  `aEnfants` TINYINT(1) NOT NULL,
  `droit` INT(1) NOT NULL,
  `contenu` VARCHAR(255) NOT NULL,
  PRIMARY KEY(`id`),
  CONSTRAINT lpcms_elements_menu_ibfk_2 FOREIGN KEY (fk_id_article) REFERENCES lpcms_article(id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_user`;
CREATE TABLE `lpcms_user` (
  `id_user` INT(11) NOT NULL AUTO_INCREMENT,
  `mail` VARCHAR(255) NOT NULL,
  `pwd` VARCHAR(60) NOT NULL,
  `pseudo` VARCHAR(255) NOT NULL,
  `role_id` INT(11),
  PRIMARY KEY(`id_user`),
  CONSTRAINT lpcms_user_role FOREIGN KEY (role_id) REFERENCES lpcms_role(id_role)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_user_role`;
CREATE TABLE `lpcms_user_role` (
  `fk_id_user` INT(11) NOT NULL,
  `fk_id_role` INT(11) NOT NULL,
  PRIMARY KEY(`fk_id_user`, `fk_id_role`),
  CONSTRAINT lpcms_user_role_ibfk_1 FOREIGN KEY (fk_id_role) REFERENCES lpcms_role(id_role)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_bind_em_module`;
CREATE TABLE `lpcms_bind_em_module` (
  `fk_id_module` INT(11) NOT NULL,
  `fk_id_elements_menu` INT(11) NOT NULL,
  `ordre` INT(11) NOT NULL,
  PRIMARY KEY(`fk_id_module`, `fk_id_elements_menu`),
  CONSTRAINT fk_bemm_module0 FOREIGN KEY (fk_id_module) REFERENCES lpcms_module(id),
  CONSTRAINT fk_bemm_article0 FOREIGN KEY (fk_id_elements_menu) REFERENCES lpcms_elements_menu(id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS `lpcms_mailing_list_user`;
CREATE TABLE `lpcms_mailing_list_user` (
  `fk_id_mailing_list` INT(11) NOT NULL,
  `fk_id_user` INT(11) NOT NULL,
  PRIMARY KEY(`fk_id_mailing_list`, `fk_id_user`),
  CONSTRAINT lpcms_mailing_list_user_ibfk_1 FOREIGN KEY (fk_id_mailing_list) REFERENCES lpcms_mailing_list(id_mailing_list),
  CONSTRAINT lpcms_mailing_list_user_ibfk_2 FOREIGN KEY (fk_id_user) REFERENCES lpcms_user(id_user)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8;

