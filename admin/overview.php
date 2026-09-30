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
  list($db_version) = pwg_db_fetch_row(pwg_query('SELECT VERSION();'));
  $page['messages'][] = l10n('<div>You are running in degraded mode because your database version (%s) is below the required version (MariaDB 11.7+ or MySQL 9+). Some Piwigo AI features are not available. <a id="p_ai_check_compatibility" href="#">Recheck compatibility</a></div>', $db_version);
}

include_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
$post_max_size = get_ini_size('post_max_size');

$statistiques = p_ai_get_stats();
$credits = p_ai_get('/credits');
$health = p_ai_get('/health');

if (!isset($credits['credits']))
{
  $page['errors'][] = p_ai_error_message($credits);
}

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->assign(array(
  'PWG_TOKEN' => get_pwg_token(),
  'P_AI_STATS' => $statistiques,
  'P_AI_CREDITS' => $credits['credits'] ?? null,
  'P_AI_SERVER_ONLINE' => $health['up'] ?? false,
  'P_AI_ANALYSIS_UP' => $health['ai_server_up'] ?? null,
  'P_AI_SERVER_DOMAIN' => preg_replace("(^https?://)", "", $conf['piwigo_ai']['url_server_ai']),
  'P_AI_COMPATIBLE' => $compatibility,
  'P_AI_TAGS_STATE' => json_encode($compatibility ? p_ai_get_tags_indexation_state() : null),
  'P_AI_POST_MAX_SIZE_LOW' => !empty($conf['piwigo_ai']['is_accessible']) && $post_max_size > 0 && $post_max_size < 8 * 1024 * 1024,
 ));
$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/overview.tpl');