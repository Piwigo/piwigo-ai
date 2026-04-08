{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_css path="themes/default/js/plugins/jquery.jgrowl.css"}

{combine_script id='p_ai_script_overview' load='footer' path="{$P_AI_PATH}admin/js/overview.js"}
{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const str_success = "{'Success'|translate|escape:javascript}"
const str_success_compatibility = "{'Compatibility check successful, changes have been applied.'|translate|escape:javascript}"
const str_error_compatibility = "{'The database is still not compatible with the required prerequisites.'|translate|escape:javascript}"
{/footer_script}
<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16">
  <main>
    This section coming soon..
  </main>
</div>
