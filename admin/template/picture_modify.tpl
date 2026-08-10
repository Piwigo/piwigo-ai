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

  const $ocrToggle = $('#p_ai_ocr_toggle');
  const $ocrContent = $('#p_ai_ocr_content');
  $ocrToggle.on('click', function(event) {
    event.preventDefault();
    const expanded = $ocrToggle.attr('aria-expanded') === 'true';
    $ocrToggle.attr('aria-expanded', !expanded);
    $ocrContent.toggleClass('hidden', expanded);
    $ocrToggle.find('.p-ai-ocr-show').toggleClass('hidden', !expanded);
    $ocrToggle.find('.p-ai-ocr-hide').toggleClass('hidden', expanded);
  });

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

{if !empty($P_AI_IMG.ocr)}
<div id="p_ai_ocr" class="hidden">
  <div class="mt-3 text-sm text-gray-600 pb-16">
    <div class="text-center">
      <a href="#" id="p_ai_ocr_toggle" aria-expanded="false" aria-controls="p_ai_ocr_content">
        <span class="p-ai-ocr-show">
          {'Show text contained in the image'|translate} <i class="icon-down-open" aria-hidden="true"></i>
        </span>
        <span class="p-ai-ocr-hide hidden">
          {'Hide text contained in the image'|translate} <i class="icon-down-open" aria-hidden="true" style="display:inline-block; transform:translateY(3px) rotate(180deg)"></i>
        </span>
      </a>
    </div>
    <div id="p_ai_ocr_content" class="hidden">
    {foreach from=$P_AI_IMG.ocr item=line}
      <p class="my-1">{$line|escape:html}</p>
    {/foreach}
    </div>
  </div>
</div>
{/if}
