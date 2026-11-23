<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

include_once(dirname(__FILE__) . '/include/constants.inc.php');

class piwigo_ai_maintain extends PluginMaintain
{
  private $default_conf = array(
    'is_accessible' => false,
    'description_prefix' => null,
    'url_server_ai' => 'https://ai.piwigo.net',
    'account_id' => null,
    'api_key' => null,
    'display_ai_description' => false,
    'tags_mode' => 'open',
  );

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);
  }

  /**
   * Plugin install
   */
  function install($plugin_version, &$errors = array())
  {
    global $conf;

    include_once(P_AI_PATH . 'include/functions.inc.php');
    include_once(P_AI_PATH . 'include/migrations.inc.php');

    if (!p_ai_check_db_compatibility())
    {
      $errors = p_ai_db_refused_message();
      return;
    }

    if (empty($conf['piwigo_ai']))
    {
      conf_update_param('piwigo_ai', $this->default_conf, true);
    }

    p_ai_run_migrations();
  }

  /**
   * Plugin activate
   */
  function activate($plugin_version, &$errors = array())
  {
    include_once(PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/include/functions.inc.php');
    include_once(P_AI_PATH . 'include/migrations.inc.php');

    // tested again, not read from the conf: after a database upgrade, the way back is to
    // deactivate and reactivate the plugin, and the migrations that waited run here
    if (!p_ai_check_db_compatibility(true))
    {
      $errors = p_ai_db_refused_message();
      return;
    }
    p_ai_run_migrations();

    if (!p_ai_check_connection($this->default_conf))
    {
      $errors = l10n('Unable to connect to the Piwigo AI server');
    }
  }

  /**
   * Plugin deactivate
   */
  function deactivate()
  {
    conf_delete_param('piwigo_ai_outdated');
  }

  /**
   * Plugin update
   */
  function update($old_version, $new_version, &$errors = array())
  {
    // reset p_ai_outdated only on real version change (avoid auto->auto on every admin page load)
    if ($old_version !== $new_version)
    {
      conf_delete_param('piwigo_ai_outdated');
      conf_delete_param('ai_check_tickets_running'); // clean stucked exec
    }

    // a refused database is told by main.inc.php: the core gives its own page errors here,
    // an array that install() would replace with a string
    $ignored = array();
    $this->install($new_version, $ignored);
  }

  /**
   * Plugin uninstallation
   */
  function uninstall()
  {
    pwg_query('DROP TABLE IF EXISTS `'. P_AI_TICKETS_TABLE .'`;');
    pwg_query('DROP TABLE IF EXISTS `'. P_AI_IMAGE_VECTORS_TABLE .'`;');
    pwg_query('DROP TABLE IF EXISTS `'. P_AI_TAG_VECTORS_TABLE .'`;');

    // embedding and embedding_model are still there only where the migration 5 never ran
    $this->drop_columns(IMAGES_TABLE, array('ocr', 'ai_description', 'embedding', 'embedding_model'));
    $this->drop_columns(TAGS_TABLE, array('ai', 'embedding', 'embedding_model'));

    conf_delete_param('piwigo_ai');
    conf_delete_param('piwigo_ai_db_compatibility');
    conf_delete_param('piwigo_ai_vector_function');
    conf_delete_param('piwigo_ai_vector_distance');
    conf_delete_param('piwigo_ai_outdated');
    conf_delete_param('piwigo_ai_migrations');
  }

  /**
   * Drops the columns of the list that exist, in one ALTER
   */
  private function drop_columns($table, array $columns)
  {
    $existing = query2array('SHOW COLUMNS FROM `'. $table .'`;', null, 'Field');

    $drops = array();
    foreach (array_intersect($columns, $existing) as $column)
    {
      $drops[] = 'DROP COLUMN `'. $column .'`';
    }

    if (!empty($drops))
    {
      pwg_query('ALTER TABLE `'. $table .'` '. implode(', ', $drops) .';');
    }
  }

}
