<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// the vectors leave the core tables (images.embedding, tags.embedding) for tables of the plugin,
// where a row exists only when the vector does: copied, checked, then the old columns are dropped.
// Rerunnable: what is already copied is skipped, what is already dropped is not looked for.

pwg_query('
CREATE TABLE IF NOT EXISTS `'.P_AI_IMAGE_VECTORS_TABLE.'` (
  `image_id` mediumint(8) unsigned NOT NULL,
  `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NOT NULL,
  `embedding_model` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
;');

pwg_query('
CREATE TABLE IF NOT EXISTS `'.P_AI_TAG_VECTORS_TABLE.'` (
  `tag_id` smallint(5) unsigned NOT NULL,
  `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NOT NULL,
  `embedding_model` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
;');

$vector_type = 'vector('.P_AI_EMBEDDING_DIMENSION.')';
$copies = array(
  'images' => array('from' => IMAGES_TABLE, 'to' => P_AI_IMAGE_VECTORS_TABLE, 'id' => 'image_id'),
  'tags' => array('from' => TAGS_TABLE, 'to' => P_AI_TAG_VECTORS_TABLE, 'id' => 'tag_id'),
);
$counts = array();
$not_copied = 0;

foreach ($copies as $name => $copy)
{
  // the old columns still there: embedding, embedding_model, both or none
  $old_columns = query2array('SHOW COLUMNS FROM `'.$copy['from'].'` LIKE "embedding%";', 'Field');
  $missing = 0;

  // copied only from a VECTOR column of the current size: a gallery that was in degraded mode
  // has a LONGTEXT one, with nothing to keep
  if (isset($old_columns['embedding']) && strtolower($old_columns['embedding']['Type']) === $vector_type)
  {
    pwg_query('
INSERT IGNORE INTO `'.$copy['to'].'` (`'.$copy['id'].'`, `embedding`, `embedding_model`)
  SELECT `id`, `embedding`, `embedding_model`
    FROM `'.$copy['from'].'`
    WHERE `embedding` IS NOT NULL
;');

    // the vectors of the old column without a row in the new table: there must be none
    list($missing) = pwg_db_fetch_row(pwg_query('
SELECT COUNT(*)
  FROM `'.$copy['from'].'` AS c
    LEFT JOIN `'.$copy['to'].'` AS v ON v.`'.$copy['id'].'` = c.`id`
  WHERE c.`embedding` IS NOT NULL
    AND v.`'.$copy['id'].'` IS NULL
;'));
    $missing = (int)$missing;
  }

  list($in_plugin) = pwg_db_fetch_row(pwg_query('SELECT COUNT(*) FROM `'.$copy['to'].'`;'));
  $counts[] = $name.' '.$in_plugin.($missing > 0 ? ' ('.$missing.' not copied, columns kept)' : '');

  // nothing is dropped while a vector is missing
  if ($missing > 0)
  {
    $not_copied += $missing;
    continue;
  }

  $drops = array();
  foreach (array('embedding', 'embedding_model') as $column)
  {
    if (isset($old_columns[$column]))
    {
      $drops[] = 'DROP COLUMN `'.$column.'`';
    }
  }

  if (!empty($drops))
  {
    pwg_query('ALTER TABLE `'.$copy['from'].'` '.implode(', ', $drops).';');
  }
}

// the confs of the degraded mode, which is gone
conf_delete_param('piwigo_ai_vector_function');
conf_delete_param('piwigo_ai_vector_distance');

// set last: it carries the counts, for the log
$migration_description = 'vectors moved to the plugin tables: '.implode(', ', $counts);

// not finished: the runner does not record it and runs it again next time
if ($not_copied > 0)
{
  return false;
}
