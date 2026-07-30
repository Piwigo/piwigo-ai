{footer_script require='jquery'}
{literal}
(function() {
  const $fields = $('#p_ai_search_word_fields');
  const $comment = $('#comment');
  if (!$fields.length || !$comment.length) return;

  $comment.closest('div').after($fields.children());
  $fields.remove();
})();
{/literal}
{/footer_script}

<div id="p_ai_search_word_fields" hidden>
  <div>
    <input type="checkbox" id="ai_description" name="ai_description">
    <label for="ai_description">{'AI description'|@translate}</label>
  </div>
  <div>
    <input type="checkbox" id="ocr" name="ocr">
    <label for="ocr">{'OCR'|@translate}</label>
  </div>
</div>
