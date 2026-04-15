<div class="titlePage">
  <h2>{'PiwigoAI'|translate}</h2>
</div>

{if $P_AI_CREDITS}
  <div class="piwigoai m-5 pb-16 flex gap-10">
    <div class="flex flex-col items-start text-start space-y-4">
      <p>
        <span class="p-1.25 rounded-full icon-ai-token icon-green"></span>
        <span class="font-bold text-sm p-1.25 dark:text-[#c1c1c1]">{'Credits owned'|translate}</span>
      </p>

      <div class="mt-5 flex flex-col p-3 bg-[#FAFAFA] dark:bg-[#252525] rounded-xl w-105 h-64 shadow-lg">
        <div class="flex-1 m-auto content-center text-center">
          <p class="flex items-center gap-1 text-4xl text-[#FF7700]">{$P_AI_CREDITS} <i
              class="text-base icon-ai-token"></i></p>
          <p>{'credits left'|translate}</p>
        </div>
        <p class="self-center text-[10px]">{'Unused credits wont be refund'|translate}</p>
      </div>
    </div>

    <div class="flex flex-col items-start text-start space-y-4">
      <p>
        <span class="p-1.25 rounded-full icon-basket icon-blue"></span>
        <span class="font-bold text-sm p-1.25 dark:text-[#c1c1c1]">{'Buy credits'|translate}</span>
      </p>

      <div class="mt-5">
        <p>Free credits in beta, Thanks to plg.</p>
      </div>
    </div>
  </div>
{/if}