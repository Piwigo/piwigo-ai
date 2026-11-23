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
  'P_AI_TAGS_STATE' => json_encode(p_ai_get_tags_indexation_state()),
  'P_AI_POST_MAX_SIZE_LOW' => !empty($conf['piwigo_ai']['is_accessible']) && $post_max_size > 0 && $post_max_size < 8 * 1024 * 1024,
 ));
$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/overview.tpl');