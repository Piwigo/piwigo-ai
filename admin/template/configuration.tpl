{combine_script id='p_ai_script_config' load='footer' path="{$P_AI_PATH}admin/js/configuration.js"}
{footer_script}
const PWG_TOKEN = "{$PWG_TOKEN}";
{/footer_script}
<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16">
  <div class="flex flex-col items-start text-start">
    <p>
      <span class="p-1.25 rounded-full icon-cog-alt icon-green"></span>
      <span class="font-bold text-sm p-1.25">{'General'|translate}</span>
    </p>

    {* <div class="mt-4">
      <label class="switch">
        <input type="checkbox" name="send_picture_file" id="send_picture_file"
          {if $P_AI_CONFIG.send_picture_file} checked {/if}
         disabled
        >
        <span class="slider round"></span>
      </label>
      <label for="send_picture_file" class="font-bold">{'ai_send_picture_file'|translate}</label>
      <p class="text-xs">{'ai_send_picture_file_description'|translate}</p>
    </div>

    <div class="mt-3">
      <label class="switch">
        <input type="checkbox" name="ticket_callback" id="ticket_callback" disabled
          {if $P_AI_CONFIG.ticket_callback} checked {/if}
        >
        <span class="slider round"></span>
      </label>
      <label for="ticket_callback" class="font-bold">{'ai_ticket_callback'|translate}</label>
      <p class="text-xs">{'ai_ticket_callback_description'|translate}</p>
    </div> *}

    <div class="mt-3 flex flex-col text-start">
      <label for="description_prefix" class="font-bold">{"ai_description_prefix"|translate|escape:html}</label>
      <p class="text-xs italic">{'ai_description_prefix_description'|translate}</p>

      <input class="p-ai-input" 
        id="description_prefix" name="description_prefix" type="text" 
        value="{$P_AI_CONFIG.description_prefix}"  
      />
    </div>

    <div class="mt-5">
      <span class="p-1.25 rounded-full icon-robot-head icon-blue"></span>
      <span class="font-bold text-sm p-1.25">{'Server AI'|translate}</span>
    </div>
    
    <div class="mt-4 flex flex-col text-start">
      <label for="url_server_ai" class="font-bold">{"ai_url_server"|translate|escape:html}</label>
      <p class="text-xs italic">{'ai_url_server_description'|translate}</p>

      <input class="p-ai-input" 
        id="url_server_ai" name="url_server_ai" type="text" 
        value="{$P_AI_CONFIG.url_server_ai}"
      />
    </div>

    <div class="mt-3 flex flex-col text-start">
      <label for="api_key" class="font-bold">{"ai_api_key"|translate|escape:html}</label>
      <p class="text-xs italic">{'ai_api_key_description'|translate}</p>

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
