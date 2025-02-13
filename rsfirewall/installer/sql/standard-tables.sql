CREATE TABLE IF NOT EXISTS `#__rsfirewall_hashes` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `file` text COLLATE $__wp_collate NOT NULL,
  `hash` varchar(32) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `type` varchar(64) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `flag` varchar(1) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) $__wp_charset;

CREATE TABLE IF NOT EXISTS `#__rsfirewall_ignored` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `path` text COLLATE $__wp_collate NOT NULL,
  `type` varchar(255) COLLATE $__wp_collate NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) $__wp_charset;

CREATE TABLE IF NOT EXISTS `#__rsfirewall_offenders` (
  `id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(255) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) $__wp_charset;

CREATE TABLE IF NOT EXISTS `#__rsfirewall_signatures` (
  `unique_id` mediumint(11) NOT NULL AUTO_INCREMENT,
  `signature` varchar(255) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `type` varchar(16) COLLATE $__wp_collate NOT NULL DEFAULT '',
  `reason` varchar(255) COLLATE $__wp_collate NOT NULL DEFAULT '',
  PRIMARY KEY (`unique_id`)
) $__wp_charset;