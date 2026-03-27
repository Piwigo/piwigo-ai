<?php
/*
Version: auto
Plugin Name: Piwigo AI
Plugin URI: auto
Author: Piwigo team
Author URI: https://github.com/Piwigo
Description: Transform your Piwigo gallery into an AI-powered smart platform!
Has Settings: webmaster
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

// check root directory
if (basename(dirname(__FILE__)) != 'piwigo_ai')
{
  add_event_handler('init', 'p_ai_error');
  function p_ai_error()
  {
    global $page;
    $page['errors'][] = 'Piwigo AI plugin folder name is incorrect, uninstall the plugin and rename it to "piwigo_ai"';
  }
  return;
}

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+
global $prefixeTable;

define('P_AI_VERSION', '0.0.1Alpha');
define('P_AI_ID', basename(dirname(__FILE__)));
define('P_AI_PATH', PHPWG_PLUGINS_PATH . P_AI_ID . '/');
define('P_AI_REALPATH', realpath(P_AI_PATH));
define('P_AI_ADMIN', get_root_url() . 'admin.php?page=plugin-' . P_AI_ID);
define('P_AI_TICKETS_TABLE',   $prefixeTable . 'ai_tickets');

// +-----------------------------------------------------------------------+
// | Init Example Plugin                                                   |
// +-----------------------------------------------------------------------+

include_once(P_AI_PATH . 'include/functions.inc.php');

add_event_handler('init', 'p_ai_init');

$ws_functions = P_AI_PATH.'include/ws_functions.inc.php';
$events_functions = P_AI_PATH.'include/events.inc.php';

add_event_handler('ws_add_methods', 'p_ai_add_methods', EVENT_HANDLER_PRIORITY_NEUTRAL, $ws_functions);
add_event_handler('loc_end_add_uploaded_file', 'p_ai_loc_end_add_uploaded_file', EVENT_HANDLER_PRIORITY_NEUTRAL, $events_functions);

if (defined('IN_ADMIN'))
{
  $admin_function = P_AI_PATH.'include/admin_functions.inc.php';
  add_event_handler('loc_begin_admin_page', 'p_ai_loc_begin_admin_page_load_tw', EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_function);
  add_event_handler('loc_end_admin', 'p_ai_loc_end_admin', EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_function);
  // add_event_handler('loc_begin_admin_page', 'p_ai_loc_begin_admin_page', EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_function);

  // TODO
  // Get list of tickets where callback is null and status pending
  // Try to retrieves data of tickets
  // If data was retrieves save into db
}