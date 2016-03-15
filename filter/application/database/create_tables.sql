--
-- Table structure for `email_filtering` database
--

CREATE TABLE IF NOT EXISTS `files` (
  `f_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `f_file_path` VARCHAR(100) NOT NULL,
  `f_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`f_id`),
  UNIQUE KEY `f_file_path` (`f_file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `emails` (
  `e_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `e_f_id` int(11) unsigned NOT NULL,
  `e_email` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`e_id`),
  KEY `e_f_id` (`e_f_id`),
  UNIQUE KEY `e_email` (`e_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `files` 
  ADD `f_raw_num` INT(11) NOT NULL DEFAULT '0',
  ADD `f_duplic_num` INT(11) NOT NULL DEFAULT '0',
  ADD `f_error_num` INT(11) NOT NULL DEFAULT '0',
  ADD `f_added_num` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `emails` CHANGE `e_email` `e_email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;