--
-- Table structure for `domain_filtering` database
--

CREATE TABLE IF NOT EXISTS `files` (
  `f_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `f_file_path` VARCHAR(100) NOT NULL,
  `f_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `f_raw_num` INT(11) NOT NULL DEFAULT '0',
  `f_duplic_num` INT(11) NOT NULL DEFAULT '0',
  `f_error_num` INT(11) NOT NULL DEFAULT '0',
  `f_added_num` INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_file_path` (`f_file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `domains` (
  `d_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `d_f_id` int(11) unsigned NOT NULL,
  `d_domain` VARCHAR(255) NOT NULL,
  `d_global_rank` int(11) unsigned NOT NULL DEFAULT '0',
  `d_visit_duration` TIME NOT NULL,
  `d_pages_visit` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0',
  `d_bounce_rate` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`d_id`),
  KEY `d_f_id` (`d_f_id`),
  UNIQUE KEY `d_domain` (`d_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `files` ADD `f_bounce_rate_out` INT(11) NOT NULL DEFAULT '0', 
ADD `f_pages_visit_out` INT(11) NOT NULL DEFAULT '0', 
ADD `f_visit_duration_out` INT(11) NOT NULL DEFAULT '0', 
ADD `f_keywords_out` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `domains` CHANGE `d_global_rank` `d_global_rank` DECIMAL( 16.6 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `domains` ADD `d_estimated_visits` DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0';