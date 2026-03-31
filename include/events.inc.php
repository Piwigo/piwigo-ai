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

  $img = null;
  $send_as_file = false;

  if ($conf[ 'piwigo_ai' ][ 'send_picture_file' ])
  {
    // TODO SEND picture file instead of url
    $img = $image_info['path'];
    $send_as_file = true;
  }
  else
  {
    $img = new SrcImage($image_info)->get_url();
  }
  
  $callback = null;

  if ($conf[ 'piwigo_ai' ][ 'ticket_callback' ])
  {
    $callback = get_root_url().'ws.php?format=json&method=pwg.ai.analyze';
    //$callback = get_root_url();
  }

  $response = p_ai_analyze($img, $callback, $send_as_file, $options);

  single_insert(
    P_AI_TICKETS_TABLE,
    array(
      'ticket_id' => $response['ticket_id'],
      'image_id' => $image_info['id'],
      'status' => $response['status'],
      'options' => $response['options'],
      'cost' => $response['cost'],
      'use_callback' => $callback ? 'true' : 'false',
    )
  );
}

// TODO
// use_callback
// Faire en sorte que le piwigo à chaque page check s'il doit récupérer des données
// à limité par un appel par minute max

