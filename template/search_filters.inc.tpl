{footer_script require='jquery'}
{literal}
(function() {
  const $fields = $('#p_ai_search_word_fields');
  const $comment = $('#comment');
  if (!$fields.length || !$comment.length) return;

  $comment.closest('div').after($fields.children());
  $fields.remove();

  $comment.on('change', function() {
    $('#ai_description').prop('checked', this.checked);
  }).trigger('change');
})();
{/literal}
{/footer_script}

<div id="p_ai_search_word_fields" hidden>
  <input type="checkbox" id="ai_description" name="ai_description" hidden>
  <div>
    <input type="checkbox" id="ocr" name="ocr">
    <label for="ocr">{'Text in the image'|@translate}</label>
  </div>
</div>
