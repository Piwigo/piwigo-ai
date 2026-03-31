<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $page, $conf, $template;

$page['tab'] = isset($_GET['tab']) ? $_GET['tab'] : $page['tab'] = 'queue';

// Create tabsheet
include_once(PHPWG_ROOT_PATH . 'admin/include/tabsheet.class.php');
$tabsheet = new tabsheet();
$tabsheet->set_id('piwigoai_tab');
$tabsheet->add('queue', '<span class="icon-tasks"></span>'.l10n('Queue'), P_AI_ADMIN . '-queue');
$tabsheet->add('credits', '<span class="icon-ai-token"></span>'.l10n('Credits'), P_AI_ADMIN . '-credits');
$tabsheet->add('config', '<span class="icon-cog"></span>'.l10n('Configuration'), P_AI_ADMIN . '-config');
$tabsheet->select($page['tab']);
$tabsheet->assign();

$is_valid_account = p_ai_check_account();
if (!$is_valid_account)
{
  return;
}

include_once(P_AI_PATH . 'admin/' . $page['tab'] . '.php');

$template->assign(array(
  'P_AI_PATH'=> P_AI_PATH,
  'P_AI_CONFIG' => $conf['piwigo_ai'],
  'PWG_TOKEN' => get_pwg_token(),
));
$template->assign_var_from_handle('ADMIN_CONTENT', 'p_ai_admin_content');
