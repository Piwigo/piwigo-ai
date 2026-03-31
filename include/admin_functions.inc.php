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

  p_ai_display_menu();

  switch ($page['page'])
    {
        case 'photo':
          p_ai_display_photo();
          break;

        case 'photos_add':
          p_ai_display_add_options();
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

function p_ai_display_menu()
{
  global $template;

  $content = '<dl><dt><a href="./admin.php?page=plugin-piwigo_ai" class="admin-main"><i class="icon-robot-head"> </i><span>Piwigo AI&nbsp;</span></a></dt></dl>';

  $template->block_footer_script(null, '$("#menubar").append(\''.$content.'\');');
}

function p_ai_display_add_options()
{
  global $template, $conf;

  $display_formats = $conf['enable_formats'] && isset($_GET['formats']);
  if ($display_formats) return;

  $template->set_filename('p_ai_picture_options', P_AI_PATH.'/admin/template/add_picture_options.tpl');
  $template->parse('p_ai_picture_options');
}

function p_ai_element_set_global_action($action, $collection)
{
  if ('p_ai_analyze' == $action and count($collection) > 0)
  {
    
  }
}

function p_ai_element_set_global_add_action()
{
  global $template, $page;
  
  $template->set_filename('p_ai_analyze_options', realpath(P_AI_PATH.'/admin/template/batch_manager_global_options.tpl'));

  $template->append(
    'element_set_global_plugins_actions',
    array(
      'ID' => 'p_ai_analyze',
      'NAME' => l10n('Piwigo AI'),
      'CONTENT' => $template->parse('p_ai_analyze_options', true),
      )
    );
}