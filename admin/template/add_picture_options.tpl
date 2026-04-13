{combine_script id='jquery.jgrowl' load='footer' require='jquery' path='themes/default/js/plugins/jquery.jgrowl_minimized.js'}
{combine_css path="themes/default/js/plugins/jquery.jgrowl.css"}

{combine_script id='p_ai_script_add_picture_options' load='footer' path="{$P_AI_PATH}admin/js/add_picture_options.js"}
{footer_script}
const str_p_ai_infos_text = "{'%s photos will be analyzed by Piwigo AI'|translate|escape:javascript}";
{/footer_script}
<div class="hidden" id="p_ai_options">
  <div>
    <label class="switch">
      <input type="checkbox" id="togglePwgAiMode" checked>
      <span class="slider round mr-0!"></span>
    </label>
    <div class="ml-1.5 align-top">
      <p class="pb-1.5">{"Analyse photos with Piwigo AI"|@translate}</p>
      <div id="p_ai_options_content" class="flex! items-center gap-2.5 text-xs">
        <label class="font-checkbox flex! items-center tiptip" title="{'Automatically generate a description for each uploaded photo using AI'|translate}">
          <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
          <input type="checkbox" name="caption" id="pAiUploadCaption" checked>
          {'Description'|translate}
        </label>

        <label class="font-checkbox flex! items-center tiptip" title="{'Automatically assign tags to each uploaded photo using AI'|translate}">
          <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
          <input type="checkbox" name="tags" id="pAiUploadTagging" checked>
          {'Tags'|translate}
        </label>

        <label class="font-checkbox flex! items-center tiptip" title="{'Extract and index text found in each uploaded photo using AI'|translate}">
          <span class="icon-check" style="margin: 0; padding: 0; border-radius: 0; font-size: 12px;"></span>
          <input type="checkbox" name="ocr" id="pAiUploadOCR" checked>
          {'OCR'|translate}
        </label>
      </div>
    </div>
  </div>
</div>
<div class="absolute bottom-0 mb-3.75 text-sm" id="p_ai_infos" style="display: none;">
  <div class="global-succes-badge">
		<div class="badge-succes bg-[#D3EAFD]! border-[#4182BE]! text-[#274159]! flex items-center gap-1">
			<i class="icon-info-circled-1 text-[#4182BE]!"></i>
			<p id="p_ai_infos_text"></p>
		</div>
	</div>
</div>