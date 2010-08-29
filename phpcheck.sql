-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 30, 2010 at 12:05 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `phpcheck`
--

-- --------------------------------------------------------

--
-- Table structure for table `pc_calls`
--

CREATE TABLE IF NOT EXISTS `pc_calls` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) unsigned NOT NULL,
  `function` varchar(255) NOT NULL,
  `class` varchar(255) DEFAULT NULL,
  `static` tinyint(1) unsigned NOT NULL,
  `objcreation` tinyint(1) unsigned NOT NULL,
  `arguments` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9065 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_classes`
--

CREATE TABLE IF NOT EXISTS `pc_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abstract` tinyint(1) NOT NULL,
  `final` tinyint(1) NOT NULL,
  `interface` tinyint(1) NOT NULL,
  `superclass` varchar(255) DEFAULT NULL,
  `interfaces` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=704 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_class_fields`
--

CREATE TABLE IF NOT EXISTS `pc_class_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `value` text,
  `visibility` varchar(20) NOT NULL,
  `static` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1588 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_constants`
--

CREATE TABLE IF NOT EXISTS `pc_constants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=518 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_errors`
--

CREATE TABLE IF NOT EXISTS `pc_errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=508 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_functions`
--

CREATE TABLE IF NOT EXISTS `pc_functions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abstract` tinyint(1) NOT NULL,
  `final` tinyint(1) NOT NULL,
  `static` tinyint(1) NOT NULL,
  `visibility` varchar(15) NOT NULL,
  `return_type` text NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6924 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_projects`
--

CREATE TABLE IF NOT EXISTS `pc_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `type_folders` text NOT NULL,
  `type_exclude` text NOT NULL,
  `stmt_folders` text NOT NULL,
  `stmt_exclude` text NOT NULL,
  `current` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_vars`
--

CREATE TABLE IF NOT EXISTS `pc_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `function` varchar(255) NOT NULL,
  `class` varchar(255) DEFAULT NULL,
  `type` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3601 ;
