-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 27, 2014 at 04:27 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `websterday`
--
CREATE DATABASE IF NOT EXISTS `websterday` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `websterday`;

-- --------------------------------------------------------

--
-- Table structure for table `domains`
--

CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `domains_users`
--

CREATE TABLE IF NOT EXISTS `domains_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allowed` tinyint(1) DEFAULT '1',
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `domain_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`domain_id`,`user_id`),
  KEY `fk_domains_has_users_users1_idx` (`user_id`),
  KEY `fk_domains_has_users_domains1_idx` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE IF NOT EXISTS `folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`user_id`),
  KEY `fk_folders_users_idx` (`user_id`),
  KEY `fk_folders_folders1_idx` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `links`
--

CREATE TABLE IF NOT EXISTS `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` longtext NOT NULL,
  `title` longtext,
  `description` longtext,
  `domain_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`domain_id`),
  KEY `fk_links_domains1_idx` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `links_users`
--

CREATE TABLE IF NOT EXISTS `links_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `link_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`link_id`,`user_id`),
  KEY `fk_domains_links_users_users1_idx` (`user_id`),
  KEY `fk_links_users_folders1_idx` (`folder_id`),
  KEY `fk_links_users_links1_idx` (`link_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(32) NOT NULL,
  `key` varchar(32) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `token`, `key`, `created`, `updated`, `status`) VALUES
(1, 'skurty', '6e12d154b2d36bc3e505e1212893a796002aa616', 'aurelien.praga@gmail.com', '5dfb5e7a0a5bd4729f911567d9fb4314', '85da8d65d7f42b1210b4e8ed5a2ca048', '2014-03-21 12:00:00', NULL, 1),
(2, 'pierre', '30e28f24dd9fa210e85be143a1e904bc6a993fa7', 'pierrechanel.gauthier@gmail.com', '3ff481ac5980c3b6d76636cf2c4e6f03', NULL, '2014-03-21 12:00:00', NULL, 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `domains_users`
--
ALTER TABLE `domains_users`
  ADD CONSTRAINT `fk_domains_has_users_domains1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_domains_has_users_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `fk_folders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_folders_folders1` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `links`
--
ALTER TABLE `links`
  ADD CONSTRAINT `fk_links_domains1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `links_users`
--
ALTER TABLE `links_users`
  ADD CONSTRAINT `fk_domains_links_users_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_links_users_folders1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_links_users_links1` FOREIGN KEY (`link_id`) REFERENCES `links` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
