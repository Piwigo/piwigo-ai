<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_loc_begin_admin_page_load_tw()
{
  global $template;
  $template->func_combine_css(array('path' => P_AI_PATH.'/css/output.css'));
  $template->func_combine_css(array('path' => P_AI_PATH.'/vendor/fontello/css/fontello.css'));
}

function p_ai_loc_end_admin()
{
  global $page;

  if (!isset($page['page'])) return;

  switch ($page['page'])
    {
        case 'photo':
            p_ai_display_photo();
            break;

        default:
            break;
    }
}

function p_ai_display_photo()
{
  global $template, $page;

  $img = $page['image'];
  unset($img['embedding']);
  if (!empty($img['ocr']))
  {
    $img['ocr'] = json_decode($img['ocr'], true);
  }

  $template->set_filename('p_ai_picture_modify', P_AI_PATH.'/admin/template/picture_modify.tpl');
  $template->assign(array('P_AI_IMG' => $img));
  $template->parse('p_ai_picture_modify');
}