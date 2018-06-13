/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  georg
 * Created: 20.09.2017
 */

DROP TABLE IF EXISTS `%TABLE_PREFIX%podcastmanager`;
DROP TABLE IF EXISTS `%TABLE_PREFIX%podcastmanager_categories`;

DELETE FROM `%TABLE_PREFIX%url_generate` WHERE `table` = '1_xxx_rex_podcastmanager';
DELETE FROM `%TABLE_PREFIX%url_generate` WHERE `table` LIKE '1_xxx_rex_podcastmanager_categories';