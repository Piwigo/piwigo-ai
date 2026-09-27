<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $prefixeTable;

define('P_AI_VERSION', '0.0.7beta');
define('P_AI_ID', basename(dirname(__DIR__)));
define('P_AI_PATH', PHPWG_PLUGINS_PATH . P_AI_ID . '/');
define('P_AI_REALPATH', realpath(P_AI_PATH));
define('P_AI_ADMIN', get_root_url() . 'admin.php?page=plugin-' . P_AI_ID);
define('P_AI_TICKETS_TABLE',   $prefixeTable . 'ai_tickets');
