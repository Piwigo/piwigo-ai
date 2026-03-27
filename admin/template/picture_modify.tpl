{footer_script}
{literal}
(function() {
  const $ocr = $('#p_ai_ocr');
  if (!$ocr.length) return;

  const $preview = $('#picture-preview');
  const $wrapper = $('<div>').css({
    display: 'flex',
    flexDirection: 'column',
    flexShrink: 0,
    maxWidth: $preview.css('max-width'),
    width: $preview.css('width'),
    marginLeft: $preview.css('margin-left'),
  });

  $preview.css({ marginLeft: 0, maxWidth: '100%' });
  $preview.after($wrapper);
  $wrapper.append($preview).append($ocr);
  $ocr.removeClass('hidden');
})();
{/literal}
{/footer_script}

<div id="p_ai_ocr" class="hidden">
  {if !empty($P_AI_IMG.ocr)}
    <div class="mt-3 text-sm text-gray-600 pb-16">
      <strong>OCR</strong>
      {foreach from=$P_AI_IMG.ocr item=line}
        <p class="my-1">{$line.text}</p>
      {/foreach}
    </div>
  {/if}
</div>
