<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16 dark:text-[#a1a1a1]!">
  <main>

    <div class="grid grid-cols-[2fr_2.5fr_1fr_2fr_1fr] text-start mb-2 text-gray-400 dark:text-[#9e9e9e]">
      <p class="font-bold text-sm">{'Photo'|translate}</p>
      <p class="font-bold text-sm">{'Actions'|translate}</p>
      <p class="font-bold text-sm">{'Credits'|translate}</p>
      <p class="font-bold text-sm">{'Started'|translate}</p>
      <p class="font-bold text-sm">{'Status'|translate}</p>
    </div>

    {foreach from=$P_AI_TICKETS item=ticket}
      {assign var="opts" value=$ticket.options|json_decode:true}
      <div class="grid grid-cols-[2fr_2.5fr_1fr_2fr_1fr] items-center min-h-10 mb-2.5 shadow-sm text-start bg-[#fafafa] dark:bg-[#333] dark:text-[#a1a1a1]">

        <div class="overflow-hidden text-ellipsis whitespace-nowrap mr-2 pl-2">
          <span class="icon-picture mr-1"></span>
          <a class="font-bold" href="{$ROOT_URL}admin.php?page=photo-{$ticket.image_id}">
            {if $ticket.name}{$ticket.name}{else}{$ticket.file}{/if}
          </a>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap px-2">
          <i class="icon-robot-head"></i>
          {if $opts.caption}
            <span class="p-ai-line-actions">{'Description'|translate}</span>
          {/if}
          {if $opts.ocr}
            <span class="p-ai-line-actions">OCR</span>
          {/if}
          {if $opts.tagging}
            <span class="p-ai-line-actions">{'Tags'|translate}</span>
          {/if}
        </div>

        <div class="px-2.5 text-gray-500 text-sm">
          <i class="icon-ai-token"></i>
          {if $ticket.cost}{$ticket.cost}{else}<span class="text-gray-300">—</span>{/if}
        </div>

        <div class="overflow-hidden whitespace-nowrap px-2">
          <i class="icon-clock"></i>
          <span class="font-bold text-sm">{$ticket.created_at|date_format:'%b %e, %Y'}</span>
          <span class="text-gray-400 text-sm">{$ticket.created_at|date_format:' %H:%M:%S'}</span>
        </div>

        <div class="px-2.5">
          {if $ticket.status == 'completed'}
            <span class="inline-flex items-center gap-1 p-ai-success text-xs px-2 py-1 rounded font-medium">
              <i class="icon-ok"></i> {'Completed'|translate}
            </span>
          {elseif $ticket.status == 'failed'}
            <span class="inline-flex items-center gap-1 p-ai-error ext-xs px-2 py-1 rounded font-medium">
              <i class="icon-cancel"></i> {'Failed'|translate}
            </span>
          {else}
            <span class="inline-flex items-center gap-1 p-ai-waiting text-xs px-2 py-1 rounded italic">
              <i class="icon-clock"></i> {'Pending'|translate}
            </span>
          {/if}
        </div>

      </div>
    {foreachelse}
      <div class="py-5 text-gray-400">{'No tickets yet'|translate}</div>
    {/foreach}

  </main>
</div>
