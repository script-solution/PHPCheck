-- phpMyAdmin SQL Dump
-- version 4.5.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 24, 2016 at 09:44 PM
-- Server version: 10.1.11-MariaDB-log
-- PHP Version: 7.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `phpcheck`
--

-- --------------------------------------------------------

--
-- Table structure for table `pc_calls`
--

CREATE TABLE `pc_calls` (
  `id` int(11) UNSIGNED NOT NULL,
  `project_id` int(11) UNSIGNED NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) UNSIGNED NOT NULL,
  `function` varchar(255) NOT NULL,
  `class` varchar(255) DEFAULT NULL,
  `static` tinyint(1) UNSIGNED NOT NULL,
  `objcreation` tinyint(1) UNSIGNED NOT NULL,
  `arguments` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_classes`
--

CREATE TABLE `pc_classes` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abstract` tinyint(1) NOT NULL,
  `final` tinyint(1) NOT NULL,
  `interface` tinyint(1) NOT NULL,
  `superclass` varchar(255) DEFAULT NULL,
  `interfaces` text NOT NULL,
  `min_version` text NOT NULL,
  `max_version` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_class_fields`
--

CREATE TABLE `pc_class_fields` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` text NOT NULL,
  `visibility` varchar(20) NOT NULL,
  `static` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_constants`
--

CREATE TABLE `pc_constants` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_errors`
--

CREATE TABLE `pc_errors` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_functions`
--

CREATE TABLE `pc_functions` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `class` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `abstract` tinyint(1) NOT NULL,
  `final` tinyint(1) NOT NULL,
  `static` tinyint(1) NOT NULL,
  `anonymous` tinyint(1) NOT NULL,
  `visibility` varchar(15) NOT NULL,
  `return_type` text NOT NULL,
  `throws` text NOT NULL,
  `params` text NOT NULL,
  `min_version` text NOT NULL,
  `max_version` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_projects`
--

CREATE TABLE `pc_projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `type_folders` text NOT NULL,
  `type_exclude` text NOT NULL,
  `stmt_folders` text NOT NULL,
  `stmt_exclude` text NOT NULL,
  `report_mixed` tinyint(1) NOT NULL,
  `report_unknown` tinyint(1) NOT NULL,
  `current` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pc_vars`
--

CREATE TABLE `pc_vars` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `type` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pc_calls`
--
ALTER TABLE `pc_calls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pc_classes`
--
ALTER TABLE `pc_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `pc_class_fields`
--
ALTER TABLE `pc_class_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pc_constants`
--
ALTER TABLE `pc_constants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class` (`class`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `pc_errors`
--
ALTER TABLE `pc_errors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pc_functions`
--
ALTER TABLE `pc_functions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class` (`class`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `pc_projects`
--
ALTER TABLE `pc_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pc_vars`
--
ALTER TABLE `pc_vars`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pc_calls`
--
ALTER TABLE `pc_calls`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_classes`
--
ALTER TABLE `pc_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_class_fields`
--
ALTER TABLE `pc_class_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_constants`
--
ALTER TABLE `pc_constants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_errors`
--
ALTER TABLE `pc_errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_functions`
--
ALTER TABLE `pc_functions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_projects`
--
ALTER TABLE `pc_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `pc_vars`
--
ALTER TABLE `pc_vars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;