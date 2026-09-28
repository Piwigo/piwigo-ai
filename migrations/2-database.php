<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

$migration_description = 'embeddings in VECTOR('.P_AI_EMBEDDING_DIMENSION.'), embedding_model per image';

// each step checks the current schema first: a rerun after a partial failure never drops the new vectors
$vector_type = 'vector('.P_AI_EMBEDDING_DIMENSION.')';
$is_compatible = p_ai_check_db_compatibility();

$images_changes = array();

$query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding";');
$column = pwg_db_fetch_assoc($query);
if ($is_compatible && strtolower($column['Type']) !== $vector_type)
{
  // the old CLIP vectors (512) cannot be converted nor compared to the new model: dropped before resizing
  pwg_query('UPDATE `'.IMAGES_TABLE.'` SET `embedding` = NULL WHERE `embedding` IS NOT NULL;');
  $images_changes[] = 'MODIFY `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NULL DEFAULT NULL';
}

$query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding_model";');
if (!pwg_db_num_rows($query))
{
  $images_changes[] = 'ADD `embedding_model` VARCHAR(255) NULL DEFAULT NULL';
}

if (!empty($images_changes))
{
  pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` '.implode(', ', $images_changes).';');
}

$query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "embedding";');
$column = pwg_db_fetch_assoc($query);
if ($is_compatible && strtolower($column['Type']) !== $vector_type)
{
  pwg_query('UPDATE `'.TAGS_TABLE.'` SET `embedding` = NULL WHERE `embedding` IS NOT NULL;');
  pwg_query('ALTER TABLE `'.TAGS_TABLE.'` MODIFY `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NULL DEFAULT NULL;');
}
