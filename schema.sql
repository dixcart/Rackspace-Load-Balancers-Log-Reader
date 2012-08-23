-- phpMyAdmin SQL Dump
-- version 3.5.2
-- http://www.phpmyadmin.net
--
-- Host: uk-necco
-- Generation Time: Aug 23, 2012 at 07:08 PM
-- Server version: 5.1.48-community-log
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `lb-logreader`
--

-- --------------------------------------------------------

--
-- Table structure for table `downloadedfiles`
--

CREATE TABLE IF NOT EXISTS `downloadedfiles` (
  `FileID` int(11) NOT NULL AUTO_INCREMENT,
  `FileName` varchar(255) NOT NULL,
  `DateTime` datetime NOT NULL,
  PRIMARY KEY (`FileID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6691 ;

-- --------------------------------------------------------

--
-- Table structure for table `processedfiles`
--

CREATE TABLE IF NOT EXISTS `processedfiles` (
  `FileID` int(11) NOT NULL AUTO_INCREMENT,
  `FileName` varchar(255) NOT NULL,
  `DateTime` datetime NOT NULL,
  PRIMARY KEY (`FileID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rawlogs`
--

CREATE TABLE IF NOT EXISTS `rawlogs` (
  `LogID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FileID` int(11) NOT NULL,
  `balancerid` varchar(50) NOT NULL,
  `host` varchar(255) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `identity` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `timezone` varchar(5) NOT NULL,
  `method` varchar(5) NOT NULL,
  `path` varchar(2000) NOT NULL,
  `protocol` varchar(10) NOT NULL,
  `status` int(11) NOT NULL,
  `bytes` int(11) NOT NULL,
  `referrer` varchar(2000) NOT NULL,
  `agent` varchar(2000) NOT NULL,
  PRIMARY KEY (`LogID`),
  KEY `FileID` (`FileID`),
  KEY `balancerid` (`balancerid`),
  KEY `host` (`host`),
  KEY `ip` (`ip`),
  KEY `path` (`path`(333)),
  KEY `status` (`status`),
  KEY `referrer` (`referrer`(333)),
  KEY `agent` (`agent`(333)),
  KEY `agent_2` (`agent`(333)),
  KEY `agent_3` (`agent`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
