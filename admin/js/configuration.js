let p_ai_saving = false;

$(function() {
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
  const ticket_callback = $('#ticket_callback').prop('checked');
  const description_prefix = $('#description_prefix').val();
  const ai_api_key = $('#api_key').val();

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.config',
    type: "POST",
    dataType: 'json',
    data: {
      pwg_token: PWG_TOKEN,
      ai_api_key,
      ticket_callback,
      description_prefix
    },
    success: function(res) {
      p_show_success();
    },
    error: function(e) {
      p_show_error();
    },
  });
}
