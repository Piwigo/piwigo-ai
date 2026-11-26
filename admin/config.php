<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// |Actions                                                                |
// +-----------------------------------------------------------------------

global $template, $conf;

$health = p_ai_get('/health');

$template->assign(array(
  'P_AI_TAGS_STATE' => p_ai_get_tags_indexation_state(),
  'P_AI_SERVER_ONLINE' => $health['up'] ?? false,
  'P_AI_ANALYSIS_UP' => $health['ai_server_up'] ?? null,
  'P_AI_SERVER_DOMAIN' => preg_replace("(^https?://)", "", $conf['piwigo_ai']['url_server_ai']),
));

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/configuration.tpl');