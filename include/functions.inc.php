<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_init()
{
  global $conf, $template;

  load_language('plugin.lang', P_AI_PATH);
  $conf['piwigo_ai'] = safe_unserialize($conf['piwigo_ai']);

  $template->assign(array(
    'P_AI_PATH' => P_AI_PATH,
  ));
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
  $headers = array();
  $headers[] = 'X-API-KEY: ' . $conf['piwigo_ai']['api_key'] ?? 'no-api-key';
  $curl_options = array(
    CURLOPT_POST => true,
    CURLOPT_USERAGENT => 'PiwigoAI Plugin',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_HTTPHEADER => $headers,
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
    $post_data['image'] = new CURLFile($image, mime_content_type($image), basename($image));
  }
  else
  {
    $post_data['imageUrl'] = $image;
  }
  
  $curl_options[CURLOPT_POSTFIELDS] = $post_data;
  curl_setopt_array($curl, $curl_options);

  
  $response = curl_exec($curl);
  
  if (version_compare(PHP_VERSION, '8', '<'))
  {
    // https://php.net/manual/en/function.curl-close.php
    curl_close($curl);
  }
  return json_decode($response, true);
}

function p_ai_get(string $url, int $timeout = 10)
{
  global $conf;
  $headers = array();

  if (!empty($conf['piwigo_ai']['api_key']))
  {
    $headers[] = 'X-API-KEY: '.$conf['piwigo_ai']['api_key'];
  }

  $req = curl_init($conf['piwigo_ai']['url_server_ai'] . $url);
  curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($req, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($req, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($req, CURLOPT_USERAGENT, 'PiwigoAI');
  $res = curl_exec($req);
  
  if (false === $res)
  {
    $error = curl_error($req);
    throw new \Exception("cURL error: {$error}");
  }

  return json_decode($res, true);
}

function p_ai_post(string $url, array $data, int $timeout = 10)
{
  global $conf;

  $headers = array(
    'Content-Type: application/json'
  );

  if (!empty($conf['piwigo_ai']['api_key']))
  {
    $headers[] = 'X-API-KEY: '.$conf['piwigo_ai']['api_key'];
  }

  $req = curl_init($conf['piwigo_ai']['url_server_ai'] . $url);
  curl_setopt($req, CURLOPT_POST, true);
  curl_setopt($req, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($req, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($req, CURLOPT_USERAGENT, 'PiwigoAI');
  curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($req, CURLOPT_TIMEOUT, $timeout);
  $res = curl_exec($req);

  if (false === $res)
  {
    $error = curl_error($req);
    throw new \Exception("cURL error: {$error}");
  }

  return json_decode($res, true);
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