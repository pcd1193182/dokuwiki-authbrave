delimiter $$

CREATE DATABASE `wiki` /*!40100 DEFAULT CHARACTER SET utf8 */$$

use wiki$$

CREATE TABLE `user` (
  `username` varchar(45) NOT NULL,
  `email` varchar(45) DEFAULT NULL,
  `id` bigint(20) NOT NULL,
  `authcreated` bigint(20) NOT NULL,
  `authlast` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

CREATE TABLE `session` (
  `sessionid` varchar(45) NOT NULL,
  `id` bigint(20) NOT NULL,
  `created` bigint(20) NOT NULL,
  PRIMARY KEY (`sessionid`),
  UNIQUE KEY `sessionid_UNIQUE` (`sessionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

CREATE TABLE `nonce` (
  `nonce` varchar(20) NOT NULL,
  `cb` varchar(45) NOT NULL,
  `time` bigint(20) NOT NULL,
  PRIMARY KEY (`nonce`),
  UNIQUE KEY `nonce_UNIQUE` (`nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

