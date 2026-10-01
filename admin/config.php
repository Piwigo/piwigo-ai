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

$template->assign('P_AI_VECTOR_DISTANCE', p_ai_check_vector_distance());
$template->assign('P_AI_TAGS_STATE', p_ai_get_tags_indexation_state());

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->set_filename('p_ai_admin_content', P_AI_REALPATH . '/admin/template/configuration.tpl');