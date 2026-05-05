let compat_is_send = false;

$(function() {
  $('#p_ai_check_compatibility').on('click', function(e) {
    e.preventDefault();
    p_ai_check_compat();
  });

  load_recent_tickets();
});

function load_recent_tickets() {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.tickets.getList',
    type: 'GET',
    dataType: 'json',
    data: { per_page: 5, page: 0, order: 'created_at', order_direction: 'DESC' },
    success: function(res) {
      $('#p-ai-recent-loading').hide();
      if (res.stat !== 'ok') return;

      const tickets = res.result.tickets;
      if (!tickets || tickets.length === 0) return;

      $.each(tickets, function(i, ticket) {
        const is_last = i === tickets.length - 1;
        $('#p-ai-recent-list').append(render_recent_row(ticket, is_last));
      });
    },
    error: function() {
      $('#p-ai-recent-loading').hide();
    }
  });
}

function render_recent_row(ticket, is_last) {
  const name = $('<span>').text(ticket.name || ticket.file || '').html();
  const photo_link = p_ai_root_url + 'admin.php?page=photo-' + ticket.image_id;

  let status_html;
  if (ticket.status === 'completed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-success text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-ok"></i> ' + p_ai_str_status_completed + '</span>';
  } else if (ticket.status === 'failed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-error text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-cancel"></i> ' + p_ai_str_status_failed + '</span>';
  } else {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-waiting text-xs px-2 py-0.5 rounded italic shrink-0"><i class="icon-clock"></i> ' + p_ai_str_status_pending + '</span>';
  }

  const border = is_last ? '' : ' border-b border-gray-100';
  return '<div class="grid grid-cols-[1fr_auto_auto] items-center py-2' + border + '">'
    + '<div class="flex items-center gap-2 min-w-0">'
    + '<i class="icon-picture text-gray-300 shrink-0"></i>'
    + '<a class="text-sm font-medium truncate hover:text-[#F3A73B]" href="' + photo_link + '">' + name + '</a>'
    + '</div>'
    + '<span class="text-xs text-gray-400 px-3 shrink-0"><i class="icon-ai-token"></i> ' + (ticket.cost || '—') + '</span>'
    + status_html
    + '</div>';
}

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