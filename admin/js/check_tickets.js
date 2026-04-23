$(function() {
  p_ai_check_tickets();
});

function p_ai_check_tickets() {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.check_tickets',
    type: "POST",
    dataType: 'json',
    data: {
      pwg_token: p_ai_ct_token,
      exec_id: p_ai_exec,
    },
    success: function(res) {},
    error: function(e) {}
  });
}