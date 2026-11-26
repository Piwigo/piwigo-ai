$(function() {
  load_recent_tickets();

  if ($('#p-ai-indexation').length) {
    render_tags_state(p_ai_tags_state);
    $('#p-ai-btn-index-tags').on('click', start_tags_indexation);

    // the card follows the indexation, whoever started it
    p_ai_realtime.subscribe({
      method: 'pwg.ai.tags_indexation',
      change: render_tags_state,
      active: function(state) {
        return state.in_progress;
      },
    });
  }
});

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

  // shown in progress at once; back to the state before if the server refuses
  const before = p_ai_tags_state;
  render_tags_state($.extend({}, before, { in_progress: true }));

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.index_tags',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token },
    success: function(res) {
      if (res.stat === 'ok') {
        p_ai_realtime.refresh();
        pwgToaster({ text: res.result.message || p_ai_str_indexation_started, icon: 'success' });
        return;
      }
      render_tags_state(before);
      pwgToaster({ text: res.message, icon: 'error' });
    },
    error: function(e) {
      render_tags_state(before);
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

function load_recent_tickets() {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.tickets.getList',
    type: 'GET',
    dataType: 'json',
    data: { per_page: 10, page: 0, order: 'created_at', order_direction: 'DESC' },
    success: function(res) {
      $('#p-ai-recent-loading').hide();
      if (res.stat !== 'ok') return;

      const tickets = res.result.tickets;
      if (!tickets || tickets.length === 0) return;

      $.each(tickets, function(i, ticket) {
        $('#p-ai-recent-list').append(render_recent_row(ticket));
      });
    },
    error: function() {
      $('#p-ai-recent-loading').hide();
    }
  });
}

function render_recent_row(ticket) {
  const name = $('<span>').text(ticket.name || ticket.file || '').html();
  const photo_link = p_ai_root_url + 'admin.php?page=photo-' + ticket.image_id;
  const subject = ticket.type === 'embed_tags'
    ? '<i class="icon-tags p-ai-muted shrink-0"></i>'
      + '<span class="text-xs font-medium truncate">' + p_ai_str_tags_indexation + '</span>'
    : '<i class="icon-picture p-ai-muted shrink-0"></i>'
      + '<a class="text-xs font-medium truncate hover:text-[#F3A73B]" href="' + photo_link + '">' + name + '</a>';

  let status_html;
  if (ticket.status === 'completed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-success text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-ok"></i> ' + p_ai_str_status_completed + '</span>';
  } else if (ticket.status === 'failed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-error text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-cancel-circled"></i> ' + p_ai_str_status_failed + '</span>';
  } else {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-waiting text-xs px-2 py-0.5 rounded shrink-0"><i class="icon-clock"></i> ' + p_ai_str_status_pending + '</span>';
  }

  return '<div class="p-ai-row grid grid-cols-[1fr_auto_auto] items-center">'
    + '<div class="flex items-center gap-2 min-w-0">'
    + subject
    + '</div>'
    + '<span class="p-ai-muted text-xs px-3 shrink-0"><i class="icon-ai-token"></i> ' + (ticket.cost || '—') + '</span>'
    + status_html
    + '</div>';
}
