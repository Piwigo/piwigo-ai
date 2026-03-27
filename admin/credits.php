<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// |Actions                                                                |
// +-----------------------------------------------------------------------

global $template, $page;

$result = p_ai_get('/credits');

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->assign(array(
  'P_AI_CREDITS'=> $result['credits'] ?? null,
));

if (!isset($result['credits']))
{
  $page['errors'][] = l10n('ai_missing_api_key');
}

$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/credits.tpl');
