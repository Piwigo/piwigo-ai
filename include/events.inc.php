<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * `Piwigo AI` : loc_end_add_uploaded_file
 */
function p_ai_loc_end_add_uploaded_file(array $image_info)
{
  global $conf, $logger;

  if (empty($conf[ 'piwigo_ai' ][ 'api_key' ])) return;

  $ai = filter_var($_POST['ai'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
  if (!$ai) return;

  $options = [
    'caption' => filter_var($_POST['caption'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
    'tagging' => filter_var($_POST['tagging'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
    'ocr' => filter_var($_POST['ocr'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
    'embedding' => (filter_var($_POST['embedding'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false)
      && p_ai_check_db_compatibility(),
  ];

  if (!$options['caption'] && !$options['tagging'] && !$options['ocr'] && !$options['embedding']) return;

  $response = p_ai_submit_image($image_info, $options);
  if (isset($response['unsent']))
  {
    $logger->info('[PIWIGO AI]['.__FUNCTION__.'] Sent later : ' . $response['unsent']);
    header('X-Piwigo-AI-Unsent: '.rawurlencode($response['unsent']));
  }
  else if (isset($response['errors']))
  {
    $logger->error('[PIWIGO AI]['.__FUNCTION__.'] Error : ' . $response['errors']);
    header('X-Piwigo-AI-Error: '.rawurlencode($response['errors']));
  }
}

/**
 * `Piwigo AI` : loc_end_picture
 */
function p_ai_loc_end_picture()
{
  global $conf, $picture, $template;

  if (empty($conf['piwigo_ai']['display_ai_description'])
    || empty($picture['current']['ai_description']))
  {
    return;
  }

  $prefix = trim((string) ($conf['piwigo_ai']['description_prefix'] ?? ''));
  $ai_description = trim($picture['current']['ai_description']);
  if ($prefix !== '')
  {
    $ai_description = $prefix.' '.$ai_description;
  }

  $ai_description = pwg_nl2br(htmlspecialchars($ai_description, ENT_QUOTES, 'UTF-8'));
  $description = $template->get_template_vars('COMMENT_IMG');
  if (!empty($description))
  {
    $ai_description = $description.'<br><br>'.$ai_description;
  }

  $template->assign('COMMENT_IMG', $ai_description);
}

/**
 * `Piwigo AI` : ws_invoke_allowed, before a ws method runs
 * no event follows a renaming: the tag is remembered here and compared in sendResponse
 */
function p_ai_ws_invoke_allowed_tags($res, $method_name, $params)
{
  global $p_ai_renamed_tag;

  if ('pwg.tags.rename' !== $method_name || $res instanceof PwgError)
  {
    return $res;
  }

  $query = '
SELECT id, name
  FROM '.TAGS_TABLE.'
  WHERE id = '.(int)$params['tag_id'].'
;';
  $tags = query2array($query);
  $p_ai_renamed_tag = $tags[0] ?? null;

  return $res;
}

/**
 * `Piwigo AI` : sendResponse, after a ws method ran
 */
function p_ai_ws_send_response_tags($encoded_response)
{
  global $p_ai_renamed_tag;

  if (empty($p_ai_renamed_tag))
  {
    return;
  }

  $tag_id = (int)$p_ai_renamed_tag['id'];
  $old_name = $p_ai_renamed_tag['name'];
  $p_ai_renamed_tag = null;

  $query = '
SELECT name
  FROM '.TAGS_TABLE.'
  WHERE id = '.$tag_id.'
;';
  list($name) = pwg_db_fetch_row(pwg_query($query));

  if (null !== $name && $name !== $old_name)
  {
    pwg_query('
UPDATE '.TAGS_TABLE.'
  SET embedding = NULL,
    embedding_model = NULL
  WHERE id = '.$tag_id.'
;');
  }
}
