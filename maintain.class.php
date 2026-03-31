<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class piwigo_ai_maintain extends PluginMaintain
{
  private $table;
  private $default_conf = array(
    'send_picture_file' => false,
    'ticket_callback' => true,
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

    if (empty($conf['piwigo_ai']))
    {
      conf_update_param('piwigo_ai', $this->default_conf, true);  
    }

    $query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "ocr";');
    if (!pwg_db_num_rows($query))
    {
      pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `ocr` LONGTEXT NULL DEFAULT NULL;');
    }

    $query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding";');
    if (!pwg_db_num_rows($query))
    {
      pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `embedding` VECTOR(512) NULL DEFAULT NULL;');
    }

    $query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "ai";');
    if (!pwg_db_num_rows($query))
    {
      pwg_query('ALTER TABLE `'.TAGS_TABLE.'` ADD `ai` enum(\'true\', \'false\') NOT NULL DEFAULT \'false\'');
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
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
;');
  }

  /**
   * Plugin activate
   */
  function activate($plugin_version, &$errors = array())
  {
  }

  /**
   * Plugin deactivate
   */
  function deactivate()
  {
  }

  /**
   * Plugin update
   */
  function update($old_version, $new_version, &$errors = array())
  {
    $this->install($new_version, $errors);
  }

  /**
   * Plugin uninstallation
   */
  function uninstall()
  {
    pwg_query('DROP TABLE IF EXISTS `'. $this->table .'`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP COLUMN IF EXISTS `ocr`;');
    pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP INDEX IF EXISTS `embedding`, DROP COLUMN IF EXISTS `embedding`;');
    pwg_query('ALTER TABLE `'. TAGS_TABLE .'` DROP COLUMN IF EXISTS `ai`;');
    conf_delete_param('piwigo_ai');
  }

}
