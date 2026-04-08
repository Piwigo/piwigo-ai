let compat_is_send = false;

$(function() {
  $('#p_ai_check_compatibility').on('click', function(e) {
    e.preventDefault();
    p_ai_check_compat();
  });
});

function p_ai_check_compat(method) {
  if (compat_is_send) return;
  compat_is_send = true;
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.check_compatibility',
    type: "POST",
    dataType: 'json',
    data: {
      pwg_token: p_ai_pwg_token,
    },
    success: function(res) {
      compat_is_send = false;
      if (res.stat === 'ok' && res.result) {
        $.jGrowl( str_success_compatibility, { theme: 'success', header: str_success, life: 4000, sticky: false });
        $('#p_ai_check_compatibility').closest('ul').remove();
        if ($('.eiw .messages').children().length === 0) {
          $('.eiw .messages').remove();
        }
        return;   
      }
      $.jGrowl( str_error_compatibility, { theme: 'error', header: 'Oops !', sticky: true });      
    },
    error: function(e) {
      compat_is_send = false;
      $.jGrowl( str_error_compatibility, { theme: 'error', header: 'Oops !', sticky: true });
    }
  })
}