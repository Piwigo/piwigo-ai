<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

$migration_description = 'tags mode in the conf, open by default';

$conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

if (!isset($conf['piwigo_ai']['tags_mode']))
{
  $conf['piwigo_ai']['tags_mode'] = 'open';
  conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);
}
