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
      'ticket_callback' => array(
        'type' => WS_TYPE_BOOL
      ),
      'send_picture_file' => array(
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
      'pwg_token' => array(),
    ),
    'Check Piwigo AI tickets available for callback',
    null,
    array(
      'hidden' => true,
      'post_only' => true,
      'admin_only' => true,
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
    'ticket_callback' => pwg_db_real_escape_string($params['ticket_callback']) == 1 ? true : false,
    'send_picture_file' => pwg_db_real_escape_string($params['send_picture_file']) == 1 ? true : false,
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
  if (!connected_with_pwg_ui())
  {
    return new PwgError(401, 'Access Denied');
  }

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, l10n('Invalid security token'));
  }

  $query = '
SELECT * 
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE 
    use_callback = \'false\'
  AND
    status = \'pending\'
  ;';

  $tickets = query2array($query);
  $nb_of_tickets = count($tickets);
  if ($nb_of_tickets > 0)
  {
    $max_count = min($nb_of_tickets, 50);
    for($i = 0; $i < $max_count; $i++)
    {
      $result = p_ai_get('/analyze/'.$tickets[$i]['ticket_id']);

      if (isset($result['ticket_status']))
      {
        continue;
      }

      // Piwigo is going to drive me crazy
      // I don't understand why I have to go through all this here for ocr
      $result['ocr'] = !empty($result['ocr']) 
        ? pwg_db_real_escape_string(is_array($result['ocr']) 
          ? json_encode($result['ocr'], JSON_UNESCAPED_UNICODE) 
          : $result['ocr']) 
        : null;
      p_ai_save_ticket($result);
    }
  }

  return 'checked';
}