{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const p_ai_str_analysis_completed = "{'Last analysis: %s'|translate|escape:javascript}";
const p_ai_str_analysis_pending = "{'Analysis pending'|translate|escape:javascript}";
const p_ai_str_analysis_none = "{'Not analyzed yet'|translate|escape:javascript}";
const p_ai_str_analyze = "{'Analyse photo with Piwigo AI'|translate|escape:javascript}";
const p_ai_str_analyze_sent = "{'%d photo sent to Piwigo AI'|translate:1|escape:javascript}";
const p_ai_str_analyze_unsent = "{'%d photo will be sent to Piwigo AI later'|translate:1|escape:javascript}";
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

{include file='themes/standard_pages/template/toaster.tpl' toaster_mode=$themeconf.colorscheme}

<div class="bg-modal" id="p_ai_analyze_modal">
  <div class="modal-content">
    <a class="close-modal icon-cancel" id="p_ai_analyze_close"></a>

    <div class="modal-icon-container">
      <span class="modal-icon-header icon-blue icon-robot-head"></span>
    </div>
    <div class="modal-title-header">
      <p>{'Analyse photo with Piwigo AI'|translate}</p>
    </div>

    <div class="modal-body">
      <p>{'Are you sure you want to analyse this photo with Piwigo AI?'|translate}</p>
      <div id="p_ai_analyze_pending" class="badge-unsaved hidden">
        {'An analysis of this photo is already pending. A new request will cost another credit.'|translate}
      </div>
      {$P_AI_ANALYZE_OPTIONS}
      <p>{'As a reminder, this will cost you %s credit.'|translate:$P_AI_ANALYSIS_COST}</p>
    </div>

    <div class="modal-footer">
      <p class="modal-footer-close" id="p_ai_analyze_cancel">{'Cancel'|translate}</p>
      <div class="modal-footer-group">
        <p class="buttonLike" id="p_ai_analyze_confirm"><span class="icon-robot-head mr-1"></span>{'Confirm'|translate}</p>
      </div>
    </div>
  </div>
</div>

<p id="p_ai_description_field" class="hidden">
  <strong>{'AI description'|@translate}</strong>
  <br>
  <textarea id="p_ai_description" class="description" readonly="readonly">{$P_AI_IMG.ai_description|default:''|escape:html}</textarea>
</p>

{if !empty($P_AI_IMG.ocr)}
<div id="p_ai_ocr" class="hidden">
  <div class="mt-3 text-sm text-gray-600 pb-16">
    <a href="#" id="p_ai_ocr_toggle" aria-expanded="false" aria-controls="p_ai_ocr_content">
      <span class="p-ai-ocr-show">
        {'Show text contained in the image'|translate} <i class="icon-down-open" aria-hidden="true"></i>
      </span>
      <span class="p-ai-ocr-hide hidden">
        {'Hide text contained in the image'|translate} <i class="icon-down-open" aria-hidden="true" style="display:inline-block; transform:rotate(180deg)"></i>
      </span>
    </a>
    <div id="p_ai_ocr_content" class="hidden">
    {foreach from=$P_AI_IMG.ocr item=line}
      <p class="my-1">{$line|escape:html}</p>
    {/foreach}
    </div>
  </div>
</div>
{/if}
