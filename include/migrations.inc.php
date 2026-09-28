<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

function p_ai_migration_ids()
{
  $ids = array();
  foreach (scandir(P_AI_PATH . 'migrations') as $file)
  {
    if (preg_match('/^(\d+)-database\.php$/', $file, $matches))
    {
      $ids[] = (int)$matches[1];
    }
  }
  sort($ids);

  return $ids;
}

function p_ai_run_migrations()
{
  global $logger;

  $applied = safe_unserialize(conf_get_param('piwigo_ai_migrations', array()));

  foreach (p_ai_migration_ids() as $id)
  {
    if (isset($applied[$id]))
    {
      continue;
    }

    $description = p_ai_run_migration($id);

    // saved after each one: if a later migration fails, only that one runs again
    $applied[$id] = date('Y-m-d H:i:s');
    conf_update_param('piwigo_ai_migrations', $applied, true);

    if (isset($logger))
    {
      $logger->info('[piwigo_ai] migration ' . $id . ' applied: ' . $description);
    }
  }
}

function p_ai_run_migration($id)
{
  global $conf;

  $migration_description = '';
  include(P_AI_PATH . 'migrations/' . $id . '-database.php');

  return $migration_description;
}

function p_ai_migrate_compatibility_db()
{
  if (!p_ai_check_db_compatibility(true)) return;
  
  $query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding";');
  if (pwg_db_num_rows($query))
  {
    pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` MODIFY `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NULL DEFAULT NULL;');
  }

  $query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "embedding";');
  if (pwg_db_num_rows($query))
  {
    pwg_query('ALTER TABLE `'.TAGS_TABLE.'` MODIFY `embedding` VECTOR('.P_AI_EMBEDDING_DIMENSION.') NULL DEFAULT NULL;');
  }
}
