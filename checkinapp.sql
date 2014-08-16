-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: vergil.u.washington.edu:3555
-- Generation Time: Aug 13, 2014 at 01:19 PM
-- Server version: 5.0.45
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `checkinapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `checkins`
--

CREATE TABLE IF NOT EXISTS `checkins` (
  `id` int(11) NOT NULL auto_increment,
  `customer_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `payment` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;

--
-- Dumping data for table `checkins`
--

INSERT INTO `checkins` (`id`, `customer_id`, `event_id`, `payment`, `timestamp`) VALUES
(1, 24, 1, 3, '2014-02-05 01:19:57'),
(2, 1, 1, 10, '2014-02-05 03:12:48'),
(3, 2, 1, 5, '2014-02-05 03:12:48'),
(4, 3, 1, 0, '2014-02-05 03:12:48'),
(5, 4, 1, 50, '2014-02-05 03:12:48'),
(6, 5, 1, 7, '2014-02-05 03:12:48'),
(7, 6, 1, 7, '2014-02-05 03:12:48'),
(8, 7, 1, 7, '2014-02-05 03:12:48'),
(9, 8, 1, 7, '2014-02-05 03:12:48'),
(10, 9, 1, 7, '2014-02-05 03:12:48'),
(11, 10, 1, 7, '2014-02-05 03:12:48'),
(12, 2, 2, 7, '2014-02-05 03:13:48'),
(13, 2, 3, 7, '2014-02-05 04:00:53'),
(14, 1, 3, 7, '2014-02-05 04:00:53'),
(15, 3, 3, 7, '2014-02-05 04:00:53'),
(16, 4, 3, 7, '2014-02-05 04:00:53'),
(17, 26, 3, 7, '2014-02-05 04:00:53'),
(18, 25, 3, 7, '2014-02-05 04:00:53'),
(19, 24, 3, 7, '2014-02-05 04:00:53'),
(20, 7, 3, 7, '2014-02-05 04:00:53'),
(21, 6, 3, 7, '2014-02-05 04:00:53'),
(22, 5, 3, 7, '2014-02-05 04:00:53'),
(23, 1, 4, 7, '2014-02-05 04:33:10'),
(30, 5, 2, 0, '2014-02-06 03:03:12'),
(29, 9, 2, 7, '2014-02-06 02:41:13'),
(28, 24, 2, 7, '2014-02-06 02:25:39'),
(31, 28, 1, 7, '2014-02-06 04:12:27'),
(32, 29, 1, 7, '2014-02-06 04:26:41'),
(33, 27, 3, 7, '2014-02-06 04:32:33'),
(34, 25, 1, 7, '2014-02-06 05:11:11'),
(35, 30, 6, 8, '2014-02-06 09:55:56'),
(36, 25, 7, 8, '2014-02-06 10:21:30'),
(37, 24, 6, 7, '2014-02-06 23:23:59'),
(38, 31, 1, 7, '2014-02-14 23:20:32');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(127) character set utf8 NOT NULL,
  `email` varchar(127) character set utf8 NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=32 ;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `timestamp`) VALUES
(1, 'Robin', 'robin@batcave.edu', '2014-02-05 03:19:03'),
(2, 'Joker', 'joker@arkham.gov', '2014-02-05 03:19:03'),
(3, 'Harley Quinn', 'drquinn@arkham.gov', '2014-02-05 03:19:03'),
(4, 'Penguin', 'penguin@theritz.com', '2014-02-05 03:19:03'),
(5, 'James Gordon', 'jgordon@gothampd.gov', '2014-02-05 03:19:03'),
(6, 'Harvey Dent', 'hdent@gotham.gov', '2014-02-05 03:19:03'),
(7, 'Two-Face', 'coinman@ustreasury.gov', '2014-02-05 03:19:03'),
(8, 'Riddler', 'admin@google.com', '2014-02-05 03:19:03'),
(9, 'Batgirl', 'Badassmofo@batcave.edu', '2014-02-05 03:19:03'),
(10, 'Nighthawk', 'CAAAAAAAAAAW@batcave.edu', '2014-02-05 03:19:03'),
(24, 'Batman', 'batman@orphans.gov', '2014-02-05 03:19:03'),
(25, 'Jenny', '8675309@mynumber.com', '2014-02-05 03:19:03'),
(26, 'Jack Black', 'jblacko@comedycentral.com', '2014-02-05 03:19:03'),
(27, 'Superman', 'superman@sun.net', '2014-02-05 03:19:03'),
(28, 'James Bond', 'jbond@mi6.uk', '2014-02-06 04:16:46'),
(29, 'Terry McGinnis', 'TMcG@batcave.edu', '2014-02-06 04:26:41'),
(30, 'Jor-El', 'jorel@blownupplanet.com', '2014-02-06 09:55:56'),
(31, 'Athena', 'blahblah@pdx.edu', '2014-02-14 23:20:32');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL auto_increment,
  `organization_id` int(11) NOT NULL,
  `name` varchar(127) character set utf8 NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `organization_id` (`organization_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `organization_id`, `name`, `timestamp`) VALUES
(1, 1, 'First Dance', '2014-02-05 01:41:44'),
(2, 1, 'Mayan Apocalypse - Dec 21, 2012', '2014-02-05 03:53:20'),
(3, 1, 'Third Dance', '2014-02-05 03:53:20'),
(4, 1, 'Who knows when!', '2014-02-06 08:57:51'),
(5, 1, 'Banana Phone 06-20-2014', '2014-02-06 08:59:10'),
(6, 1, 'Another Dance', '2014-02-06 09:01:28'),
(7, 1, 'Fundraiser for Humanity', '2014-02-06 09:01:50'),
(8, 5, 'Freeze Red - 12-32-2014', '2014-02-07 01:05:48'),
(9, 3, 'Goat Sacrifice - 2014-02-12', '2014-02-14 04:44:48');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(127) NOT NULL,
  `email` varchar(127) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `email`, `timestamp`) VALUES
(1, 'SuperSavvyClub', '', '2014-02-06 07:45:17'),
(2, 'BigSmall Biz Co', '', '2014-02-07 00:51:07'),
(3, 'SomeOtherOrganization LLC', '', '2014-02-07 01:02:40'),
(4, 'McLarge Huge Inc', '', '2014-02-07 01:03:36'),
(5, 'Freeze Red', '', '2014-02-07 01:05:14');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
