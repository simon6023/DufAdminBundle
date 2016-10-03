-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Client :  localhost:8889
-- Généré le :  Ven 01 Juillet 2016 à 19:05
-- Version du serveur :  5.5.34
-- Version de PHP :  5.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `duf-admin`
--
CREATE DATABASE IF NOT EXISTS `duf-admin` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `duf-admin`;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `duf_admin_users`
--

CREATE TABLE `duf_admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_4E8AD14EE7927C74` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Contenu de la table `duf_admin_users`
--

INSERT INTO `duf_admin_users` (`id`, `username`, `firstname`, `lastname`, `email`, `last_login`, `password`, `salt`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Simon', 'Duflos', 'simon.duflos@gmail.com', NULL, 'ISMvKXpXpadDiUoOSoAfww', '', 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `duf_admin_user_role`
--

CREATE TABLE `duf_admin_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

--
-- Contenu de la table `duf_admin_user_role`
--

INSERT INTO `duf_admin_user_role` (`id`, `name`, `created_at`, `updated_at`) VALUES
(3, 'ROLE_USER', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 'ROLE_ADMIN', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `user_user_role`
--

CREATE TABLE `user_user_role` (
  `user_id` int(11) NOT NULL,
  `user_role_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`user_role_id`),
  KEY `IDX_2D084B47A76ED395` (`user_id`),
  KEY `IDX_2D084B478E0E3CA6` (`user_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Contenu de la table `user_user_role`
--

INSERT INTO `user_user_role` (`user_id`, `user_role_id`) VALUES
(1, 4);

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `user_user_role`
--
ALTER TABLE `user_user_role`
  ADD CONSTRAINT `FK_2D084B478E0E3CA6` FOREIGN KEY (`user_role_id`) REFERENCES `duf_admin_user_role` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_2D084B47A76ED395` FOREIGN KEY (`user_id`) REFERENCES `duf_admin_users` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
