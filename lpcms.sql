CREATE DATABASE IF NOT EXISTS lpcms;

USE lpcms;
-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le : Lun 18 Février 2013 à 19:25
-- Version du serveur: 5.5.29
-- Version de PHP: 5.3.10-1ubuntu3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `lpcms`
--

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_article`
--

CREATE TABLE IF NOT EXISTS `lpcms_article` (
  `id_article` int(11) NOT NULL AUTO_INCREMENT,
  `fk_id_module` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` varchar(255) NOT NULL,
  `droit` int(1) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cree_par` int(11) NOT NULL,
  `derniere_modif` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `der_modif_par` int(11) DEFAULT NULL,
  `derniere_visualisation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `der_visualise_par` int(11) DEFAULT NULL,
  `nb_vu` int(11) NOT NULL,
  `date_publication` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `meta` int(11) DEFAULT NULL,
  `rank_sum` int(11) NOT NULL,
  `rank_count` int(11) NOT NULL,
  PRIMARY KEY (`id_article`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Contenu de la table `lpcms_article`
--

INSERT INTO `lpcms_article` (`id_article`, `fk_id_module`, `titre`, `contenu`, `droit`, `date_creation`, `cree_par`, `derniere_modif`, `der_modif_par`, `derniere_visualisation`, `der_visualise_par`, `nb_vu`, `date_publication`, `meta`, `rank_sum`, `rank_count`) VALUES
(1, 6, 'Accessoires', 'accessoires', 2, '2010-12-30 23:00:00', 0, '2010-12-30 23:00:00', 0, '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 0, 0),
(2, 6, 'Parchemins', 'parchemins', 2, '2010-12-30 23:00:00', 0, '2010-12-30 23:00:00', 0, '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 0, 0),
(3, 6, 'Potions', 'potions', 2, '2010-12-30 23:00:00', 0, '2010-12-30 23:00:00', 0, '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 0, 0),
(4, 7, 'Deuxième article', 'article2', 2, '2010-12-30 23:00:00', 0, '2010-12-30 23:00:00', 0, '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 0, 0),
(5, 8, 'Troisième article', 'article3', 2, '2010-12-30 23:00:00', 0, '2010-12-30 23:00:00', 0, '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_elements_menu`
--

CREATE TABLE IF NOT EXISTS `lpcms_elements_menu` (
  `id_elementsmenu` int(11) NOT NULL AUTO_INCREMENT,
  `fk_id_module` int(11) NOT NULL,
  `fk_id_article` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `aEnfants` tinyint(1) NOT NULL,
  `droit` int(1) NOT NULL,
  `ordre` int(11) NOT NULL,
  `contenu` varchar(255) NOT NULL,
  PRIMARY KEY (`id_elementsmenu`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

--
-- Contenu de la table `lpcms_elements_menu`
--

INSERT INTO `lpcms_elements_menu` (`id_elementsmenu`, `fk_id_module`, `fk_id_article`, `parent`, `aEnfants`, `droit`, `ordre`, `contenu`) VALUES
(1, 1, 4, -1, 0, 1, 0, 'Forum'),
(2, 1, 4, -1, 0, 1, 1, 'Jeu'),
(3, 1, 5, -1, 0, 1, 2, 'News'),
(4, 1, 4, -1, 0, 2, 3, 'L''équipe'),
(5, 3, 1, -1, 1, 1, 0, 'Accessoires'),
(6, 3, 2, 6, 0, 1, 0, 'Parchemins'),
(7, 3, 3, 6, 0, 1, 0, 'Potions'),
(8, 3, 4, -1, 0, 1, 1, 'Armes'),
(9, 3, 5, -1, 0, 1, 2, 'Aromathérapie'),
(10, 3, 5, -1, 0, 1, 3, 'Divers'),
(11, 3, 5, -1, 0, 1, 4, 'Histoire'),
(12, 3, 5, -1, 0, 1, 5, 'Lithothérapie'),
(13, 3, 5, -1, 0, 1, 6, 'Objets Magiques'),
(14, 3, 5, -1, 0, 1, 7, 'Sorts'),
(15, 4, 5, -1, 0, 1, 0, 'Documentation'),
(16, 4, 5, -1, 0, 1, 1, 'Forum'),
(17, 4, 5, -1, 0, 1, 2, 'Profil'),
(18, 4, 5, -1, 0, 0, 3, 'Soumettre un article'),
(19, 4, 5, -1, 0, 1, 4, 'Se déconnecter');

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_footer`
--

CREATE TABLE IF NOT EXISTS `lpcms_footer` (
  `id_footer` int(11) NOT NULL AUTO_INCREMENT,
  `fichierImage` varchar(255) NOT NULL DEFAULT '',
  `texte` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_footer`,`fichierImage`,`texte`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `lpcms_footer`
--

INSERT INTO `lpcms_footer` (`id_footer`, `fichierImage`, `texte`) VALUES
(1, 'footer.png', 'Copyright © 2010 ---\n All Rights Reserved.\n');

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_header`
--

CREATE TABLE IF NOT EXISTS `lpcms_header` (
  `id_header` int(11) NOT NULL AUTO_INCREMENT,
  `fichierImage` varchar(255) NOT NULL,
  `titre` varchar(255) NOT NULL,
  PRIMARY KEY (`id_header`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Contenu de la table `lpcms_header`
--

INSERT INTO `lpcms_header` (`id_header`, `fichierImage`, `titre`) VALUES
(1, 'header.jpg', 'Erevent');

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_mailing_list`
--

CREATE TABLE IF NOT EXISTS `lpcms_mailing_list` (
  `id_mailing_list` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `descr` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_mailing_list`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `lpcms_mailing_list`
--

INSERT INTO `lpcms_mailing_list` (`id_mailing_list`, `name`, `descr`) VALUES
(1, 'mailing list de test', 'Notre première mailing list'),
(2, 'mailing list de test2', 'Notre deuxième mailing list');

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_mailing_list_user`
--

CREATE TABLE IF NOT EXISTS `lpcms_mailing_list_user` (
  `fk_id_mailing_list` int(11) NOT NULL,
  `fk_id_user` int(11) NOT NULL,
  PRIMARY KEY (`fk_id_mailing_list`,`fk_id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `lpcms_mailing_list_user`
--

INSERT INTO `lpcms_mailing_list_user` (`fk_id_mailing_list`, `fk_id_user`) VALUES
(1, 1),
(1, 2),
(2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_module`
--

CREATE TABLE IF NOT EXISTS `lpcms_module` (
  `id_module` int(11) NOT NULL AUTO_INCREMENT,
  `type_module` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `ordre` int(11) NOT NULL,
  `droit` int(1) NOT NULL,
  `contenu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Contenu de la table `lpcms_module`
--

INSERT INTO `lpcms_module` (`id_module`, `type_module`, `position`, `ordre`, `droit`, `contenu`) VALUES
(1, 2, 0, 0, 2, 'Menu Horizontal'),
(2, 2, 0, 1, 2, 'Autre module horizontal'),
(3, 1, 1, 0, 2, 'Documents'),
(4, 1, 1, 1, 2, 'Outils'),
(5, 0, 1, 2, 2, 'Connexion'),
(6, 3, 2, 0, 2, ' '),
(7, 3, 2, 1, 2, ' '),
(8, 3, 2, 2, 2, ' ');

-- --------------------------------------------------------

--
-- Structure de la table `lpcms_user`
--

CREATE TABLE IF NOT EXISTS `lpcms_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `mail` varchar(255) NOT NULL,
  `pwd` varchar(60) NOT NULL,
  `pseudo` varchar(255) NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Contenu de la table `lpcms_user`
--

INSERT INTO `lpcms_user` (`id_user`, `mail`, `pwd`, `pseudo`) VALUES
(1, 'peramo.lionel@gmail.com', '$2a$07$ThisoneIsanAwesomefraeakRGvWSe.S9qVVHGgk8pDDUf3SPtRae', 'pgmail'),
(2, 'lionelperamo@hotmail.fr', '$2a$07$ThisoneIsanAwesomefraef8cbfqVIp.wfT33gaCVaF4Gu9js9mke', 'photmail');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
