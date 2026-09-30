<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

$migration_description = 'tickets typed (analysis, embed_tags) without image for embed_tags, embedding_model per tag';

$tickets_changes = array();

$query = pwg_query('SHOW COLUMNS FROM `'.P_AI_TICKETS_TABLE.'` LIKE "type";');
if (!pwg_db_num_rows($query))
{
  $tickets_changes[] = 'ADD `type` enum(\'analysis\',\'embed_tags\') NOT NULL DEFAULT \'analysis\' AFTER `ticket_id`';
}

$query = pwg_query('SHOW COLUMNS FROM `'.P_AI_TICKETS_TABLE.'` LIKE "image_id";');
$column = pwg_db_fetch_assoc($query);
if ('YES' !== $column['Null'])
{
  $tickets_changes[] = 'MODIFY `image_id` int(11) unsigned NULL DEFAULT NULL';
}

if (!empty($tickets_changes))
{
  pwg_query('ALTER TABLE `'.P_AI_TICKETS_TABLE.'` '.implode(', ', $tickets_changes).';');
}

$query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "embedding_model";');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.TAGS_TABLE.'` ADD `embedding_model` VARCHAR(255) NULL DEFAULT NULL;');
}
