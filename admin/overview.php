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

$compatibility = p_ai_check_db_compatibility();
if (!$compatibility)
{
  $page['messages'][] = l10n('<div>You are running in degraded mode because your database version (%s) is below the required version (MariaDB 11.7+ or MySQL 9+). Some Piwigo AI features are not available. <a id="p_ai_check_compatibility" href="#">Recheck compatibility</a></div>', pwg_get_db_version());
}

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->assign(array(
  'PWG_TOKEN' => get_pwg_token(),
 ));
$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/overview.tpl');