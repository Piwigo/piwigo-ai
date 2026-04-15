<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

function p_ai_loc_begin_admin_page_load_tw()
{
  global $template;
  $themeconf = $template->get_template_vars('themeconf');
  if ('dark' == $themeconf['colorscheme'])
  {
    // put tailwind in darkmode
    add_event_handler('loc_begin_page_header', 'p_ai_setprefilter_admins');
  }
  $template->func_combine_css(array('path' => P_AI_PATH.'/css/output.css'));
  $template->func_combine_css(array('path' => P_AI_PATH.'/vendor/fontello/css/fontello.css'));
}

function p_ai_setprefilter_admins()
{
  global $template;
  $template->set_prefilter('header', 'p_ai_prefilter_admin');
}

function p_ai_prefilter_admin($content)
{
  $search = '<html lang="{$lang_info.code}" dir="{$lang_info.direction}">';
  $replace = '<html lang="{$lang_info.code}" dir="{$lang_info.direction}" data-theme="dark">';
  return str_replace($search, $replace, $content);
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

function p_ai_begin_end_admin()
{
  global $template;

  $tickets_lastcheck = pwg_get_session_var('p_ai_tickets_lastcheck', 0);

  if ($tickets_lastcheck != 0 && time() - $tickets_lastcheck < 5)
  {
    return;
  }

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
  pwg_set_session_var('p_ai_tickets_lastcheck', time());

  if (!$tickets) return;

  $template->block_footer_script(null, 'const p_ai_ct_token = "'.get_pwg_token().'";');
  $template->func_combine_script(array(
		"id" => "p_ai_check_tickets",
		"load" => "footer",
		"path" => P_AI_PATH.'/admin/js/check_tickets.js'
	));
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
  global $conf, $page;

  if ('p_ai_analyze' != $action or count($collection) == 0)
  {
    return;
  }

  if (empty($conf['piwigo_ai']['api_key']))
  {
    $page['errors'][] = l10n('Piwigo AI API key is not configured');
    return;
  }

  $options = [
    'caption' => !empty($_POST['p_ai_caption']),
    'tagging' => !empty($_POST['p_ai_tagging']),
    'ocr'     => !empty($_POST['p_ai_ocr']),
  ];

  if (!$options['caption'] && !$options['tagging'] && !$options['ocr'])
  {
    $page['errors'][] = l10n('Please select at least one Piwigo AI option');
    return;
  }

  $success = 0;

  foreach ($collection as $image_id)
  {
    $image_info = get_image_infos($image_id);
    if (empty($image_info))
    {
      continue;
    }

    $response = p_ai_submit_image($image_info, $options);

    if (isset($response['errors']))
    {
      $page['errors'][] = $response['errors'];
      break;
    }

    $success++;
  }

  if ($success > 0)
  {
    $page['infos'][] = l10n_dec('%d photo sent to Piwigo AI', '%d photos sent to Piwigo AI', $success);
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