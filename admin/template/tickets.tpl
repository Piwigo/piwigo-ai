{combine_script id='p_ai_script_tickets' load='footer' path="{$P_AI_PATH}admin/js/tickets.js"}
{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const p_ai_root_url = "{$ROOT_URL}";
const p_ai_exec_id = "{$CHECK_TICKETS_EXEC_ID}";
const p_ai_str_empty_all = "{'No tickets yet'|translate|escape:javascript}";
const p_ai_str_empty_pending = "{'No pending tickets at the moment'|translate|escape:javascript}";
const p_ai_str_empty_failed = "{'No failed tickets'|translate|escape:javascript}";
const p_ai_str_status_completed = "{'Completed'|translate|escape:javascript}";
const p_ai_str_status_failed = "{'Failed'|translate|escape:javascript}";
const p_ai_str_status_pending = "{'Pending'|translate|escape:javascript}";
const p_ai_str_tickets_processed = "{'%d tickets processed'|translate|escape:javascript}";
const p_ai_str_tickets_retried = "{'%d tickets resubmitted'|translate|escape:javascript}";
const p_ai_str_tickets_deleted = "{'%d tickets deleted'|translate|escape:javascript}";
{/footer_script}

<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai light m-5 dark:text-[#a1a1a1]!">
  <main>

    <div class="flex items-center justify-between mb-2">

      <div class="flex items-center gap-2">
        <label class="head-button-2 gap-1" id="p-ai-btn-force-check">
          <i class="icon-cw"></i>
          <p>{'Check tickets'|translate}</p>
        </label>

        <label class="head-button-2 gap-1" id="p-ai-btn-retry-failed">
          <i class="icon-arrows-cw"></i>
          <p>{'Retry failed'|translate}</p>
        </label>

        <label class="head-button-2 gap-1" id="p-ai-btn-purge">
          <i class="icon-trash"></i>
          <p>{'Purge failed'|translate}</p>
        </label>
      </div>

      <div class="advanced-filter-btn icon-filter" style="margin:0">
        <span>{'Filters'|translate}</span>
      </div>

    </div>

    <div class="advanced-filter mb-2" id="p-ai-advanced-filter" style="display:none">
      <div class="advanced-filter-header">
        <span class="advanced-filter-title">{'Advanced filters'|translate}</span>
      </div>
      <div class="advanced-filter-container">

        <div class="advanced-filter-item">
          <label class="advanced-filter-item-label">{'Status'|translate}</label>
          <div class="advanced-filter-item-container">
            <select class="user-action-select advanced-filter-select" id="p-ai-filter-status">
              <option value="">{'All'|translate}</option>
              <option value="pending" selected>{'Pending'|translate}</option>
              <option value="completed">{'Completed'|translate}</option>
              <option value="failed">{'Failed'|translate}</option>
            </select>
          </div>
        </div>

      </div>
    </div>

    <div class="grid grid-cols-[2fr_2.5fr_1fr_2fr_1fr] text-start mb-2 mt-4 text-gray-400 dark:text-[#9e9e9e]">
      <p class="font-bold text-sm">{'Photo'|translate}</p>
      <p class="font-bold text-sm">{'Actions'|translate}</p>
      <p class="group font-bold text-sm cursor-pointer select-none hover:text-[#F3A73B]" id="p-ai-col-cost">{'Credits'|translate} <span id="p-ai-icon-cost" class="icon-up group-hover:inline!" style="display:none"></span></p>
      <p class="group font-bold text-sm cursor-pointer select-none hover:text-[#F3A73B]" id="p-ai-col-created_at">{'Started'|translate} <span id="p-ai-icon-created_at" class="icon-down group-hover:inline!"></span></p>
      <p class="font-bold text-sm">{'Status'|translate}</p>
    </div>

    <div id="p-ai-tickets-list"></div>

    <div id="p-ai-tickets-loading" class="py-5 text-gray-400 text-sm">
      {'Loading...'|translate}
    </div>

    <div id="p-ai-tickets-empty" class="py-5 text-gray-400" style="display:none">
      {'No tickets yet'|translate}
    </div>

    <div id="p-ai-tickets-footer" style="display:none; justify-content:space-between; padding: 1em; height: 33px; margin: 10px 0;">

      <div class="pagination-per-page">
        <span style="font-weight:bold; color:unset;">{'tickets per page'|translate}&nbsp;:</span>
        <a href="#" class="p-ai-per-page" data-value="10">10</a>
        <a href="#" class="p-ai-per-page" data-value="25">25</a>
        <a href="#" class="p-ai-per-page" data-value="50">50</a>
        <a href="#" class="p-ai-per-page" data-value="100">100</a>
      </div>

      <div id="p-ai-pagination" class="pagination-container"></div>

    </div>

  </main>
  {include file='themes/standard_pages/template/toaster.tpl'}
</div>
