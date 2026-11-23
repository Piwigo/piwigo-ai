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

// false when they did not all run: the database cannot compare vectors, or one could not finish
function p_ai_run_migrations()
{
  global $logger;

  $applied = safe_unserialize(conf_get_param('piwigo_ai_migrations', array()));
  $pending = array_diff(p_ai_migration_ids(), array_keys($applied));
  if (empty($pending))
  {
    return true;
  }

  // tested again before a migration, not read from the conf: a gallery on MySQL 9 was noted
  // compatible by the versions that accepted it
  if (!p_ai_check_db_compatibility(true))
  {
    return false;
  }

  foreach ($pending as $id)
  {
    $description = p_ai_run_migration($id);

    // not recorded: it runs again next time, and the next ones wait for it
    if (false === $description)
    {
      return false;
    }

    // saved after each one: if a later migration fails, only that one runs again
    $applied[$id] = date('Y-m-d H:i:s');
    conf_update_param('piwigo_ai_migrations', $applied, true);

    if (isset($logger))
    {
      $logger->info('[piwigo_ai] migration ' . $id . ' applied: ' . $description);
    }
  }

  return true;
}

// the description of the migration, or false when it could not finish (the file returns false)
function p_ai_run_migration($id)
{
  global $conf, $logger;

  $migration_description = '';
  $finished = include(P_AI_PATH . 'migrations/' . $id . '-database.php');

  if (false === $finished)
  {
    if (isset($logger))
    {
      $logger->error('[piwigo_ai] migration ' . $id . ' not finished: ' . $migration_description);
    }

    return false;
  }

  return $migration_description;
}
