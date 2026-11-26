{combine_script id='p_ai_script_config' load='footer' path="{$P_AI_PATH}admin/js/configuration.js"}
{footer_script}
const PWG_TOKEN = "{$PWG_TOKEN}";
const p_ai_str_mode_public = "{'public'|translate|escape:javascript}";
const p_ai_str_mode_private = "{'private'|translate|escape:javascript}";
{/footer_script}
<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16 dark:text-[#a1a1a1]! grid grid-cols-[5fr_3fr] gap-10 items-start">
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

    <div class="mt-4">
      <label class="switch">
        <input type="checkbox" name="display_ai_description" id="display_ai_description"
          {if $P_AI_CONFIG.display_ai_description} checked {/if}
        >
        <span class="slider round"></span>
      </label>
      <label for="display_ai_description" class="font-bold">
        {'Display AI descriptions in the gallery'|translate}
      </label>
      <p class="text-xs">{'Enable to display AI-generated descriptions after the regular photo description.'|translate}</p>
    </div>

    <div id="description_prefix_container" class="mt-3 ml-16 flex flex-col gap-1 text-start{if !$P_AI_CONFIG.display_ai_description} hidden{/if}">
      <p>
        <label for="description_prefix" class="font-bold">{"Description prefix"|translate|escape:html}</label>
        <span class="text-xs italic">{'Optional text displayed before AI-generated descriptions.'|translate}</span>
      </p>
      <input class="p-ai-input" 
        id="description_prefix" name="description_prefix" type="text" 
        value="{$P_AI_CONFIG.description_prefix|default:''|escape:html}"
      />
    </div>

    <div class="mt-8">
      <span class="p-1.25 rounded-full icon-tags icon-purple"></span>
      <span class="font-bold text-sm p-1.25 dark:text-[#c1c1c1]">{'Tags'|translate}</span>
    </div>

    <div class="mt-4">
      <p class="font-bold">{'Association mode'|translate}</p>
      {assign var=p_ai_smart_available value=$P_AI_TAGS_STATE.indexed > 0}
      {assign var=p_ai_tags_mode value=$P_AI_CONFIG.tags_mode|default:'open'}
      {if !$p_ai_smart_available}{assign var=p_ai_tags_mode value='open'}{/if}
      <div class="flex flex-col gap-2 mt-2">
        <div class="user-list-checkbox p-ai-tags-mode flex items-center gap-2 cursor-pointer" data-value="open"{if $p_ai_tags_mode == 'open'} data-selected="1"{/if}>
          <span class="select-checkbox"></span>
          <span class="user-list-checkbox-label"><span class="font-bold">{'Open'|translate}</span> <span class="text-xs italic">{'the AI creates its own tags.'|translate}</span></span>
        </div>
        <div class="user-list-checkbox p-ai-tags-mode flex items-center gap-2 cursor-pointer{if !$p_ai_smart_available} opacity-50 pointer-events-none{/if}" data-value="smart"{if $p_ai_tags_mode == 'smart'} data-selected="1"{/if}>
          <span class="select-checkbox"></span>
          <span class="user-list-checkbox-label"><span class="font-bold">{'Smart'|translate}</span> <span class="text-xs italic">{'the AI prefers the indexed tags of the gallery, and creates a tag only when it fits the photo better.'|translate}</span></span>
        </div>
        <div class="user-list-checkbox p-ai-tags-mode flex items-center gap-2 cursor-pointer{if !$p_ai_smart_available} opacity-50 pointer-events-none{/if}" data-value="closed"{if $p_ai_tags_mode == 'closed'} data-selected="1"{/if}>
          <span class="select-checkbox"></span>
          <span class="user-list-checkbox-label"><span class="font-bold">{'Closed'|translate}</span> <span class="text-xs italic">{'the AI only uses the indexed tags of the gallery, it never creates one.'|translate}</span></span>
        </div>
      </div>
      {if $P_AI_TAGS_STATE.indexed == 0}
      <div class="badge-unsaved inline-block mt-3 ml-6 text-xs"><i class="icon-attention"></i>{'Index your tags first, from the overview: the smart and closed modes choose among the indexed tags.'|translate}</div>
      {elseif $P_AI_TAGS_STATE.to_index > 0}
      <div class="badge-unsaved inline-block mt-3 ml-6 text-xs"><i class="icon-attention"></i>{'%d tags of the gallery are not indexed: they cannot be chosen until they are indexed, from the Piwigo AI overview.'|translate:$P_AI_TAGS_STATE.to_index}</div>
      {/if}
    </div>
  </div>

  {* AI Server status *}
  <div class="p-ai-card">
    <p class="font-bold text-sm text-center mb-4">{'AI Server'|translate}</p>

    <div class="flex items-center gap-2 mb-3">
    {if $P_AI_SERVER_ONLINE}
      <span class="w-2 h-2 rounded-full bg-green-400 shrink-0"></span>
      <span class="text-sm font-medium">{'Online'|translate}</span>
    {else}
      <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
      <span class="text-sm font-medium">{'Offline'|translate}</span>
    {/if}
    </div>

    <div class="p-ai-muted flex flex-col gap-1.5 text-xs">
      <div class="flex items-center justify-between">
        <span>URL</span>
        <span class="truncate ml-2">{$P_AI_SERVER_DOMAIN}</span>
      </div>
      <div class="flex items-center justify-between">
        <span>{'Analysis'|translate}</span>
        <span>{if $P_AI_ANALYSIS_UP === true}{'Available'|translate}{elseif $P_AI_ANALYSIS_UP === false}{'Unavailable'|translate}{else}{'Unknown'|translate}{/if}</span>
      </div>
      <div class="flex items-center justify-between">
        <span>{'Mode'|translate}</span>
        <span id="p_ai_server_mode">{if $P_AI_CONFIG.is_accessible}{"public"|translate}{else}{"private"|translate}{/if}</span>
      </div>
    </div>
  </div>
</div>
<div class="savebar-footer justify-end!">
  <div class="badge-container hidden" id="p_ai_error_changes">
    <div class="badge-error">
      <i class="icon-cancel"></i>
      <span id="p_ai_error_message" data-default="{"an error happened"|translate|escape:html}">{"an error happened"|translate}</span>
    </div>
  </div>

  <div class="badge-container hidden" id="p_ai_saving_changes">
    <div class="badge-succes">
      <i class="icon-ok-circled"></i>
      {"Changes saved"|translate}
    </div>
  </div>

  <button class="buttonLike" id="p_ai_save_settings"><i class="icon-floppy"></i> {'Save Settings'|translate}</button>
</div>
