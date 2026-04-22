{combine_script id='p_ai_script_config' load='footer' path="{$P_AI_PATH}admin/js/configuration.js"}
{footer_script}
const PWG_TOKEN = "{$PWG_TOKEN}";
{/footer_script}
<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16 dark:text-[#a1a1a1]!">
  <div class="flex flex-col items-start text-start">
    <p>
      <span class="p-1.25 rounded-full icon-cog-alt icon-green"></span>
      <span class="font-bold text-sm p-1.25 dark:text-[#c1c1c1]">{'General'|translate}</span>
    </p>

    <div class="mt-4">
      <label class="switch">
        <input type="checkbox" name="is_accessible" id="is_accessible"
          {if $P_AI_CONFIG.is_accessible} checked {/if}
        >
        <span class="slider round"></span>
      </label>
      <label for="is_accessible" class="font-bold">
        {'Piwigo reachable from AI server'|translate}
        <span class="icon-help-circled tiptip" style="cursor:help" title="{'Enabled: the AI server fetches images by URL and pushes results back via callback (faster, less bandwidth, requires a publicly reachable Piwigo).'|translate}<br /><br />{'Disabled: Piwigo uploads image files and polls the AI server for results (works on local or private installations).'|translate}"></span>
      </label>
      <p class="text-xs">{'Enable if the AI server can reach your Piwigo over the network.'|translate}</p>
    </div>

    <div class="mt-3 flex flex-col text-start">
      <label for="description_prefix" class="font-bold">{"Description prefix"|translate|escape:html}</label>
      <p class="text-xs italic">{'Text prepended to AI-generated descriptions.'|translate}</p>

      <input class="p-ai-input" 
        id="description_prefix" name="description_prefix" type="text" 
        value="{$P_AI_CONFIG.description_prefix}"  
      />
    </div>

    <div class="mt-5">
      <span class="p-1.25 rounded-full icon-robot-head icon-blue"></span>
      <span class="font-bold text-sm p-1.25 dark:text-[#c1c1c1]">{'Server AI'|translate}</span>
    </div>
    
    <div class="mt-4 flex flex-col text-start">
      <label for="url_server_ai" class="font-bold">{"AI server URL"|translate|escape:html}</label>
      <p class="text-xs italic">{'URL of the AI server used for processing.'|translate}</p>

      <input class="p-ai-input" 
        id="url_server_ai" name="url_server_ai" type="text" 
        value="{$P_AI_CONFIG.url_server_ai}"
      />
    </div>

    <div class="mt-3 flex flex-col text-start">
      <label for="api_key" class="font-bold">{"API key"|translate|escape:html}</label>
      <p class="text-xs italic">{'Authentication key for the AI server.'|translate}</p>

      <input class="p-ai-input" 
        id="api_key" name="api_key" type="text" 
        value="{$P_AI_CONFIG.api_key}"
      />
    </div>
  </div>
</div>
<div class="savebar-footer justify-end!">
  <div class="badge-container hidden" id="p_ai_error_changes">
    <div class="badge-error">
      <i class="icon-cancel"></i>
      {"an error happened"|translate}
    </div>
  </div>

  <div class="badge-container hidden" id="p_ai_saving_changes">
    <div class="badge-succes">
      <i class="icon-ok"></i>
      {"Changes saved"|translate}
    </div>
  </div>

  <button class="buttonLike" id="p_ai_save_settings">{'Save Settings'|translate}</button>
</div>
