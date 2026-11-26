{combine_script id='p_ai_script_overview' load='footer' path="{$P_AI_PATH}admin/js/overview.js"}
{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const p_ai_root_url = "{$ROOT_URL}";
const p_ai_str_status_completed = "{'Completed'|translate|escape:javascript}";
const p_ai_str_status_failed = "{'Failed'|translate|escape:javascript}";
const p_ai_str_status_pending = "{'Pending'|translate|escape:javascript}";
const p_ai_str_tags_indexation = "{'Tags indexation'|translate|escape:javascript}";
const p_ai_str_index_tags = "{'Index %d tags'|translate|escape:javascript}";
const p_ai_str_index_one_tag = "{'Index 1 tag'|translate|escape:javascript}";
const p_ai_str_all_tags_indexed = "{'All tags are indexed'|translate|escape:javascript}";
const p_ai_str_indexation_in_progress = "{'Indexation in progress…'|translate|escape:javascript}";
const p_ai_str_indexation_started = "{'The tags indexation has started.'|translate|escape:javascript}";
let p_ai_tags_state = {$P_AI_TAGS_STATE};
const str_success = "{'Success'|translate|escape:javascript}"
{/footer_script}

<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai light m-5 dark:text-[#a1a1a1]!">
  <main>
    {* --- Credits, usage, coverage --- *}
    <div class="flex items-stretch justify-between gap-3 mt-4 mb-8">

      <a href="admin.php?page=plugin-piwigo_ai-credits" class="p-ai-pill p-ai-pill-credits">
        <i class="icon-ai-token"></i>
        <span class="text-base font-bold">{if isset($P_AI_CREDITS)}{$P_AI_CREDITS}{else}—{/if}</span>
        <span class="text-xs ml-2">{'Get more'|translate} →</span>
      </a>

      <div class="p-ai-card flex items-center gap-3 py-2! w-1/2">
        <span class="font-bold text-xs">{'Usage'|translate} <span class="icon-help-circled tiptip cursor-help" title="{'Session usage limits how many photos can be sent to the AI in a 4-hour window. This is separate from credits: credits are consumed per analysis, while the session limit prevents overloading the server with large batch requests.'|translate}"></span></span>
        <div class="p-ai-bar flex-1"><div class="p-ai-bar-fill bg-[#F4AB4F]!" style="width: 0%"></div></div>
        <span class="p-ai-muted text-xs">UNLIMITED</span>
      </div>

      <div class="p-ai-pill p-ai-pill-coverage">
        <span class="text-base font-bold">{$P_AI_STATS.coverage_pct}%</span>
        <span class="text-xs">{'gallery covered'|translate}</span>
        <span class="icon-help-circled tiptip cursor-help" title="{'Photos with at least one completed analysis'|translate}"></span>
      </div>

    </div>

    <div class="grid grid-cols-[5fr_3fr] gap-10 items-start">

      <div class="flex flex-col gap-5">

        {* Photos *}
        <div class="p-ai-card">
          <div class="flex items-center gap-3 mb-4">
            <span class="p-ai-card-icon bg-[#E5DEFF] text-[#6D4FC2]"><i class="icon-picture"></i></span>
            <span class="font-bold text-sm">{'Photos'|translate}</span>
            <span class="p-ai-count">{$P_AI_STATS.analyzed_images}</span>
            <span class="p-ai-muted text-xs">{'photos analyzed'|translate}</span>
          </div>

          <div class="flex items-center gap-3">
            <span class="font-bold text-xs">{'Analyzed photos'|translate} <span class="icon-help-circled tiptip cursor-help" title="{'Photos with at least one completed analysis'|translate}"></span></span>
            <div class="p-ai-bar flex-1"><div class="p-ai-bar-fill" style="width: {$P_AI_STATS.coverage_pct}%"></div></div>
            <span class="p-ai-muted text-xs">{$P_AI_STATS.analyzed_images}/{$P_AI_STATS.total_images}</span>
          </div>
        </div>

        {* Tags *}
        <div id="p-ai-indexation" class="p-ai-card">
          <div class="flex items-center gap-3 mb-4">
            <span class="p-ai-card-icon bg-[#FFE6C7] text-[#B45309]"><i class="icon-tags"></i></span>
            <span class="font-bold text-sm">{'Tags'|translate}</span>
            <span class="p-ai-count">{$P_AI_STATS.tags}</span>
            <span class="p-ai-muted text-xs">{'generated tags'|translate}</span>
          </div>

          <div class="flex items-center gap-3 mb-4">
            <span class="font-bold text-xs">{'Indexed tags'|translate} <span class="icon-help-circled tiptip cursor-help" title="{'The indexation computes an embedding of each tag, so that Piwigo AI can then choose your own tags for your photos.'|translate}"></span></span>
            <div class="p-ai-bar flex-1"><div id="p-ai-tags-bar" class="p-ai-bar-fill" style="width: 0%"></div></div>
            <span class="p-ai-muted text-xs"><span id="p-ai-tags-indexed"></span>/<span id="p-ai-tags-total"></span></span>
          </div>

          <div class="flex items-center gap-3">
            <label class="head-button-2 gap-1" id="p-ai-btn-index-tags"><i class="icon-robot-head"></i> <span id="p-ai-btn-index-tags-label"></span></label>
            <p id="p-ai-tags-message" class="p-ai-muted text-xs"></p>
          </div>
          {if $P_AI_POST_MAX_SIZE_LOW}
          <p class="p-ai-muted text-xs mt-2"><i class="icon-attention"></i> {'The PHP post_max_size is below 8 MB: the tag embeddings will come by polling, more slowly.'|translate}</p>
          {/if}
        </div>

      </div>

      {* Recent tickets *}
      <div>
        <div class="flex items-center gap-3 mb-4">
          <span class="p-ai-card-icon bg-[#D3EAFD] text-[#2F6CA8]"><i class="icon-robot-head"></i></span>
          <span class="font-bold text-sm flex-1 text-start">{'Recently analyzed'|translate}</span>
          <span class="p-ai-count">{$P_AI_STATS.pending_jobs}</span>
          <span class="p-ai-muted text-xs">{'Pending tickets'|translate}</span>
        </div>

        <div id="p-ai-recent-loading" class="p-ai-muted py-3 text-xs">{'Loading...'|translate}</div>
        <div id="p-ai-recent-list" class="flex flex-col gap-2"></div>

        <div class="mt-4 text-right">
          <a href="admin.php?page=plugin-piwigo_ai-tickets" class="p-ai-muted text-xs font-bold hover:underline">{'See all tickets'|translate}</a>
        </div>
      </div>

    </div>

  </main>
  {include file='themes/standard_pages/template/toaster.tpl'}
  {include file=$P_AI_REALTIME_TPL}
</div>
