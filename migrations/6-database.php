<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');


$migration_description = 'index on the photo of a ticket';

$query = pwg_query('SHOW INDEX FROM `'.P_AI_TICKETS_TABLE.'` WHERE Key_name = \'image_id\';');
if (!pwg_db_num_rows($query))
{
  pwg_query('ALTER TABLE `'.P_AI_TICKETS_TABLE.'` ADD KEY `image_id` (`image_id`);');
}
