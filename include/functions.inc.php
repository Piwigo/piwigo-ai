<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_init()
{
  global $conf, $template;

  load_language('plugin.lang', P_AI_PATH);
  $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);
  $config_updated = false;
  if (!isset($conf['piwigo_ai']['display_ai_description']))
  {
    $conf['piwigo_ai']['display_ai_description'] = false;
    $config_updated = true;
  }
  if ($config_updated)
  {
    conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);
  }

  // don't re-seed from the check_tickets worker request itself
  $is_check_tickets_request = ($_REQUEST['method'] ?? '') == 'pwg.ai.check_tickets';

  if (!$is_check_tickets_request)
  {
    p_ai_check_tickets();
  }

  $template->assign(array(
    'P_AI_PATH' => P_AI_PATH,
  ));
}

function p_ai_decode_response($res, $status)
{
  if (426 === $status)
  {
    conf_update_param('piwigo_ai_outdated', true, true);
  }

  $decoded = json_decode($res, true);

  if ($status >= 400)
  {
    $first_error = is_array($decoded['errors'] ?? null) ? reset($decoded['errors']) : null;

    return array(
      'errors' => $first_error[0] ?? $decoded['message'] ?? l10n('An error occurred with the Piwigo AI server'),
      'status' => $status,
    );
  }

  if (!is_array($decoded))
  {
    return array(
      'errors' => l10n('Invalid response from the Piwigo AI server'),
      'status' => $status,
    );
  }

  return $decoded;
}

// no status: the server did not answer at all
function p_ai_error_message(array $response)
{
  if (empty($response['status']))
  {
    return l10n('Piwigo AI server unreachable');
  }

  if (401 === $response['status'])
  {
    return l10n('No API key configured or the key is invalid. Please check your settings.');
  }

  return $response['errors'] ?? l10n('An error occurred with the Piwigo AI server');
}

function p_ai_request($method, $path, $data = null, $multipart = false, $timeout = 10)
{
  global $conf;

  $headers = p_ai_default_headers();
  $curl_options = array(
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => 'PiwigoAI Plugin',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
  );

  if (null !== $data)
  {
    if ($multipart)
    {
      $curl_options[CURLOPT_POSTFIELDS] = $data;
    }
    else
    {
      $headers[] = 'Content-Type: application/json';
      $curl_options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
  }

  $curl_options[CURLOPT_HTTPHEADER] = $headers;

  $req = curl_init(rtrim($conf['piwigo_ai']['url_server_ai'], '/') . '/api/v1' . $path);
  curl_setopt_array($req, $curl_options);
  $res = curl_exec($req);
  $error = false === $res ? curl_error($req) : null;
  $status = (int) curl_getinfo($req, CURLINFO_HTTP_CODE);

  if (version_compare(PHP_VERSION, '8', '<'))
  {
    // https://php.net/manual/en/function.curl-close.php
    curl_close($req);
  }

  if (false === $res)
  {
    return array('errors' => $error);
  }

  return p_ai_decode_response($res, $status);
}

function p_ai_check_account()
{
  global $conf;
  $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

  // TODO: remove after closing beta access
  $conf['piwigo_ai']['account_id'] = $conf['piwigo_ai_beta_account_id'] ?? $conf['piwigo_ai']['account_id'] ?? null;
  $conf['piwigo_ai']['api_key'] = $conf['piwigo_ai_beta_api_key'] ?? $conf['piwigo_ai']['api_key'] ?? null;
  $conf['piwigo_ai']['url_server_ai'] = $conf['piwigo_ai_beta_url'] ?? $conf['piwigo_ai']['url_server_ai'] ?? null;

  return !empty($conf['piwigo_ai']['account_id']) || !empty($conf['piwigo_ai']['api_key']);
}

function p_ai_analyze($image, $callback, $options = [])
{
  $post_data = array(
    'caption' => ($options['caption'] ?? true) ? '1' : '0',
    'tagging' => ($options['tagging'] ?? true) ? '1' : '0',
    'ocr' => ($options['ocr'] ?? true) ? '1' : '0',
    'embedding' => (($options['embedding'] ?? true) && p_ai_check_db_compatibility()) ? '1' : '0',
    'language' => get_default_language(),
  );

  if (null === $callback)
  {
    $mime_content_type = mime_content_type($image) ? mime_content_type($image) : 'application/octet-stream';
    $post_data['image'] = new CURLFile($image, $mime_content_type, basename($image));
  }
  else
  {
    $post_data['image_url'] = $image;
    $post_data['callback_url'] = $callback;
  }

  return p_ai_request('POST', '/tickets', $post_data, true, 300);
}

function p_ai_get(string $url, int $timeout = 10)
{
  return p_ai_request('GET', $url, null, false, $timeout);
}

function p_ai_post(string $url, array $data, int $timeout = 10)
{
  return p_ai_request('POST', $url, $data, false, $timeout);
}

function p_ai_default_headers()
{
  global $conf;
  $headers = array('Accept: application/json');

  if (!empty($conf['piwigo_ai']['api_key']))
  {
    $headers[] = 'Authorization: Bearer '.$conf['piwigo_ai']['api_key'];
  }

  $headers[] = 'X-Plugin-Version: '.P_AI_VERSION;

  return $headers;
}

function p_ai_submit_image(array $image_info, array $options)
{
  single_insert(
    P_AI_TICKETS_TABLE,
    array(
      'image_id'        => $image_info['id'],
      'status'          => 'unsent',
      'options'         => pwg_db_real_escape_string(json_encode($options)),
      'use_callback'    => 'false',
    )
  );

  return p_ai_send_ticket(pwg_db_insert_id(), $image_info, $options);
}

// an unsent ticket belongs to the request that claimed it for 10 minutes, so it is never sent twice
function p_ai_claim_unsent_ticket($id)
{
  pwg_query('
UPDATE '.P_AI_TICKETS_TABLE.'
  SET send_attempt_at = NOW()
  WHERE id = '.(int)$id.'
    AND status = \'unsent\'
    AND COALESCE(send_attempt_at, created_at) < NOW() - INTERVAL 10 MINUTE
;');

  return pwg_db_changes() > 0;
}

function p_ai_send_ticket($id, array $image_info, array $options)
{
  global $conf;

  if (p_ai_check_seeded_recently(60, 'ai_backend_down_at'))
  {
    return array('unsent' => l10n('Piwigo AI server unreachable'));
  }

  $abs_root = get_absolute_root_url();

  $is_accessible = $conf['piwigo_ai']['is_accessible'];
  $callback = null;
  if ($is_accessible)
  {
    $callback = $abs_root . 'ws.php?format=json&method=pwg.ai.analyze';
    $img = $abs_root . (new SrcImage($image_info))->rel_path; // https://my-piwigo.com/./upload/2026/05/06/202605xxxxxxxx-xxxxxxx.jpg
  }
  else
  {
    $img = realpath(PHPWG_ROOT_PATH . $image_info['path']); // /var/www/html/piwigo/upload/2026/05/06/202605xxxxxxxx-xxxxxxx.jpg
    if (!$img || !is_file($img))
    {
      return p_ai_fail_unsent_ticket($id, l10n('Image file not found').' => '.$image_info['path']);
    }
  }

  $response = p_ai_analyze($img, $callback, $options);

  if (p_ai_is_temporary_error($response))
  {
    conf_update_param('ai_backend_down_at', time(), true);
    return array('unsent' => $response['errors']);
  }

  if (!empty($response['errors']))
  {
    return p_ai_fail_unsent_ticket($id, $response['errors']);
  }

  $ticket = $response['data'] ?? array();

  if (empty($ticket['id']))
  {
    return p_ai_fail_unsent_ticket($id, l10n('No ticket ID in Piwigo AI response'));
  }

  single_update(
    P_AI_TICKETS_TABLE,
    array(
      'ticket_id'    => pwg_db_real_escape_string($ticket['id']),
      'status'       => pwg_db_real_escape_string($ticket['status']),
      'options'      => pwg_db_real_escape_string(json_encode($ticket['options'])),
      'cost'         => (int)$ticket['cost'],
      'use_callback' => $callback ? 'true' : 'false',
    ),
    array('id' => (int)$id)
  );

  return $response;
}

function p_ai_is_temporary_error(array $response)
{
  if (empty($response['errors']))
  {
    return false;
  }

  $status = $response['status'] ?? 0;
  return 0 === $status || 429 === $status || $status >= 500;
}

function p_ai_fail_unsent_ticket($id, $message)
{
  single_update(
    P_AI_TICKETS_TABLE,
    array(
      'status'         => 'failed',
      'failed_message' => pwg_db_real_escape_string($message),
    ),
    array('id' => (int)$id)
  );

  return array('errors' => $message);
}

function p_ai_send_unsent_tickets($exec_id = null, $limit = 10)
{
  pwg_query('
UPDATE '.P_AI_TICKETS_TABLE.'
  SET status = \'failed\'
    , failed_message = \'Piwigo AI server unreachable\'
  WHERE status = \'unsent\'
    AND created_at < NOW() - INTERVAL 24 HOUR
;');

  $query = '
SELECT id, image_id, options
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE status = \'unsent\'
    AND COALESCE(send_attempt_at, created_at) < NOW() - INTERVAL 10 MINUTE
  ORDER BY id
  LIMIT '.(int)$limit.'
;';
  $tickets = query2array($query);

  $count = 0;
  foreach ($tickets as $ticket)
  {
    if (!p_ai_claim_unsent_ticket($ticket['id']))
    {
      continue;
    }

    $image_info = get_image_infos($ticket['image_id']);
    if (empty($image_info))
    {
      p_ai_fail_unsent_ticket($ticket['id'], 'Image not found');
      $count++;
      continue;
    }

    $response = p_ai_send_ticket($ticket['id'], $image_info, json_decode($ticket['options'], true) ?: array());
    if (isset($response['unsent']))
    {
      break;
    }

    $count++;
    if ($exec_id)
    {
      p_ai_refresh_check_lock($exec_id);
    }
  }

  return $count;
}

function p_ai_get_tickets()
{
  $query = '
SELECT t.*, i.file, i.name
  FROM '.P_AI_TICKETS_TABLE.' AS t
  LEFT JOIN '.IMAGES_TABLE.' AS i ON i.id = t.image_id
  ORDER BY t.created_at DESC
;';
  return query2array($query);
}

function p_ai_get_pending_tickets()
{
  $query = '
SELECT *
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE status = \'pending\'
  LIMIT 500
;';

  return query2array($query, 'ticket_id');
}

// single-ticket wrapper for the callback path (pwg.ai.analyze)
function p_ai_sort_polled_tickets(array $pending_ids, array $polled)
{
  $by_id = array();
  foreach ($polled as $ticket)
  {
    $by_id[$ticket['id']] = $ticket;
  }

  $sorted = array('to_save' => array(), 'failed' => array());
  foreach ($pending_ids as $ticket_id)
  {
    $ticket = $by_id[$ticket_id] ?? null;
    if (null === $ticket)
    {
      $sorted['failed'][$ticket_id] = 'not found on AI server';
    }
    else if (!empty($ticket['expired_at']))
    {
      $sorted['failed'][$ticket_id] = 'result expired, never acknowledged';
    }
    else if (!empty($ticket['acked_at']))
    {
      $sorted['failed'][$ticket_id] = 'result already acknowledged';
    }
    else if (in_array($ticket['status'], array('completed', 'failed'), true))
    {
      $sorted['to_save'][] = $ticket;
    }
  }

  return $sorted;
}

function p_ai_save_ticket($data)
{
  $results = p_ai_save_tickets(array($data));
  $result = $results[$data['id']] ?? array('errors' => 'Ticket not found');

  return isset($result['errors']) ? $result : 'Ticket updated';
}

function p_ai_valid_embedding($embedding)
{
  if (!is_array($embedding) || count($embedding) !== P_AI_EMBEDDING_DIMENSION)
  {
    return null;
  }

  $vector = array();
  foreach ($embedding as $value)
  {
    if (!is_numeric($value))
    {
      return null;
    }
    $vector[] = (float)$value;
  }

  return $vector;
}

// batch-save tickets in a few bulk queries. $known reuses already-fetched rows.
function p_ai_save_tickets(array $tickets, array $known = array())
{
  global $logger;

  if (empty($tickets))
  {
    return array();
  }

  // resolve the rows we don't already know, in a single query
  $needed = array();
  foreach ($tickets as $t)
  {
    if (!isset($known[$t['id']]))
    {
      $needed[] = '"'.pwg_db_real_escape_string($t['id']).'"';
    }
  }
  if (!empty($needed))
  {
    $known += query2array('
SELECT *
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE ticket_id IN ('.implode(',', array_unique($needed)).')
;', 'ticket_id');
  }

  $is_compatible = p_ai_check_db_compatibility();
  $vec_fn = conf_get_param('piwigo_ai_vector_function');

  // which target images still exist? (one query, for the completed tickets)
  $image_ids = array();
  foreach ($tickets as $data)
  {
    if ('failed' === $data['status']) continue;
    $row = $known[$data['id']] ?? null;
    if ($row) $image_ids[(int)$row['image_id']] = true;
  }
  $existing_images = array();
  if (!empty($image_ids))
  {
    $existing_images = query2array('
SELECT id
  FROM '.IMAGES_TABLE.'
  WHERE id IN ('.implode(',', array_keys($image_ids)).')
;', 'id');
  }

  $results = array();
  $images_update = array();
  $tickets_completed = array();
  $tickets_failed = array();
  $embeddings = array();
  $tags_by_image = array();
  $all_tag_names = array();

  foreach ($tickets as $data)
  {
    $tid = $data['id'];
    $row = $known[$tid] ?? null;
    if (!$row)
    {
      $results[$tid] = array('errors' => 'Ticket not found');
      continue;
    }

    if ('pending' !== $row['status'])
    {
      $results[$tid] = true;
      continue;
    }

    $options = json_decode($row['options'], true) ?: array();
    $result = $data['result'] ?? array();
    $is_asked = !empty($options['caption']) || !empty($options['tagging']);
    $is_caption_missing = empty($options['caption']) || empty($result['caption']);
    $is_tags_missing = empty($options['tagging']) || empty($result['tags']);

    if ('completed' === $data['status'] && $is_asked && $is_caption_missing && $is_tags_missing)
    {
      $data['status'] = 'failed';
      $data['error'] = 'detected failed by piwigo';
    }
    $logger->info('[p_ai_save_tickets] Saving '.pwg_db_real_escape_string($tid));

    // failed reported by the server (or detected upstream)
    if ('failed' === $data['status'])
    {
      $tickets_failed[] = array(
        'ticket_id' => pwg_db_real_escape_string($tid),
        'cost' => $data['cost'] ?? null,
        'failed_message' => pwg_db_real_escape_string($data['error'] ?? 'failed'),
        'status' => 'failed',
      );
      $results[$tid] = true;
      continue;
    }

    $image_id = (int)$row['image_id'];
    if (!isset($existing_images[$image_id]))
    {
      $results[$tid] = array('errors' => 'Image not found');
      continue;
    }

    // image columns (mass_updates expects pre-escaped values)
    $ocr = null;
    if (!empty($result['ocr']))
    {
      $ocr = pwg_db_real_escape_string(json_encode($result['ocr'], JSON_UNESCAPED_UNICODE));
    }
    $images_update[] = array(
      'id' => $image_id,
      'ocr' => $ocr,
      'ai_description' => !empty($result['caption'])
        ? pwg_db_real_escape_string($result['caption'])
        : null,
    );

    // embedding (per-row: needs a SQL function, unfit for mass_updates)
    if ($is_compatible && !empty($result['embedding']))
    {
      $vector = p_ai_valid_embedding($result['embedding']);
      if (null === $vector)
      {
        $logger->warn('[p_ai_save_tickets] Invalid embedding ignored for image '.$image_id
          .' ('.(is_array($result['embedding']) ? count($result['embedding']) : 0).' values, expected '.P_AI_EMBEDDING_DIMENSION.')');
      }
      else
      {
        $embeddings[$image_id] = array(
          'vector' => pwg_db_real_escape_string(json_encode($vector)),
          'model' => !empty($result['embedding_model']) && is_string($result['embedding_model'])
            ? '\''.pwg_db_real_escape_string(substr($result['embedding_model'], 0, 255)).'\''
            : 'NULL',
        );
      }
    }

    // tags (names pre-escaped: tag_id_from_tag_name expects escaped input)
    if (!empty($result['tags']))
    {
      $names = array();
      foreach ($result['tags'] as $tag_candidate)
      {
        $name = pwg_db_real_escape_string(strip_tags(trim($tag_candidate)));
        if ($name !== '')
        {
          $names[] = $name;
          $all_tag_names[$name] = true;
        }
      }
      if (!empty($names))
      {
        $tags_by_image[$image_id] = $names;
      }
    }

    $tickets_completed[] = array(
      'ticket_id' => pwg_db_real_escape_string($tid),
      'cost' => $data['cost'] ?? null,
      'process_time' => pwg_db_real_escape_string($data['process_time'] ?? ''),
      'status' => 'completed',
    );
    $results[$tid] = true;
  }

  // --- bulk writes ---

  if (!empty($images_update))
  {
    mass_updates(
      IMAGES_TABLE,
      array('primary' => array('id'), 'update' => array('ocr', 'ai_description')),
      $images_update
    );
  }

  foreach ($embeddings as $image_id => $embedding)
  {
    pwg_query('
UPDATE `'.IMAGES_TABLE.'`
  SET `embedding` = '.$vec_fn.'(\''.$embedding['vector'].'\'),
    `embedding_model` = '.$embedding['model'].'
  WHERE id = '.$image_id.'
;');
  }

  // tags: resolve every name once, associate per image, flag them all at once
  if (!empty($tags_by_image))
  {
    $names = array_keys($all_tag_names);
    $name_to_id = array_combine($names, get_tag_ids($names));

    $flag_ids = array();
    foreach ($tags_by_image as $image_id => $img_names)
    {
      $img_tag_ids = array();
      foreach ($img_names as $n)
      {
        if (isset($name_to_id[$n]))
        {
          $img_tag_ids[] = $name_to_id[$n];
          $flag_ids[$name_to_id[$n]] = true;
        }
      }
      if (!empty($img_tag_ids))
      {
        add_tags($img_tag_ids, array($image_id));
      }
    }
    if (!empty($flag_ids))
    {
      pwg_query('
UPDATE `'.TAGS_TABLE.'`
  SET `ai` = \'true\'
  WHERE id IN ('.implode(',', array_keys($flag_ids)).')
;');
    }
  }

  if (!empty($tickets_completed))
  {
    mass_updates(
      P_AI_TICKETS_TABLE,
      array('primary' => array('ticket_id'), 'update' => array('cost', 'process_time', 'status')),
      $tickets_completed
    );
  }
  if (!empty($tickets_failed))
  {
    mass_updates(
      P_AI_TICKETS_TABLE,
      array('primary' => array('ticket_id'), 'update' => array('cost', 'failed_message', 'status')),
      $tickets_failed
    );
  }

  return $results;
}

function p_ai_check_db_compatibility($force = false)
{
  global $mysqli;

  $is_compatible = conf_get_param('piwigo_ai_db_compatibility', null);

  // a compatible gallery checked before the vector function was stored is checked again once
  if (!is_null($is_compatible) && !$force
    && (!$is_compatible || !empty(conf_get_param('piwigo_ai_vector_function'))))
  {
    return $is_compatible;
  }

  // tested on $mysqli directly: on the expected failure, pwg_query would stop the script (PHP < 8.1)
  $vector_function = null;
  foreach (array('VEC_FromText', 'STRING_TO_VECTOR') as $function)
  {
    try
    {
      $result = $mysqli->query('SELECT '.$function.'(\'[1]\');');
    }
    catch (Throwable $e)
    {
      $result = false;
    }

    if (false !== $result)
    {
      $vector_function = $function;
      break;
    }
  }

  $is_compatible = null !== $vector_function;
  conf_update_param('piwigo_ai_db_compatibility', $is_compatible, true);
  if ($is_compatible)
  {
    conf_update_param('piwigo_ai_vector_function', $vector_function, true);
  }
  else
  {
    conf_delete_param('piwigo_ai_vector_function');
  }

  return $is_compatible;
}

function p_ai_check_connection($default_conf)
{
  global $conf;

  // conf fallback because we use this function in
  // maintain.class.php
  if (!isset($conf['piwigo_ai']))
  {
    $conf['piwigo_ai'] = $default_conf;
  }
  p_ai_check_account();

  $result = p_ai_get('/credits');
  return !isset($result['errors']) || isset($result['status']);
}

function p_ai_ping()
{
  // check url localhost / 127.0.0.1
  $piwigo_url = get_absolute_root_url();
  if (!p_ai_is_public_url($piwigo_url))
  {
    return false;
  }

  $result = p_ai_post('/ping', array('url' => $piwigo_url));
  return !empty($result['pong']);
}

function p_ai_is_public_url($url)
{
  $host = parse_url($url, PHP_URL_HOST);

  // no host = no public
  if (!$host) return false;

  // simple test: localhost, ipv4 localhost, ipv6 localhost = no public
  if (in_array($host, ['localhost', '127.0.0.1', '::1'])) return false;

  // ip: check if this ip is public
  if (filter_var($host, FILTER_VALIDATE_IP))
  {
    return p_ai_is_public_ip($host);
  }

  // domain name = we assume public
  return true;
}

function p_ai_is_public_ip($ip)
{
  return false !== filter_var(
      $ip,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
  );
}

function p_ai_check_tickets()
{
  global $conf;

  if (p_ai_check_tickets_running()) return;

  // debounce the burst of requests a single page view fires (page + its ajax)
  if (p_ai_check_seeded_recently()) return;

  $has_tickets = false;
  if (empty($conf['piwigo_ai']['is_accessible']))
  {
    $has_tickets = p_ai_has_pending_ticket('use_callback = \'false\'');
  }

  if (!$has_tickets && !p_ai_check_seeded_recently(300, 'ai_check_tickets_last_unsent'))
  {
    conf_update_param('ai_check_tickets_last_unsent', time());
    $has_tickets = p_ai_has_pending_ticket('COALESCE(send_attempt_at, created_at) < NOW() - INTERVAL 10 MINUTE', 'unsent');
  }

  if (!$has_tickets && !p_ai_check_seeded_recently(600, 'ai_check_tickets_last_fallback'))
  {
    conf_update_param('ai_check_tickets_last_fallback', time());
    $has_tickets = p_ai_has_pending_ticket('created_at < NOW() - INTERVAL 30 MINUTE');
  }

  if (!$has_tickets) return;

  $exec_id = pwg_unique_exec_begins('ai_check_tickets');
  if (!$exec_id) return; // another one won the race

  conf_update_param('ai_check_tickets_last_seed', time());
  p_ai_fire_check_worker($exec_id, 0);
}

function p_ai_has_pending_ticket($condition, $status = 'pending')
{
  $query = '
SELECT id
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE
    status = \''.$status.'\'
  AND
    '.$condition.'
  LIMIT 1
;';

  return pwg_db_num_rows(pwg_query($query)) > 0;
}

function p_ai_check_seeded_recently($window = 10, $param = 'ai_check_tickets_last_seed')
{
  $last_seed = conf_get_param($param, 0);
  return (time() - (int)$last_seed) < $window;
}

// like pwg_unique_exec_is_running() but a timed-out lock counts as not running
function p_ai_check_tickets_running($timeout = 60)
{
  $stored = conf_get_param('ai_check_tickets_running', null);
  if (!$stored) return false;

  list(, $started_at) = explode('-', $stored);
  return (time() - (int)$started_at) < $timeout;
}

function p_ai_refresh_check_lock($exec_id)
{
  conf_update_param('ai_check_tickets_running', $exec_id.'-'.time());
}

function p_ai_fire_check_worker($exec_id, $iteration)
{
  $url = get_absolute_root_url().'ws.php?format=json&method=pwg.ai.check_tickets';
  $data = [
    'exec_id' => $exec_id,
    'iteration' => $iteration,
  ];
  $is_send = p_ai_fire_and_forget($url, $data);

  if (!$is_send && defined('IN_ADMIN'))
  {
    // fallback if p_ai_fire_and_forget failed
    global $template;
    $template->block_footer_script(null,
      'const p_ai_ct_token = "'.get_pwg_token().'";
       const p_ai_exec = "'.$exec_id.'";'
    );
    $template->func_combine_script(array(
	    "id" => "p_ai_check_tickets",
	    "load" => "footer",
	    "path" => P_AI_PATH.'/admin/js/check_tickets.js'
	  ));
  }
  else if (!$is_send)
  {
    // can't continue the chain: release the lock
    pwg_unique_exec_ends('ai_check_tickets');
  }

  return $is_send;
}

function p_ai_fire_and_forget($url, $data)
{
  $parsed_url = parse_url($url);
  if (!$parsed_url || empty($parsed_url['host']))
  {
    return false;
  }

  $is_https = $parsed_url['scheme'] === 'https';
  $fallback_port = $is_https ? 443 : 80;
  $host_prefix = $is_https ? 'ssl://' : '';

  $path = $parsed_url['path'] ?? '/';
  if (!empty($parsed_url['query']))
  {
    $path .= '?' . $parsed_url['query'];
  }

  $socket = @fsockopen(
    $host_prefix . $parsed_url['host'],
    $parsed_url['port'] ?? $fallback_port,
    $error_code,
    $error_message,
    0.5 // 
  );

  if (!$socket) return false;

  // body like "key=value&pwg_token=123abc"
  $body = http_build_query($data);

  $req = 'POST ' . $path . ' HTTP/1.1' . "\r\n";
  $req .= 'Host: ' . $parsed_url['host'] . "\r\n";
  $req .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
  $req .= "Content-Length: " . strlen($body) . "\r\n";
  $req .= 'Connection: Close' . "\r\n\r\n";
  $req .= $body;

  fwrite($socket, $req);
  fclose($socket);

  return true;
}
