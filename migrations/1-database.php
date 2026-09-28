<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// baseline: the schema and conf up to 0.0.7beta, idempotent so it also fits galleries installed before the migrations
$migration_description = 'baseline: images, tags and tickets tables up to 0.0.7beta';

$is_compatible = p_ai_check_db_compatibility();
$type = $is_compatible ? 'VECTOR(512)' : 'LONGTEXT';

$conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

// 0.0.3beta => 0.0.4beta
// change send_picture_file && ticket_callback to is_accessible
if (isset($conf['piwigo_ai']['send_picture_file'])
  || isset($conf['piwigo_ai']['ticket_callback']))
{
  unset($conf['piwigo_ai']['send_picture_file'],
  $conf['piwigo_ai']['ticket_callback']);
  $conf['piwigo_ai']['is_accessible'] = false;
}

if (!isset($conf['piwigo_ai']['display_ai_description']))
{
  $conf['piwigo_ai']['display_ai_description'] = false;
}

conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);

$query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "ocr";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `ocr` LONGTEXT NULL DEFAULT NULL;');
}

$query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "ai_description";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `ai_description` LONGTEXT NULL DEFAULT NULL;');
}

$query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `embedding` '. $type .' NULL DEFAULT NULL;');
}

$query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "ai";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.TAGS_TABLE.'` ADD `ai` enum(\'true\', \'false\') NOT NULL DEFAULT \'false\'');
}

$query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "embedding";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.TAGS_TABLE.'` ADD `embedding` '. $type .' NULL DEFAULT NULL;');
}

pwg_query('
CREATE TABLE IF NOT EXISTS `'. P_AI_TICKETS_TABLE .'` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` CHAR(36) NULL DEFAULT NULL,
  `image_id` int(11) unsigned NOT NULL,
  `status` enum(\'unsent\',\'pending\',\'failed\',\'completed\') NOT NULL,
  `use_callback` enum(\'true\', \'false\') NOT NULL,
  `cost` FLOAT NULL,
  `options` LONGTEXT NULL,
  `process_time` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `failed_at` TIMESTAMP NULL,
  `failed_message` TEXT NULL DEFAULT NULL,
  `send_attempt_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
;');

// 0.0.3beta => 0.0.4beta
$query = pwg_query('SHOW COLUMNS FROM `'.P_AI_TICKETS_TABLE.'` LIKE "failed_message";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.P_AI_TICKETS_TABLE.'` ADD `failed_message` TEXT NULL DEFAULT NULL;');
}

// 0.0.6beta => 0.0.7beta
$query = pwg_query('SHOW COLUMNS FROM `'.P_AI_TICKETS_TABLE.'` LIKE "send_attempt_at";');
if (!pwg_db_num_rows($query))
{
  pwg_query('
ALTER TABLE `'.P_AI_TICKETS_TABLE.'`
  DROP PRIMARY KEY,
  ADD `id` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  MODIFY `ticket_id` CHAR(36) NULL DEFAULT NULL,
  ADD UNIQUE KEY `ticket_id` (`ticket_id`),
  MODIFY `status` enum(\'unsent\',\'pending\',\'failed\',\'completed\') NOT NULL,
  ADD `send_attempt_at` TIMESTAMP NULL DEFAULT NULL
;');
}
