let compat_is_send = false;

$(function() {
  $('#p_ai_check_compatibility').on('click', function(e) {
    e.preventDefault();
    p_ai_check_compat();
  });

  load_recent_tickets();

  if ($('#p-ai-indexation').length) {
    render_tags_state(p_ai_tags_state);
    $('#p-ai-btn-index-tags').on('click', start_tags_indexation);
    if (p_ai_tags_state.in_progress) follow_tags_indexation();
  }
});

let p_ai_tags_following = false;

function render_tags_state(state) {
  p_ai_tags_state = state;
  const pct = state.total > 0 ? Math.round(state.indexed / state.total * 100) : 0;
  $('#p-ai-tags-indexed').text(state.indexed);
  $('#p-ai-tags-total').text(state.total);
  $('#p-ai-tags-bar').css('width', pct + '%');
  $('#p-ai-tags-message').text(state.last_message || '');

  let label = p_ai_str_all_tags_indexed;
  let disabled = true;
  if (state.in_progress) {
    label = p_ai_str_indexation_in_progress;
  } else if (state.to_index > 0) {
    label = state.to_index === 1 ? p_ai_str_index_one_tag : p_ai_str_index_tags.replace('%d', state.to_index);
    disabled = false;
  }
  $('#p-ai-btn-index-tags-label').text(label);
  $('#p-ai-btn-index-tags').css({ opacity: disabled ? 0.5 : 1, 'pointer-events': disabled ? 'none' : '' });
}

function start_tags_indexation() {
  if (p_ai_tags_state.in_progress || p_ai_tags_state.to_index === 0) return;
  render_tags_state($.extend({}, p_ai_tags_state, { in_progress: true }));

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.index_tags',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token },
    success: function(res) {
      if (res.stat === 'ok') {
        $.jGrowl(res.result.message || p_ai_str_indexation_started, { theme: 'success', header: str_success, life: 4000, sticky: false });
        follow_tags_indexation();
        return;
      }
      $.jGrowl(res.message, { theme: 'error', header: 'Oops !', sticky: true });
      refresh_tags_state();
    },
    error: function() {
      $.jGrowl(p_ai_str_tags_indexation, { theme: 'error', header: 'Oops !', sticky: true });
      refresh_tags_state();
    }
  });
}

// the worker only runs on page views: while this page is open, it is run by hand
function follow_tags_indexation() {
  if (p_ai_tags_following) return;
  p_ai_tags_following = true;
  tags_indexation_round();
}

function tags_indexation_round() {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.check_tickets',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token, force: 1 },
    complete: function() {
      refresh_tags_state(function(state) {
        if (state.in_progress) {
          setTimeout(tags_indexation_round, 5000);
        } else {
          p_ai_tags_following = false;
        }
      });
    }
  });
}

function refresh_tags_state(then) {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.tags_indexation',
    type: 'GET',
    dataType: 'json',
    success: function(res) {
      if (res.stat !== 'ok') {
        p_ai_tags_following = false;
        return;
      }
      render_tags_state(res.result);
      if (then) then(res.result);
    },
    error: function() {
      p_ai_tags_following = false;
    }
  });
}

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
  const subject = ticket.type === 'embed_tags'
    ? '<i class="icon-tags text-gray-300 shrink-0"></i>'
      + '<span class="text-sm font-medium truncate">' + p_ai_str_tags_indexation + '</span>'
    : '<i class="icon-picture text-gray-300 shrink-0"></i>'
      + '<a class="text-sm font-medium truncate hover:text-[#F3A73B]" href="' + photo_link + '">' + name + '</a>';

  let status_html;
  if (ticket.status === 'completed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-success text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-ok"></i> ' + p_ai_str_status_completed + '</span>';
  } else if (ticket.status === 'failed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-error text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-cancel"></i> ' + p_ai_str_status_failed + '</span>';
  } else {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-waiting text-xs px-2 py-0.5 rounded italic shrink-0"><i class="icon-clock"></i> ' + p_ai_str_status_pending + '</span>';
  }

  const border = is_last ? '' : ' border-b border-gray-100 dark:border-[#3f3f3f]';
  return '<div class="grid grid-cols-[1fr_auto_auto] items-center py-2' + border + '">'
    + '<div class="flex items-center gap-2 min-w-0">'
    + subject
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