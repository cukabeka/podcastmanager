/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  georg
 * Created: 20.09.2017
 */

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%podcastmanager` (
    `pid` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id` int(10) unsigned NOT NULL,
    `status` tinyint(1) DEFAULT '0',
    `podcastmanager_category_id` varchar(255) DEFAULT NULL,
    `number` varchar(255) NOT NULL DEFAULT '',
    `title` varchar(255) NOT NULL DEFAULT '',
    `subtitle` varchar(255) NOT NULL DEFAULT '',
    `runtime` varchar(255) NOT NULL DEFAULT '',
    `description` text,
    `richtext` text,
    `images` text,
    `audiofiles` text,
    `publishdate` varchar(255) NOT NULL DEFAULT '',
    `seo_description` varchar(255) NOT NULL DEFAULT '',
    `seo_canonical` varchar(255) DEFAULT NULL,
    `clang_id` int(10) DEFAULT NULL,
    `author` varchar(255) NOT NULL,
    `createuser` varchar(255) NOT NULL,
    `createdate` datetime NOT NULL,
    `updateuser` varchar(255) NOT NULL,
    `updatedate` datetime NOT NULL,
    PRIMARY KEY  (`pid`),
    KEY `status_publishdate` (`status`, `publishdate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%podcastmanager_categories` (
    `pid` int(10) unsigned NOT NULL auto_increment,
    `id` int(10) unsigned NOT NULL,
    `name` varchar(255) default NULL,
    `clang_id` int(10),
    `createuser` varchar(255) NOT NULL,
    `createdate` datetime NOT NULL,
    `updateuser` varchar(255) NOT NULL,
    `updatedate` datetime NOT NULL,
    PRIMARY KEY  (`pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%podcastmanager_stats` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `episode_id` int(10) unsigned NOT NULL,
    `episode_number` varchar(255) NOT NULL DEFAULT '',
    `session_id` varchar(255) NOT NULL DEFAULT '',
    `ip_hash` varchar(64) NOT NULL DEFAULT '',
    `user_agent` varchar(500) DEFAULT NULL,
    `referrer` varchar(500) DEFAULT NULL,
    `download_type` enum('stream','download','rss','embed') DEFAULT 'stream',
    `bytes_sent` bigint(20) unsigned DEFAULT 0,
    `duration_seconds` int(10) unsigned DEFAULT 0,
    `completed` tinyint(1) DEFAULT 0,
    `platform` varchar(50) DEFAULT NULL,
    `app_name` varchar(100) DEFAULT NULL,
    `country` varchar(2) DEFAULT NULL,
    `timestamp` int(10) unsigned NOT NULL,
    `date` date NOT NULL,
    `is_bot` tinyint(1) DEFAULT 0,
    `createdate` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `episode_id` (`episode_id`),
    KEY `date` (`date`),
    KEY `episode_date` (`episode_id`, `date`),
    KEY `timestamp` (`timestamp`),
    KEY `is_bot` (`is_bot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;