let p_ai_saving = false;

$(function() {
  const $display_ai_description = $('#display_ai_description');
  const toggle_description_prefix = function() {
    $('#description_prefix_container').toggle($display_ai_description.prop('checked'));
  };

  toggle_description_prefix();
  $display_ai_description.on('change', toggle_description_prefix);

  $('#p_ai_save_settings').on('click', function() {
    if (p_ai_saving) return;
    p_ai_save();
  });
});

function p_show_success() {
  p_ai_saving = false;
  $('#p_ai_error_changes, #p_ai_saving_changes').hide();
  $('#p_ai_saving_changes').show();
}
function p_show_error() {
  p_ai_saving = false;
  $('#p_ai_error_changes, #p_ai_saving_changes').hide();
  $('#p_ai_error_changes').show();
}

function p_ai_save() {
  p_ai_saving = true;
  const is_accessible = $('#is_accessible').prop('checked');
  const display_ai_description = $('#display_ai_description').prop('checked');
  const description_prefix = $('#description_prefix').val();

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.config',
    type: "POST",
    dataType: 'json',
    data: {
      pwg_token: PWG_TOKEN,
      description_prefix,
      is_accessible,
      display_ai_description,
    },
    success: function(res) {
      if (res.stat === 'ok')
      {
        p_show_success();
        return;
      }
      p_show_error();
    },
    error: function(e) {
      p_show_error();
    },
  });
}
