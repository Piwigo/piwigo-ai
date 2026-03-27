<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

<div class="piwigoai m-5 pb-16">
  <main>

    <div class="grid grid-cols-[1.5fr_2fr_1fr_1fr_1.5fr] text-start mb-2">
      <p class="text-gray-400 font-bold text-sm">{'Image'|translate}</p>
      <p class="text-gray-400 font-bold text-sm">{'Date'|translate}</p>
      <p class="text-gray-400 font-bold text-sm">{'Status'|translate}</p>
      <p class="text-gray-400 font-bold text-sm">{'Time'|translate}</p>
      <p class="text-gray-400 font-bold text-sm">{'Details'|translate}</p>
    </div>

    {foreach from=$P_AI_TICKETS item=ticket}
      <div class="grid grid-cols-[1.5fr_2fr_1fr_1fr_1.5fr] items-center min-h-10 mb-2.5 bg-[#fafafa] shadow-sm text-start">

        <div class="overflow-hidden text-ellipsis whitespace-nowrap mr-2 pl-2">
          <span class="icon-picture mr-1"></span>
          <a class="font-bold" href="{$ROOT_URL}admin.php?page=photo-{$ticket.image_id}">
            {if $ticket.name}{$ticket.name}{else}{$ticket.file}{/if}
          </a>
        </div>

        <div class="overflow-hidden whitespace-nowrap mr-2">
          <span class="icon-clock font-bold mr-1"></span>
          <span class="font-bold">{$ticket.created_at|date_format:'%A, %B %e, %Y'}</span>
          <span class="text-gray-400 font-normal">{$ticket.created_at|date_format:' %H:%M:%S'}</span>
        </div>

        <div class="px-2.5 font-bold">{$ticket.status}</div>

        <div class="px-2.5 text-gray-500">{$ticket.process_time} s</div>

        <div class="flex items-center gap-1.5 overflow-hidden">
          {if $ticket.completed_at}
            <span class="inline-flex items-center gap-1 bg-green-100 text-green-600 text-xs px-2 py-1 rounded">
              <i class="icon-ok"></i> {$ticket.completed_at|date_format:'%H:%M:%S'}
            </span>
          {/if}
          {if $ticket.failed_at}
            <span class="inline-flex items-center gap-1 bg-red-100 text-red-500 text-xs px-2 py-1 rounded">
              <i class="icon-cancel"></i> {'error'|translate}
            </span>
          {/if}
          <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded font-normal">
            <i class="icon-key"></i> {$ticket.ticket_id|truncate:8:'':true}
          </span>
        </div>

      </div>
    {foreachelse}
      <div class="py-5 text-gray-400">{'No tickets yet'|translate}</div>
    {/foreach}

  </main>
</div>
