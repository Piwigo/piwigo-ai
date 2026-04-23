<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_init()
{
  global $conf, $template;

  load_language('plugin.lang', P_AI_PATH);
  $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

  if (isset($conf['piwigo_ai']['is_accessible']) 
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
  $conf['piwigo_ai']['account_id'] = $conf['piwigo_ai']['account_id'] ?? $conf['piwigo_ai_beta_account_id'] ?? null;
  $conf['piwigo_ai']['api_key'] = $conf['piwigo_ai']['api_key'] ?? $conf['piwigo_ai_beta_api_key'] ?? null;

  return !empty($conf['piwigo_ai']['account_id']) || !empty($conf['piwigo_ai']['api_key']);
}

function p_ai_analyze($image, $callback, $send_as_file, $options = [])
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

  if ($send_as_file)
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
    $img = realpath(PHPWG_ROOT_PATH . $image_info['path']);
    if (!$img || !is_file($img))
    {
      return array('errors' => l10n('Image file not found').' => '.$image_info['path']);
    }
  }
  else
  {
    $img = $abs_root . (new SrcImage($image_info))->rel_path;
  }

  $response = p_ai_analyze($img, $callback, $is_accessible, $options);

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

function p_ai_save_ticket($data)
{
  global $conf, $logger;

  $query = '
SELECT *
  FROM '.P_AI_TICKETS_TABLE.'
  WHERE ticket_id = "'.pwg_db_real_escape_string($data['ticket_id']).'"  
  ';

  $logger->info('['.__FUNCTION__.'] Saving ' . pwg_db_real_escape_string($data['ticket_id']));

  $result = pwg_db_fetch_assoc(pwg_query($query));
  if (empty($result))
  {
    return array('errors' => 'Ticket not found');
  }

  if (isset($data['failed']))
  {
    single_update(
      P_AI_TICKETS_TABLE,
      array(
        'cost' => $data['cost'],
        'failed_message' => pwg_db_real_escape_string($data['failed']),
        'status' => 'failed'
      ),
      array('ticket_id' => pwg_db_real_escape_string($data['ticket_id']))
    );
    return 'Ticket updated';
  }

  $is_compatible = p_ai_check_db_compatibility();

  $image = get_image_infos($result['image_id']);

  $ocr = !empty($data['ocr']) ? $data['ocr'] : null;

  $image_update = array('ocr' => $ocr);

  if (!empty($data['description']))
  {
    $prefix = null === $conf[ 'piwigo_ai' ][ 'description_prefix' ]
      ? l10n('(Generated by Artificial Intelligence)')
      : $conf[ 'piwigo_ai' ][ 'description_prefix' ];
    $description = pwg_db_real_escape_string($prefix . ' ' . stripslashes($data['description']));

    if (null !== $image['comment'])
    {
      $description = $image['comment'] . "\n\n" . $description;
    }

    $image_update['comment'] = $description;
  }

  single_update(
    IMAGES_TABLE,
    $image_update,
    array('id' => $result['image_id'])
  );

  // in degraded mode we ignore embedding
  if (!empty($data['embedding']) && $is_compatible)
  {
    $decoded = json_decode($data['embedding'], true);
    if (is_array($decoded))
    {
      $vec_fn = p_ai_is_mariadb() ? 'VEC_FromText' : 'STRING_TO_VECTOR';
      pwg_query('
UPDATE `'.IMAGES_TABLE.'`
  SET `embedding` = '.$vec_fn.'(\''.pwg_db_real_escape_string($data['embedding']).'\')
  WHERE id = '.intval($result['image_id']).'
      ');
    }
  }

  if (!empty($data['tags']))
  {
    $tags = explode(',', $data['tags']);
    foreach ($tags as $idx => $tag_candidate)
    {
      $tags[$idx] = pwg_db_real_escape_string(strip_tags(stripslashes(trim($tag_candidate))));
    }

    $tag_list = get_tag_ids($tags);
    add_tags($tag_list, array($result['image_id']));

    $query = '
UPDATE `'.TAGS_TABLE.'`
  SET `ai` = \'true\'
  WHERE id IN ('.implode(',', $tag_list).')
';
    pwg_query($query);
  }

  single_update(
    P_AI_TICKETS_TABLE,
    array(
      'cost' => $data['cost'],
      'process_time' => $data['process_time'],
      'status' => 'completed'
    ),
    array('ticket_id' => pwg_db_real_escape_string($data['ticket_id']))
  );

  return 'Ticket updated';  
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
  $is_running = pwg_unique_exec_is_running('ai_check_tickets');
  if ($is_running) return; // another one checking actually

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
  if (!$exec_id) return; // another one checking actually

  $url = get_absolute_root_url().'ws.php?format=json&method=pwg.ai.check_tickets';
  $data = [
    'exec_id' => $exec_id,
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
    // trigger exec_end if fire and forget doesn't work
    // and isn't in admin
    pwg_unique_exec_ends('ai_check_tickets');
  }
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
