<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_init()
{
  global $conf, $template;

  load_language('plugin.lang', P_AI_PATH);
  $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);
  if (!isset($conf['piwigo_ai']['allow_new_tags']))
  {
    $conf['piwigo_ai']['allow_new_tags'] = true;
    conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);
  }

  // don't re-seed from the check_tickets worker request itself
  $is_check_tickets_request = ($_REQUEST['method'] ?? '') == 'pwg.ai.check_tickets';

  if (!$is_check_tickets_request
    && isset($conf['piwigo_ai']['is_accessible'])
    && !$conf['piwigo_ai']['is_accessible'])
  {
    p_ai_check_tickets();
  }

  $template->assign(array(
    'P_AI_PATH' => P_AI_PATH,
  ));
}

function p_ai_decode_response($res)
{
  $decoded = json_decode($res, true);
  if (is_array($decoded) && ($decoded['status'] ?? null) === 426)
  {
    conf_update_param('piwigo_ai_outdated', true, true);
  }
  return $decoded;
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

function p_ai_get_existing_tags()
{
  $query = '
SELECT name
  FROM '.TAGS_TABLE.'
  ORDER BY name ASC
;';
  return query2array($query, null, 'name');
}

function p_ai_analyze($image, $callback, $options = [])
{
  global $conf;

  $curl = curl_init($conf['piwigo_ai']['url_server_ai'] . '/analyze');
  $headers = p_ai_default_headers();
  $curl_options = array(
    CURLOPT_POST => true,
    CURLOPT_USERAGENT => 'PiwigoAI Plugin',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
  );

  $post_data = array(
    'callback' => $callback,
    'caption' => $options['caption'] ?? true,
    'tagging' => $options['tagging'] ?? true,
    'ocr' => $options['ocr'] ?? true,
    'language' => get_default_language(),
  );

  if ($post_data['tagging'])
  {
    $post_data['allow_new_tags'] = filter_var($conf['piwigo_ai']['allow_new_tags'], FILTER_VALIDATE_BOOLEAN);
    $post_data['existing_tags'] = json_encode(p_ai_get_existing_tags(), JSON_UNESCAPED_UNICODE);
  }

  if (null === $callback)
  {
    $mime_content_type = mime_content_type($image) ? mime_content_type($image) : 'application/octet-stream';
    $post_data['image'] = new CURLFile($image, $mime_content_type, basename($image));
  }
  else
  {
    $post_data['imageUrl'] = $image;
  }

  $curl_options[CURLOPT_POSTFIELDS] = $post_data;
  curl_setopt_array($curl, $curl_options);

  
  $response = curl_exec($curl);

  if (false === $response)
  {
    return ['errors' => curl_error($curl)];
  }

  if (version_compare(PHP_VERSION, '8', '<'))
  {
    // https://php.net/manual/en/function.curl-close.php
    curl_close($curl);
  }

  return p_ai_decode_response($response);
}

function p_ai_get(string $url, int $timeout = 10)
{
  global $conf;

  $headers = p_ai_default_headers();

  $req = curl_init($conf['piwigo_ai']['url_server_ai'] . $url);
  curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($req, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($req, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($req, CURLOPT_USERAGENT, 'PiwigoAI');
  curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($req, CURLOPT_SSL_VERIFYHOST, 2);
  $res = curl_exec($req);
  $error = false === $res ? curl_error($req) : null;

  if (version_compare(PHP_VERSION, '8', '<'))
  {
    curl_close($req);
  }

  if (false === $res)
  {
    return ['errors' => $error];
  }

  return p_ai_decode_response($res);
}

function p_ai_post(string $url, array $data, int $timeout = 10)
{
  global $conf;

  $headers = p_ai_default_headers();
  $headers[] = 'Content-Type: application/json';

  $req = curl_init($conf['piwigo_ai']['url_server_ai'] . $url);
  curl_setopt($req, CURLOPT_POST, true);
  curl_setopt($req, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($req, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($req, CURLOPT_USERAGENT, 'PiwigoAI');
  curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($req, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($req, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($req, CURLOPT_SSL_VERIFYHOST, 2);
  $res = curl_exec($req);
  $error = false === $res ? curl_error($req) : null;

  if (version_compare(PHP_VERSION, '8', '<'))
  {
    curl_close($req);
  }

  if (false === $res)
  {
    return ['errors' => $error];
  }

  return p_ai_decode_response($res);
}

function p_ai_default_headers()
{
  global $conf;
  $headers = [];

  if (!empty($conf['piwigo_ai']['api_key']))
  {
    $headers[] = 'X-API-KEY: '.$conf['piwigo_ai']['api_key'];
  }

  if (defined('P_AI_VERSION'))
  {
    $headers[] = 'X-PLUGIN-VERSION: '.P_AI_VERSION;
  }

  return $headers;
}

function p_ai_submit_image(array $image_info, array $options)
{
  global $conf;

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
      return array('errors' => l10n('Image file not found').' => '.$image_info['path']);
    }
  }

  $response = p_ai_analyze($img, $callback, $options);

  if (!empty($response['errors']))
  {
    return array('errors' => $response['errors']);
  }

  if (!empty($response['status']) && $response['status'] >= 400)
  {
    return array('errors' => $response['message'] ?? l10n('An error occurred with the Piwigo AI server'));
  }

  if (empty($response['ticket_id']))
  {
    return array('errors' => l10n('No ticket ID in Piwigo AI response'));
  }

  single_insert(
    P_AI_TICKETS_TABLE,
    array(
      'ticket_id'    => $response['ticket_id'],
      'image_id'     => $image_info['id'],
      'status'       => $response['ticket_status'],
      'options'      => $response['options'],
      'cost'         => $response['cost'],
      'use_callback' => $callback ? 'true' : 'false',
    )
  );

  return $response;
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
function p_ai_save_ticket($data)
{
  $results = p_ai_save_tickets(array($data));
  $result = $results[$data['ticket_id']] ?? array('errors' => 'Ticket not found');

  return isset($result['errors']) ? $result : 'Ticket updated';
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
    if (!isset($known[$t['ticket_id']]))
    {
      $needed[] = '"'.pwg_db_real_escape_string($t['ticket_id']).'"';
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
  $vec_fn = p_ai_is_mariadb() ? 'VEC_FromText' : 'STRING_TO_VECTOR';

  // which target images still exist? (one query, for the completed tickets)
  $image_ids = array();
  foreach ($tickets as $data)
  {
    if (isset($data['failed'])) continue;
    $row = $known[$data['ticket_id']] ?? null;
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
  $new_tag_names = array();
  $allow_new_tags_by_image = array();

  foreach ($tickets as $data)
  {
    $tid = $data['ticket_id'];
    $row = $known[$tid] ?? null;
    if (!$row)
    {
      $results[$tid] = array('errors' => 'Ticket not found');
      continue;
    }
    $logger->info('[p_ai_save_tickets] Saving '.pwg_db_real_escape_string($tid));

    // failed reported by the server (or detected upstream)
    if (isset($data['failed']))
    {
      $tickets_failed[] = array(
        'ticket_id' => pwg_db_real_escape_string($tid),
        'cost' => $data['cost'] ?? null,
        'failed_message' => pwg_db_real_escape_string($data['failed']),
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
    if (!empty($data['ocr']))
    {
      $ocr = json_encode($data['ocr'], JSON_UNESCAPED_UNICODE);
      $ocr = pwg_db_real_escape_string($ocr);
    }
    $images_update[] = array(
      'id' => $image_id,
      'ocr' => $ocr,
      'ai_description' => !empty($data['description'])
        ? pwg_db_real_escape_string(stripslashes($data['description']))
        : null,
    );

    // embedding (per-row: needs a SQL function, unfit for mass_updates)
    if (!empty($data['embedding']) && $is_compatible)
    {
      $decoded = json_decode($data['embedding'], true);
      if (is_array($decoded))
      {
        $embeddings[$image_id] = pwg_db_real_escape_string($data['embedding']);
      }
    }

    // tags
    if (!empty($data['tags']))
    {
      $options = json_decode($row['options'] ?? '', true);
      $allow_new_tags = !isset($options['allow_new_tags'])
        || filter_var($options['allow_new_tags'], FILTER_VALIDATE_BOOLEAN);
      $names = array();
      foreach (explode(',', $data['tags']) as $tag_candidate)
      {
        $name = strip_tags(stripslashes(trim($tag_candidate)));
        if ($name !== '')
        {
          $names[] = $name;
          $all_tag_names[$name] = true;
          if ($allow_new_tags)
          {
            $new_tag_names[$name] = true;
          }
        }
      }
      if (!empty($names))
      {
        $tags_by_image[$image_id] = $names;
        $allow_new_tags_by_image[$image_id] = $allow_new_tags;
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

  foreach ($embeddings as $image_id => $emb)
  {
    pwg_query('
UPDATE `'.IMAGES_TABLE.'`
  SET `embedding` = '.$vec_fn.'(\''.$emb.'\')
  WHERE id = '.$image_id.'
;');
  }

  // tags: resolve every name once, associate per image, flag them all at once
  if (!empty($tags_by_image))
  {
    $names = array_keys($all_tag_names);
    $quoted_names = array();
    foreach ($names as $name)
    {
      $quoted_names[] = "'".pwg_db_real_escape_string($name)."'";
    }
    $existing_name_to_id = query2array('
SELECT name, id
  FROM '.TAGS_TABLE.'
  WHERE name IN ('.implode(',', $quoted_names).')
;', 'name', 'id');

    $name_to_id = $existing_name_to_id;
    if (!empty($new_tag_names))
    {
      $new_names = array_keys($new_tag_names);
      $escaped_new_names = array_map('pwg_db_real_escape_string', $new_names);
      $name_to_id += array_combine($new_names, get_tag_ids($escaped_new_names));
    }

    $flag_ids = array();
    foreach ($tags_by_image as $image_id => $img_names)
    {
      $img_tag_ids = array();
      foreach ($img_names as $n)
      {
        if (isset($existing_name_to_id[$n]))
        {
          $tag_id = $existing_name_to_id[$n];
          $img_tag_ids[] = $tag_id;
          $flag_ids[$tag_id] = true;
        }
        elseif (!empty($allow_new_tags_by_image[$image_id]) && isset($name_to_id[$n]))
        {
          $tag_id = $name_to_id[$n];
          $img_tag_ids[] = $tag_id;
          $flag_ids[$tag_id] = true;
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
  $is_compatible = conf_get_param('piwigo_ai_db_compatibility', null);

  // if we have already checked the compatibility return the stored data
  if (!is_null($is_compatible) && !$force)
  {
    return $is_compatible;
  }

  $db_version =  pwg_get_db_version();
  $version = p_ai_parse_db_version($db_version);
  $is_mariadb = p_ai_is_mariadb($db_version);

  if ($is_mariadb) {
    $is_compatible = version_compare($version, '11.7.0', '>=');
  }
  else
  {
    $is_compatible =  version_compare($version, '9.0.0', '>=');
  }

  conf_update_param('piwigo_ai_db_compatibility', $is_compatible, true);
  return $is_compatible;
}

function p_ai_is_mariadb($db_version = null)
{
  return stripos($db_version ?? pwg_get_db_version(), 'MariaDB') !== false;
}

function p_ai_parse_db_version($db_version)
{
  // legacy compatibility prefix sometimes seen on some environments
  $parsed_db_version = preg_replace('/^5\.5\.5-/', '', $db_version);
  preg_match('/^(\d+\.\d+\.\d+)/', $parsed_db_version, $matches);
  return $matches[1] ?? '0.0.0';
}

function p_ai_migrate_db()
{
  if (!p_ai_check_db_compatibility(true)) return;
  
  $query = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "embedding";');
  if (pwg_db_num_rows($query))
  {
    pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` MODIFY `embedding` VECTOR(512) NULL DEFAULT NULL;');
  }

  $query = pwg_query('SHOW COLUMNS FROM `'.TAGS_TABLE.'` LIKE "embedding";');
  if (pwg_db_num_rows($query))
  {
    pwg_query('ALTER TABLE `'.TAGS_TABLE.'` MODIFY `embedding` VECTOR(512) NULL DEFAULT NULL;');
  }
}

function p_ai_ping($default_conf)
{
  global $conf;

  // conf fallback because we use this function in
  // maintain.class.php
  if (!is_array($conf['piwigo_ai'] ?? null))
  {
    $conf['piwigo_ai'] = safe_unserialize(conf_get_param('piwigo_ai', $default_conf));
  }

  // check url localhost / 127.0.0.1
  $piwigo_url = get_absolute_root_url();
  if (!p_ai_is_public_url($piwigo_url))
  {
    $conf['piwigo_ai']['is_accessible'] = false;
    conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);
    return true;
  }

  $result = p_ai_post('/ping', ['callback' => $piwigo_url]);
  if (isset($result['errors']))
  {
    return false;
  }

  $conf['piwigo_ai']['is_accessible'] = isset($result['pong']) && $result['pong'];
  conf_update_param('piwigo_ai', $conf['piwigo_ai'], true);
  return true;
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
  if (p_ai_check_tickets_running()) return;

  // debounce the burst of requests a single page view fires (page + its ajax)
  if (p_ai_check_seeded_recently()) return;

  $query = '
SELECT *
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE
    use_callback = \'false\'
  AND
    status = \'pending\'
  LIMIT 1
;';

  $tickets = pwg_db_fetch_assoc(pwg_query($query));
  if (empty($tickets)) return;

  $exec_id = pwg_unique_exec_begins('ai_check_tickets');
  if (!$exec_id) return; // another one won the race

  conf_update_param('ai_check_tickets_last_seed', time());
  p_ai_fire_check_worker($exec_id, 0);
}

function p_ai_check_seeded_recently($window = 10)
{
  $last_seed = conf_get_param('ai_check_tickets_last_seed', 0);
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
