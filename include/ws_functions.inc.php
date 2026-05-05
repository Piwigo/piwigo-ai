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
        'flags' => WS_PARAM_OPTIONAL,
      ),
      'force' => array(
        'default' => false,
        'flags'   => WS_PARAM_OPTIONAL,
        'type' => WS_TYPE_BOOL,
      ),
      'exec_id' => array(
        'default' => null,
        'flags'   => WS_PARAM_OPTIONAL,
      ),
    ),
    'Check Piwigo AI tickets available for callback',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => false,
    )
  );

  $service->addMethod(
    'pwg.ai.tickets.getList',
    'p_ws_ai_tickets_getList',
    array(
      'status' => array(
        'default' => null,
        'flags'   => WS_PARAM_OPTIONAL,
      ),
      'image_id' => array(
        'default' => null,
        'type'    => WS_TYPE_ID,
        'flags'   => WS_PARAM_OPTIONAL,
      ),
      'per_page' => array(
        'default' => 5,
        'maxValue' => 100,
        'type'    => WS_TYPE_INT|WS_TYPE_POSITIVE,
      ),
      'page' => array(
        'default' => 0,
        'type'    => WS_TYPE_INT|WS_TYPE_POSITIVE,
      ),
      'order' => array(
        'default' => 'created_at',
        'flags'   => WS_PARAM_OPTIONAL,
      ),
      'order_direction' => array(
        'default' => 'DESC',
        'flags'   => WS_PARAM_OPTIONAL,
      ),
    ),
    'Returns the list of Piwigo AI tickets with optional filtering and sorting.',
    null,
    array(
      'hidden'    => false,
      'post_only' => false,
      'admin_only' => true,
    )
  );

  $service->addMethod(
    'pwg.ai.retry_failed',
    'p_ws_ai_retry_failed',
    array(
      'pwg_token' => array(),
    ),
    'Re-submit all failed tickets to the AI server.',
    null,
    array(
      'hidden' => false,
      'post_only' => true,
      'admin_only' => true,
    )
  );

  $service->addMethod(
    'pwg.ai.purge_tickets',
    'p_ws_ai_purge_tickets',
    array(
      'pwg_token' => array(),
      'status' => array(),
    ),
    'Delete tickets by status.',
    null,
    array(
      'hidden' => false,
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

  if ($params['force'])
  {
    $exec_running = pwg_unique_exec_is_running('ai_check_tickets');
    if ($exec_running)
    {
      return new PwgError(409, l10n('Automatic ticket verification is already in progress.'));
    }
  }
  else
  {
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
  }

  $result = p_ai_get('/tickets');

  if (isset($result['errors']) || !isset($result['tickets']))
  {
    $logger->error('[PIWIGO_AI][CHECK TICKETS] Unable to retrieves result from AI Server');
    if (!$params['force']) pwg_unique_exec_ends('ai_check_tickets');
    return new PwgError(500, l10n('Error with AI Server'));
  }

  // save the results
  $count = 0;
  foreach($result['tickets'] as $ticket)
  {
    $ticket['ocr'] = !empty($ticket['ocr'])
      ? pwg_db_real_escape_string(is_array($ticket['ocr'])
        ? json_encode($ticket['ocr'], JSON_UNESCAPED_UNICODE)
        : $ticket['ocr'])
      : null;
    $saved = p_ai_save_ticket($ticket);
    if (!isset($saved['errors']))
    {
      $count++;
    }
  }

  if (!$params['force']) pwg_unique_exec_ends('ai_check_tickets');
  return array('processed' => $count);
}

/**
 * `WS Piwigo AI` : Return paginated, filtered and sorted ticket list
 */
function p_ws_ai_tickets_getList($params)
{
  $allowed_order_fields = array('created_at', 'status', 'cost', 'process_time', 'image_id', 'ticket_id');
  $allowed_status = array('pending', 'completed', 'failed');

  $order_field = in_array($params['order'], $allowed_order_fields)
    ? $params['order']
    : 'created_at';

  $order_direction = strtoupper($params['order_direction']) === 'ASC' ? 'ASC' : 'DESC';

  $where_clauses = array();

  if (!empty($params['status']))
  {
    $status = pwg_db_real_escape_string($params['status']);
    if (!in_array($status, $allowed_status))
    {
      return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid status value. Allowed: ' . implode(', ', $allowed_status));
    }
    $where_clauses[] = 't.status = "' . $status . '"';
  }

  if (!empty($params['image_id']))
  {
    $where_clauses[] = 't.image_id = ' . intval($params['image_id']);
  }

  $where_sql = count($where_clauses) > 0
    ? 'WHERE ' . implode(' AND ', $where_clauses)
    : '';

  $per_page = intval($params['per_page']);
  $page = intval($params['page']);
  $offset = $page * $per_page;

  $query = '
SELECT COUNT(*) AS nb
  FROM ' . P_AI_TICKETS_TABLE . ' AS t
  ' . $where_sql . '
;';
  $count_result = pwg_db_fetch_assoc(pwg_query($query));
  $total_count = intval($count_result['nb']);

  $query = '
SELECT t.*, i.file, i.name
  FROM ' . P_AI_TICKETS_TABLE . ' AS t
  LEFT JOIN ' . IMAGES_TABLE . ' AS i ON i.id = t.image_id
  ' . $where_sql . '
  ORDER BY t.' . $order_field . ' ' . $order_direction . '
  LIMIT ' . $per_page . '
  OFFSET ' . $offset . '
;';

  $tickets = query2array($query);

  foreach ($tickets as $i => $ticket)
  {
    $ticket['created_at_format'] = format_date($ticket['created_at'], array('day', 'month', 'year', 'hour'));
    $tickets[$i] = $ticket;
  }

  return array(
    'paging' => array(
      'page' => $page,
      'per_page' => $per_page,
      'total' => $total_count,
      'total_pages' => ceil($total_count / $per_page),
    ),
    'tickets' => $tickets,
  );
}

/**
 * `WS Piwigo AI` : Re-submit all failed tickets to the AI server
 */
function p_ws_ai_retry_failed($params)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, l10n('Invalid security token'));
  }

  $query = '
SELECT ticket_id, image_id, options
  FROM ' . P_AI_TICKETS_TABLE . '
  WHERE status = \'failed\'
;';

  $failed_tickets = query2array($query);

  if (empty($failed_tickets))
  {
    return array('retried' => 0);
  }

  $count = 0;
  foreach ($failed_tickets as $ticket)
  {
    $image = get_image_infos($ticket['image_id']);
    if (empty($image))
    {
      continue;
    }

    $options = json_decode($ticket['options'], true) ?: array();

    pwg_query('
DELETE FROM ' . P_AI_TICKETS_TABLE . '
  WHERE ticket_id = "' . pwg_db_real_escape_string($ticket['ticket_id']) . '"
');

    $response = p_ai_submit_image($image, $options);
    if (empty($response['errors']))
    {
      $count++;
    }
  }

  return array('retried' => $count);
}

/**
 * `WS Piwigo AI` : Delete tickets by status
 */
function p_ws_ai_purge_tickets($params)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, l10n('Invalid security token'));
  }

  $allowed = array('failed');
  $status = pwg_db_real_escape_string($params['status']);

  if (!in_array($status, $allowed))
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid status. Allowed: failed');
  }

  $count_query = pwg_query('SELECT COUNT(*) FROM ' . P_AI_TICKETS_TABLE . ' WHERE status = "' . $status . '"');
  list($count) = pwg_db_fetch_row($count_query);

  pwg_query('DELETE FROM ' . P_AI_TICKETS_TABLE . ' WHERE status = "' . $status . '"');

  return array('deleted' => intval($count));
}