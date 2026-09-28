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
