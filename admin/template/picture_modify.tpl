{footer_script}
{literal}
(function() {
  const $aiDescription = $('#p_ai_description_field');
  const $description = $('#description');
  if ($aiDescription.length && $description.length) {
    $description.closest('p').after($aiDescription);
    $aiDescription.removeClass('hidden');
  }

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

<p id="p_ai_description_field" class="hidden">
  <strong>{'AI description'|@translate}</strong>
  <br>
  <textarea id="p_ai_description" class="description" readonly="readonly">{$P_AI_IMG.ai_description|default:''|escape:html}</textarea>
</p>

<div id="p_ai_ocr" class="hidden">
  {if !empty($P_AI_IMG.ocr)}
    <div class="mt-3 text-sm text-gray-600 pb-16">
      <strong>OCR</strong>
      {foreach from=$P_AI_IMG.ocr item=line}
        {* Ensure compatibility with the old format *}
        <p class="my-1">{if isset($line.text)}{$line.text|escape:html}{else}{$line|escape:html}{/if}</p>
      {/foreach}
    </div>
  {/if}
</div>
