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
    },
    success: function(res) {},
    error: function(e) {}
  });
}