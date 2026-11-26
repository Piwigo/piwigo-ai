{include file=$P_AI_REALTIME_TPL}
{combine_script id='p_ai_script_tags' load='footer' path="{$P_AI_PATH}admin/js/tags.js"}
{footer_script}
const p_ai_pwg_token = "{$PWG_TOKEN}";
const p_ai_str_ai = "{'Created by the AI'|translate|escape:javascript}";
const p_ai_str_index_tags = "{'Index %d tags'|translate|escape:javascript}";
const p_ai_str_index_one_tag = "{'Index 1 tag'|translate|escape:javascript}";
const p_ai_str_all_tags_indexed = "{'All tags are indexed'|translate|escape:javascript}";
const p_ai_str_indexation_in_progress = "{'Indexation in progress…'|translate|escape:javascript}";
const p_ai_str_indexation_started = "{'The tags indexation has started.'|translate|escape:javascript}";
const p_ai_str_similar = "{'Similar tags'|translate|escape:javascript}";
const p_ai_str_similar_title = "{'Tags similar to “%s”'|translate|escape:javascript}";
const p_ai_str_similar_not_indexed = "{'This tag is not indexed yet. Index your tags first.'|translate|escape:javascript}";
const p_ai_str_similar_none = "{'No similar tag found.'|translate|escape:javascript}";
const p_ai_str_similar_error = "{'The similar tags could not be loaded.'|translate|escape:javascript}";
const p_ai_str_similar_merge_into = "{'The selected tags will be merged into “%s”.'|translate|escape:javascript}";
{/footer_script}

{* the button that indexes the tags: moved after "Add a tag" by the script. Built as that button
   of the core, and hidden with it in selection mode *}
<label class="p-ai-tag-action head-button-2 icon-robot-head not-in-selection-mode" id="p_ai_index_tags" style="display: none;"><p></p></label>

{* ends the line of the buttons in the header of the core: moved before its messages by the script *}
<div class="p-ai-tag-break not-in-selection-mode" id="p_ai_tag_break"></div>

{* the tags closest to a tag: the script fills the title, the message or the rows *}
<div class="bg-modal" id="p_ai_similar_modal">
  <div class="modal-content" style="width: 500px; padding-left: 44px; padding-right: 44px;">
    <a class="close-modal icon-cancel" id="p_ai_similar_close"></a>

    <div class="modal-icon-container">
      <span class="modal-icon-header icon-green icon-search"></span>
    </div>
    <div class="modal-title-header">
      <p id="p_ai_similar_title"></p>
    </div>

    <div class="modal-body">
      <p id="p_ai_similar_message"></p>

      <div id="p_ai_similar_list" class="flex flex-col gap-1.5 text-[13px] text-start">
        <div class="p-ai-similar-row p-ai-muted font-bold text-xs">
          <span>{'Tag'|translate}</span>
          <span>{'Similarity'|translate}</span>
          <span>{'Photos'|translate}</span>
        </div>
        <div id="p_ai_similar_rows" class="flex flex-col gap-1.5"></div>
        {* hidden but in place out of the selection mode: its line is kept *}
        <p id="p_ai_similar_merge_into" class="p-ai-muted text-xs" style="visibility: hidden;"></p>
      </div>
    </div>

    {* the selection mode turns the rows into choices, and Close into the merge button *}
    <div class="modal-footer h-12">
      <div id="p_ai_similar_select_block" class="flex items-center gap-2">
        <label class="switch">
          <input type="checkbox" id="p_ai_similar_select">
          <span class="slider round"></span>
        </label>
        <label for="p_ai_similar_select">{'Selection mode'|translate}</label>
      </div>
      <p class="modal-footer-close ml-auto" id="p_ai_similar_cancel">{'Close'|translate}</p>
      <label class="p-ai-similar-merge head-button-2 icon-object-group ml-auto" id="p_ai_similar_merge" style="display: none;"><p>{'Merge selected tags'|translate}</p></label>
    </div>
  </div>
</div>
