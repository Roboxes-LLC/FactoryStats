/*
 * Factory Stats MySql upgrade script
 *
 * Covers changes made between 10/7/2022 and 3/30/2023
 *
 * Table: flexscreenalb
 *
 * Update summary:
 * - Add column: display.scaling
 */
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

 -- Factory Stats v1.17, live on 10/16/2022
 -- Add column: display.scaling
DROP TABLE IF EXISTS `display`;
CREATE TABLE IF NOT EXISTS `display` (
  `displayId` int(11) NOT NULL AUTO_INCREMENT,
  `uid` tinytext NOT NULL,
  `name` tinytext,
  `ipAddress` tinytext NOT NULL,
  `version` tinytext NOT NULL,
  `scaling` int(11) NOT NULL DEFAULT '0',
  `presentationId` int(11) NOT NULL DEFAULT '0',
  `lastContact` datetime NOT NULL,
  `resetTime` datetime DEFAULT NULL,
  `upgradeTime` datetime DEFAULT NULL,
  `firmwareImage` varchar(64) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`displayId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

 -- Add column: slide.groupId
DROP TABLE IF EXISTS `slide`;
CREATE TABLE IF NOT EXISTS `slide` (
  `slideId` int(11) NOT NULL AUTO_INCREMENT,
  `presentationId` int(11) NOT NULL,
  `slideType` int(11) NOT NULL,
  `slideIndex` int(11) NOT NULL,
  `duration` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `reloadInterval` int(11) NOT NULL,
  `url` tinytext NOT NULL,
  `image` tinytext NOT NULL,
  `shiftId` int(11) NOT NULL,
  `groupId` int(11) NOT NULL,
  `stationFilter` int(11) NOT NULL,
  `stationId1` int(11) NOT NULL,
  `stationId2` int(11) NOT NULL,
  `stationId3` int(11) NOT NULL,
  `stationId4` int(11) NOT NULL,
  PRIMARY KEY (`slideId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

COMMIT;
 

