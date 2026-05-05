<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// |Actions                                                                |
// +-----------------------------------------------------------------------

global $template, $page, $conf;

$compatibility = p_ai_check_db_compatibility();
if (!$compatibility)
{
  $page['messages'][] = l10n('<div>You are running in degraded mode because your database version (%s) is below the required version (MariaDB 11.7+ or MySQL 9+). Some Piwigo AI features are not available. <a id="p_ai_check_compatibility" href="#">Recheck compatibility</a></div>', pwg_get_db_version());
}

$statistiques = p_ai_get_stats();
$credits = p_ai_get('/credits');
$is_ai_server_up = p_ai_get('/health');

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->assign(array(
  'PWG_TOKEN' => get_pwg_token(),
  'P_AI_STATS' => $statistiques,
  'P_AI_CREDITS' => $credits['credits'] ?? 0,
  'P_AI_SERVER_ONLINE' => $is_ai_server_up['up'] ?? false,
  'P_AI_SERVER_DOMAIN' => preg_replace("(^https?://)", "", $conf['piwigo_ai']['url_server_ai']),
 ));
$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/overview.tpl');