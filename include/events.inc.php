<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/**
 * `Piwigo AI` : loc_end_add_uploaded_file
 */
function p_ai_loc_end_add_uploaded_file($image_info)
{
  global $conf;

  if (empty($conf[ 'piwigo_ai' ][ 'api_key' ])) return;

  $ai = filter_var($_POST['ai'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
  if (!$ai) return;

  $options = [
    'caption' => filter_var($_POST['caption'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
    'tagging' => filter_var($_POST['tagging'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
    'ocr' => filter_var($_POST['ocr'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
  ];

  p_ai_submit_image($image_info, $options);
}
