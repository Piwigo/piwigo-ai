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
  ];

  $response = p_ai_submit_image($image_info, $options);
  if (isset($response['errors']))
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
 * `Piwigo AI` : loc_end_index
 */
function p_ai_loc_end_index()
{
  global $page, $template;

  if ('search' != $page['section'] or !isset($page['search_details'])) return;

  $template->set_filename('p_ai_search_filters', P_AI_PATH.'template/search_filters.inc.tpl');
  $template->concat('PLUGIN_INDEX_CONTENT_END', $template->parse('p_ai_search_filters', true));
}

/**
 * `Piwigo AI` : get_search_allwords_fields
 */
function p_ai_add_search_allwords_fields($fields)
{
  $fields[] = 'ai_description';
  $fields[] = 'ocr';

  return $fields;
}
