<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// |Actions                                                                |
// +-----------------------------------------------------------------------

global $template;

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$check_tickets_exec_id = bin2hex(random_bytes(20));
$template->assign(array(
  'PWG_TOKEN' => get_pwg_token(),
  'CHECK_TICKETS_EXEC_ID' => $check_tickets_exec_id,
));
$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/tickets.tpl');
