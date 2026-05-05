{combine_script id='p_ai_script_overview' load='footer' path="{$P_AI_PATH}admin/js/overview.js"}
{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const p_ai_root_url = "{$ROOT_URL}";
const p_ai_str_status_completed = "{'Completed'|translate|escape:javascript}";
const p_ai_str_status_failed = "{'Failed'|translate|escape:javascript}";
const p_ai_str_status_pending = "{'Pending'|translate|escape:javascript}";
const str_success = "{'Success'|translate|escape:javascript}"
const str_success_compatibility = "{'Compatibility check successful, changes have been applied.'|translate|escape:javascript}"
const str_error_compatibility = "{'The database is still not compatible with the required prerequisites.'|translate|escape:javascript}"
{/footer_script}

<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai light m-5 dark:text-[#a1a1a1]!">
  <main>
    {* --- Stats --- *}
    <div class="grid grid-cols-4 gap-3 my-4">

      <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4 flex items-center gap-4">
        <i class="icon-chart-bar text-4xl"></i>
        <div>
          <p class="text-2xl font-bold leading-tight">{$P_AI_STATS.coverage_pct}%</p>
          <p class="text-xs text-gray-400 mt-0.5">{'Gallery coverage'|translate}</p>
          <p class="text-[10px] text-gray-300 dark:text-[#666]">{$P_AI_STATS.analyzed_images} / {$P_AI_STATS.total_images} {'photos'|translate}</p>
        </div>
      </div>

      <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4 flex items-center gap-4">
        <i class="icon-robot-head text-4xl"></i>
        <div>
          <p class="text-2xl font-bold leading-tight">{$P_AI_STATS.completed_jobs}</p>
          <p class="text-xs text-gray-400 mt-0.5">{'Photos analyzed'|translate}</p>
        </div>
      </div>

      <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4 flex items-center gap-4">
        <i class="icon-clock text-4xl"></i>
        <div>
          <p class="text-2xl font-bold leading-tight">{$P_AI_STATS.pending_jobs}</p>
          <p class="text-xs text-gray-400 mt-0.5">{'Pending tickets'|translate}</p>
        </div>
      </div>

      <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4 flex items-center gap-4">
        <i class="icon-tags text-4xl"></i>
        <div>
          <p class="text-2xl font-bold leading-tight">{$P_AI_STATS.tags}</p>
          <p class="text-xs text-gray-400 mt-0.5">{'AI-generated tags'|translate}</p>
        </div>
      </div>

    </div>

    {* --- Main Row --- *}
    <div class="grid grid-cols-[2fr_1fr] gap-3">

      {* Recent analyzed tickets *}
      <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4">
        <h3 class="font-bold text-sm text-gray-400 dark:text-[#9e9e9e] mb-3">{'Recently analyzed'|translate}</h3>

        <div id="p-ai-recent-loading" class="py-3 text-xs text-gray-400">{'Loading...'|translate}</div>
        <div id="p-ai-recent-list"></div>

        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-[#3f3f3f]">
          <a href="admin.php?page=plugin-piwigo_ai-tickets" class="text-xs text-[#F3A73B] hover:underline">{'See all tickets'|translate} →</a>
        </div>
      </div>

      {* Right column *}
      <div class="flex flex-col gap-3">

        {* Session usage *}
        <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4">
          <h3 class="font-bold text-sm text-gray-400 dark:text-[#9e9e9e] mb-4">{'Usage'|translate} <span class="icon-help-circled tiptip cursor-help" title="{'Session usage limits how many photos can be sent to the AI in a 4-hour window. This is separate from credits: credits are consumed per analysis, while the session limit prevents overloading the server with large batch requests.'|translate}"></span></h3>

          <div class="mb-4">
            <div class="flex items-baseline justify-between mb-0.5">
              <span class="text-sm font-medium">{'Current session'|translate}</span>
              <span class="text-xs text-gray-400">UNLIMITED</span>
            </div>
            <p class="text-[10px] text-gray-400 mb-2">{'Resets in'|translate} unlimited</p>
            <div class="w-full bg-gray-200 dark:bg-[#444] rounded-full h-1.5">
              <div class="h-1.5 rounded-full bg-[#F3A73B] w-[2.5%]"></div>
            </div>
          </div>

          <div class="pt-3 border-t border-gray-100 dark:border-[#3f3f3f] flex items-center justify-between">
            <div>
              <p class="text-sm font-medium">{'credits left'|translate}</p>
              <p class="text-[10px] text-gray-400">{'No expiration'|translate}</p>
            </div>
            <p class="text-xl font-bold">{$P_AI_CREDITS}</p>
          </div>

        </div>

        {* AI Server status *}
        <div class="bg-[#fafafa] dark:bg-[#333] rounded shadow-sm p-4">
          <h3 class="font-bold text-sm text-gray-400 dark:text-[#9e9e9e] mb-3">{'AI Server'|translate}</h3>

          <div class="flex items-center gap-2 mb-3">
          {if $P_AI_SERVER_ONLINE}
            <span class="w-2 h-2 rounded-full bg-green-400 shrink-0"></span>
            <span class="text-sm font-medium">{'Online'|translate}</span>
          {else}
            <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
            <span class="text-sm font-medium">{'Offline'|translate}</span>
          {/if}
          </div>

          <div class="flex flex-col gap-1.5 text-xs text-gray-400">
            <div class="flex items-center justify-between">
              <span>URL</span>
              <span class="font-medium truncate ml-2 dark:text-[#a1a1a1]">{$P_AI_SERVER_DOMAIN}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>{'Mode'|translate}</span>
              <span class="font-medium dark:text-[#a1a1a1]">{if $P_AI_CONFIG.is_accessible}{"public"|translate}{else}{"private"|translate}{/if}</span>
            </div>
          </div>
        </div>

      </div>

    </div>

  </main>
  {include file='themes/standard_pages/template/toaster.tpl'}
</div>
