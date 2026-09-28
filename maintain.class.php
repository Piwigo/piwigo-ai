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

    $this->install($new_version, $errors);
  }

  /**
   * Plugin uninstallation
   */
  function uninstall()
  {
    pwg_query('DROP TABLE IF EXISTS `'. P_AI_TICKETS_TABLE .'`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN `ocr`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN `ai_description`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN `embedding`;');
    pwg_query('ALTER TABLE `'. TAGS_TABLE .'` DROP COLUMN `ai`;');
    pwg_query('ALTER TABLE `'. TAGS_TABLE .'` DROP COLUMN `embedding`;');

    conf_delete_param('piwigo_ai');
    conf_delete_param('piwigo_ai_db_compatibility');
    conf_delete_param('piwigo_ai_outdated');
    conf_delete_param('piwigo_ai_migrations');
  }

}
