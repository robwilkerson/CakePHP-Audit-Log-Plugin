-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 11, 2012 at 10:10 PM
-- Server version: 5.1.58
-- PHP Version: 5.3.6-13ubuntu3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `42viral_default`
--

-- --------------------------------------------------------

--
-- Table structure for table `audits`
--

CREATE TABLE IF NOT EXISTS `audits` (
  `id` varchar(36) NOT NULL,
  `event` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `json_object` text NOT NULL,
  `description` text,
  `source_id` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `audit_deltas`
--

CREATE TABLE IF NOT EXISTS `audit_deltas` (
  `id` varchar(36) NOT NULL,
  `audit_id` varchar(36) NOT NULL,
  `property_name` varchar(255) NOT NULL,
  `old_value` text,
  `new_value` text,
  PRIMARY KEY (`id`),
  KEY `audit_id` (`audit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;