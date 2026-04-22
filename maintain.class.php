<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class piwigo_ai_maintain extends PluginMaintain
{
  private $table;
  private $default_conf = array(
    'is_accessible' => false,
    'description_prefix' => null,
    'url_server_ai' => 'https://ai.piwigo.net',
    'account_id' => null,
    'api_key' => null,
  );

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);

    global $prefixeTable;

    $this->table = $prefixeTable . 'ai_tickets';
  }

  /**
   * Plugin install
   */
  function install($plugin_version, &$errors = array())
  {
    global $conf;

    include_once(PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/include/functions.inc.php');
    $is_compatible = p_ai_check_db_compatibility();
    conf_update_param('piwigo_ai_db_compatibility', $is_compatible, true);
    $type = $is_compatible ? 'VECTOR(512)' : 'LONGTEXT';

    if (empty($conf['piwigo_ai']))
    {
      // $conf new install
      conf_update_param('piwigo_ai', $this->default_conf, true);
    }
    else
    {
      // $conf migration
      $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

      // 0.0.3beta => 0.0.4beta
      // change send_picture_file && ticket_callback to is_accessible
      if (isset($conf['piwigo_ai']['send_picture_file']) 
        || isset($conf['piwigo_ai']['ticket_callback']))
      {
        unset($conf['piwigo_ai']['send_picture_file'],
        $conf['piwigo_ai']['ticket_callback']);
        $conf['piwigo_ai']['is_accessible'] = false;
        conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);

        p_ai_ping($this->default_conf);
      }
    }

    $query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "ocr";');
    if (!pwg_db_num_rows($query))
    {
      pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `ocr` LONGTEXT NULL DEFAULT NULL;');
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
CREATE TABLE IF NOT EXISTS `'. $this->table .'` (
  `ticket_id` CHAR(36) NOT NULL,
  `image_id` int(11) unsigned NOT NULL,
  `status` enum(\'pending\',\'failed\',\'completed\') NOT NULL,
  `use_callback` enum(\'true\', \'false\') NOT NULL,
  `cost` FLOAT NULL,
  `options` LONGTEXT NULL,
  `process_time` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `failed_at` TIMESTAMP NULL,
  `failed_message` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
;');

    // MIGRATIONS
    // 0.0.3beta => 0.0.4beta
    $query = pwg_query('SHOW COLUMNS FROM `'.$this->table.'` LIKE "failed_message";');
    if (!pwg_db_num_rows($query))
    {
      pwg_query('ALTER TABLE `'.$this->table.'` ADD `failed_message` TEXT NULL DEFAULT NULL;');
    }
  }

  /**
   * Plugin activate
   */
  function activate($plugin_version, &$errors = array())
  {
    include_once(PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)) . '/include/functions.inc.php');
    $ping = p_ai_ping($this->default_conf);
    if (!$ping)
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
    }

    $this->install($new_version, $errors);
  }

  /**
   * Plugin uninstallation
   */
  function uninstall()
  {
    pwg_query('DROP TABLE IF EXISTS `'. $this->table .'`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN `ocr`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN `embedding`;');
    pwg_query('ALTER TABLE `'. TAGS_TABLE .'` DROP COLUMN `ai`;');
    pwg_query('ALTER TABLE `'. TAGS_TABLE .'` DROP COLUMN `embedding`;');

    conf_delete_param('piwigo_ai');
    conf_delete_param('piwigo_ai_db_compatibility');
    conf_delete_param('piwigo_ai_outdated');
  }

}
