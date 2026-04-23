<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

/**
 * `Piwigo AI` : add new pwg method
 */
function p_ai_add_methods($arr)
{
  $service = &$arr[0];

  $service->addMethod(
    'pwg.ai.analyze',
    'p_ws_ai_analyze',
    array(
      'ticket_id' => array(),
      'cost' => array(),
      'ocr' => array('default' => null),
      'description' => array('default' => null),
      'tags' => array('default' => null),
      'embedding' => array('default' => null),
      'process_time' => array('default' => null),
      'failed' => array(
        'flags'=>WS_PARAM_OPTIONAL,
      ),
    ),
    'Save ticket infos',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );

  $service->addMethod(
    'pwg.ai.config',
    'p_ws_ai_config',
    array(
      'is_accessible' => array(
        'type' => WS_TYPE_BOOL
      ),
      'ai_url' => array(),
      'description_prefix' => array(
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'ai_api_key' => array(),
      'pwg_token' => array(),
    ),
    'Change Piwigo AI configuration',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => true,
    )
  );

  $service->addMethod(
    'pwg.ai.check_compatibility',
    'p_ws_ai_check_compatibility',
    array(
      'pwg_token' => array(),
    ),
    'Check Piwigo AI database compatibility',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => true,
    )
  );

  $service->addMethod(
    'pwg.ai.check_tickets',
    'p_ws_ai_check_tickets',
    array(
      'pwg_token' => array(
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'exec_id' => array(),
    ),
    'Check Piwigo AI tickets available for callback',
    null,
    array(
      'hidden' => true,
      'post_only' => true,
      'admin_only' => false,
    )
  );

}

/**
 * `WS Piwigo AI` : Endpoint for server ai to callback the result
 */
function p_ws_ai_analyze($params)
{
  $save_ticket = p_ai_save_ticket($params);

  if (isset($save_ticket['errors']))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, $save_ticket['errors']);
  }

  return $save_ticket;
}

/**
 * `WS Piwigo AI` : Update PiwigoAI configuration
 */
function p_ws_ai_config($params)
{
  global $conf;
  
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, l10n('Invalid security token'));
  }

  if (!connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  $url_server_ai = rtrim($params['ai_url'], '/');
  $prefix_desc = $params['description_prefix'] ? pwg_db_real_escape_string(strip_tags(stripslashes(trim($params['description_prefix'])))) : null;

  $new_conf = array(
    'api_key' => pwg_db_real_escape_string($params['ai_api_key']),
    'url_server_ai' => pwg_db_real_escape_string($url_server_ai),
    'is_accessible' => pwg_db_real_escape_string($params['is_accessible']) == 1 ? true : false,
    'description_prefix' => $prefix_desc,
  );
  conf_update_param('piwigo_ai', array_merge($conf['piwigo_ai'], $new_conf), true);
  return 'Configuration saved';
}

/**
 * `WS Piwigo AI` : Check Piwigo AI database compatibility
 */
function p_ws_ai_check_compatibility($params)
{
  if (!connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, l10n('Invalid security token'));
  }

  $is_compatible = p_ai_check_db_compatibility(true);

  if ($is_compatible)
  {
    p_ai_migrate_db();
    return true;
  }

  return false;
}

/**
 * `WS Piwigo AI` : Check Piwigo AI tickets available for callback
 */
function p_ws_ai_check_tickets($params)
{
  global $logger;

  // to prevent fire and forget
  @ignore_user_abort(true);

  if (connected_with_pwg_ui())
  {
    if (isset($params['pwg_token']))
    {
      if (get_pwg_token() != $params['pwg_token'])
      {
        $logger->error('[PIWIGO_AI][CHECK TICKETS] Invalid pwg_token authentication');
        return new PwgError(401, l10n('Invalid authentication'));
      }

      if (!is_admin())
      {
        $logger->error('[PIWIGO_AI][CHECK TICKETS] Invalid admin authentication');
        return new PwgError(403, l10n('Forbidden'));
      }
    }
    else
    {
      $logger->error('[PIWIGO_AI][CHECK TICKETS] Missing pwg_token');
      return new PwgError(401, l10n('Missing pwg_token'));
    }
  }

  $stored = conf_get_param('ai_check_tickets_running', null);
  if (!$stored)
  {
    return new PwgError(403, l10n('No exec running'));
  }

  list($stored_exec_id, $time_exec) = explode('-', $stored);
  if (!hash_equals($stored_exec_id, $params['exec_id']))
  {
    $logger->error('[PIWIGO_AI][CHECK TICKETS] Invalid exec_id');
    return new PwgError(403, l10n('Invalid exec_id'));
  }

  $result = p_ai_get('/tickets');

  if (isset($result['errors']) || !isset($result['tickets']))
  {
    $logger->error('[PIWIGO_AI][CHECK TICKETS] Unable to retrieves result from AI Server');
    pwg_unique_exec_ends('ai_check_tickets');
    return new PwgError(500, l10n('Error with AI Server'));
  }

  // save the results
  foreach($result['tickets'] as $ticket)
  {
    $ticket['ocr'] = !empty($ticket['ocr'])
      ? pwg_db_real_escape_string(is_array($ticket['ocr'])
        ? json_encode($ticket['ocr'], JSON_UNESCAPED_UNICODE)
        : $ticket['ocr'])
      : null;
    p_ai_save_ticket($ticket);
  }

  pwg_unique_exec_ends('ai_check_tickets');
  return 'checked';
}