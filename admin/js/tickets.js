let p_ai_page = 0;
let p_ai_per_page = 10;
let p_ai_order = 'created_at';
let p_ai_dir = 'DESC';
let p_ai_status = 'pending';

const p_ai_sortable_cols = ['cost', 'created_at'];

$(function() {
  update_sort_icons();
  load_tickets();

  $('.advanced-filter-btn').on('click', function() {
    const is_open = $('#p-ai-advanced-filter').is(':visible');
    if (is_open) {
      $('#p-ai-advanced-filter').slideUp(150);
      $('.advanced-filter-btn, .advanced-filter').removeClass('advanced-filter-open');
    } else {
      $('#p-ai-advanced-filter').slideDown(150);
      $('.advanced-filter-btn, .advanced-filter').addClass('advanced-filter-open');
    }
  });

$('#p-ai-filter-status').on('change', function() {
    p_ai_status = $(this).val();
    p_ai_page = 0;
    load_tickets();
  });

  p_ai_sortable_cols.forEach(function(col) {
    $('#p-ai-col-' + col).on('click', function() {
      if (p_ai_order === col) {
        p_ai_dir = p_ai_dir === 'DESC' ? 'ASC' : 'DESC';
      } else {
        p_ai_order = col;
        p_ai_dir = 'DESC';
      }
      p_ai_page = 0;
      update_sort_icons();
      load_tickets();
    });
  });

  $(document).on('click', '.p-ai-per-page', function(e) {
    e.preventDefault();
    p_ai_per_page = parseInt($(this).data('value'));
    p_ai_page = 0;
    load_tickets();
  });

  $('#p-ai-btn-force-check').on('click', force_check_tickets);
  $('#p-ai-btn-retry-failed').on('click', retry_failed_tickets);
  $('#p-ai-btn-purge').on('click', purge_completed_tickets);
});

function force_check_tickets() {
  const icon = $('#p-ai-btn-force-check').find('i');
  icon.addClass('icon-spin animate-spin');

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.check_tickets',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token, force: true },
    success: function(res) {
      icon.removeClass('icon-spin animate-spin');
      if (res.stat === 'ok') {
        pwgToaster({ text: p_ai_str_tickets_processed.replace('%d', res.result.processed), icon: 'success' });
        load_tickets();
      }
    },
    error: function(e) {
      icon.removeClass('icon-spin animate-spin');
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

function retry_failed_tickets() {
  const icon = $('#p-ai-btn-retry-failed').find('i');
  icon.addClass('icon-spin animate-spin');

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.retry_failed',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token },
    success: function(res) {
      icon.removeClass('icon-spin animate-spin');
      if (res.stat === 'ok') {
        pwgToaster({ text: p_ai_str_tickets_retried.replace('%d', res.result.retried), icon: 'success' });
        load_tickets();
      }
    },
    error: function(e) {
      icon.removeClass('icon-spin animate-spin');
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

function purge_completed_tickets() {
  const icon = $('#p-ai-btn-purge').find('i');
  icon.addClass('icon-spin animate-spin');

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.purge_tickets',
    type: 'POST',
    dataType: 'json',
    data: { pwg_token: p_ai_pwg_token, status: 'failed' },
    success: function(res) {
      icon.removeClass('icon-spin animate-spin');
      if (res.stat === 'ok') {
        pwgToaster({ text: p_ai_str_tickets_deleted.replace('%d', res.result.deleted), icon: 'success' });
        p_ai_page = 0;
        load_tickets();
      }
    },
    error: function(e) {
      icon.removeClass('icon-spin animate-spin');
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}


function update_sort_icons() {
  p_ai_sortable_cols.forEach(function(col) {
    const icon = $('#p-ai-icon-' + col);
    if (col === p_ai_order) {
      icon.attr('class', p_ai_dir === 'ASC' ? 'icon-up' : 'icon-down').show();
    } else {
      icon.hide();
    }
  });
}

function load_tickets() {
  $('#p-ai-tickets-loading').show();
  $('#p-ai-tickets-list').empty();
  $('#p-ai-tickets-empty').hide();
  $('#p-ai-tickets-footer').hide();

  const data = {
    page: p_ai_page,
    per_page: p_ai_per_page,
    order: p_ai_order,
    order_direction: p_ai_dir,
  };

  if (p_ai_status) {
    data.status = p_ai_status;
  }

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.tickets.getList',
    type: 'GET',
    dataType: 'json',
    data: data,
    success: function(res) {
      $('#p-ai-tickets-loading').hide();

      if (res.stat !== 'ok') return;

      const tickets = res.result.tickets;
      const paging = res.result.paging;

      if (!tickets || tickets.length === 0) {
        const msg = p_ai_status === 'pending' ? p_ai_str_empty_pending
          : p_ai_status === 'failed' ? p_ai_str_empty_failed
          : p_ai_str_empty_all;
        $('#p-ai-tickets-empty').text(msg).show();
        return;
      }

      $.each(tickets, function(i, ticket) {
        $('#p-ai-tickets-list').append(render_row(ticket));
      });

      render_footer(paging);
    },
    error: function() {
      $('#p-ai-tickets-loading').hide();
    }
  });
}

function render_row(ticket) {
  let opts = {};
  try { opts = JSON.parse(ticket.options || '{}'); } catch(e) {}

  const name = $('<span>').text(ticket.name || ticket.file || '').html();
  const photo_link = p_ai_root_url + 'admin.php?page=photo-' + ticket.image_id;

  let actions = '<i class="icon-robot-head"></i>';
  if (opts.caption) actions += ' <span class="p-ai-line-actions">Description</span>';
  if (opts.ocr) actions += ' <span class="p-ai-line-actions">OCR</span>';
  if (opts.tagging) actions += ' <span class="p-ai-line-actions">Tags</span>';

  const cost = ticket.cost
    ? '<i class="icon-ai-token"></i> ' + ticket.cost
    : '<i class="icon-ai-token"></i> <span class="text-gray-300">—</span>';

  const date = format_date(ticket.created_at_format, ticket.created_at);

  let status_html;
  if (ticket.status === 'completed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-success text-xs px-2 py-1 rounded font-medium"><i class="icon-ok"></i> ' + p_ai_str_status_completed + '</span>';
  } else if (ticket.status === 'failed') {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-error text-xs px-2 py-1 rounded font-medium"><i class="icon-cancel"></i> ' + p_ai_str_status_failed + '</span>';
  } else {
    status_html = '<span class="inline-flex items-center gap-1 p-ai-waiting text-xs px-2 py-1 rounded italic"><i class="icon-clock"></i> ' + p_ai_str_status_pending + '</span>';
  }

  return '<div class="grid grid-cols-[2fr_2.5fr_1fr_2fr_1fr] items-center min-h-10 mb-2.5 shadow-sm text-start bg-[#fafafa] dark:bg-[#333] dark:text-[#a1a1a1]">'
    + '<div class="overflow-hidden text-ellipsis whitespace-nowrap mr-2 pl-2"><span class="icon-picture mr-1"></span><a class="font-bold" href="' + photo_link + '">' + name + '</a></div>'
    + '<div class="flex items-center gap-1.5 flex-wrap px-2">' + actions + '</div>'
    + '<div class="px-2.5 text-gray-500 text-sm">' + cost + '</div>'
    + '<div class="overflow-hidden whitespace-nowrap px-2"><i class="icon-clock"></i> ' + date + '</div>'
    + '<div class="px-2.5">' + status_html + '</div>'
    + '</div>';
}

function render_footer(paging) {
  $('.p-ai-per-page').removeClass('selected-pagination');
  $('.p-ai-per-page[data-value="' + p_ai_per_page + '"]').addClass('selected-pagination');

  let html = '';
  if (paging.page > 0) {
    html += '<a href="#" class="pagination-arrow left p-ai-go-page" data-page="' + (paging.page - 1) + '"><span class="icon-left-open"></span></a>';
  } else {
    html += '<a class="pagination-arrow left unavailable"><span class="icon-left-open"></span></a>';
  }

  html += '<div class="pagination-item-container">' + pagination_pages(paging.page, paging.total_pages) + '</div>';

  if (paging.page < paging.total_pages - 1) {
    html += '<a href="#" class="pagination-arrow rigth p-ai-go-page" data-page="' + (paging.page + 1) + '"><span class="icon-left-open"></span></a>';
  } else {
    html += '<a class="pagination-arrow rigth unavailable"><span class="icon-left-open"></span></a>';
  }

  $('#p-ai-pagination').html(html);
  $('#p-ai-tickets-footer').css('display', 'flex');

  $(document).off('click', '.p-ai-go-page').on('click', '.p-ai-go-page', function(e) {
    e.preventDefault();
    p_ai_page = parseInt($(this).data('page'));
    load_tickets();
  });
}

function format_date(date_str_format, date_str) {
  const d = new Date(date_str);
  const h = String(d.getHours()).padStart(2, '0');
  const m = String(d.getMinutes()).padStart(2, '0');
  const s = String(d.getSeconds()).padStart(2, '0');
  return '<span class="font-bold text-sm">' + date_str_format + '</span>'
    + '<span class="text-gray-400 text-sm"> ' + h + ':' + m + ':' + s + '</span>';
}

function pagination_pages(current, total) {
  if (total <= 1) return '';

  const pages = {};
  pages[0] = true;
  pages[total - 1] = true;
  if (current - 1 >= 0) pages[current - 1] = true;
  pages[current] = true;
  if (current + 1 < total) pages[current + 1] = true;

  const sorted = Object.keys(pages).map(Number).sort((a, b) => a - b);

  let html = '';
  let prev = -1;
  for (const p of sorted) {
    if (prev !== -1 && p > prev + 1) {
      html += '<span>...</span>';
    }
    if (p === current) {
      html += '<a class="actual">' + (p + 1) + '</a>';
    } else {
      html += '<a href="#" class="p-ai-go-page" data-page="' + p + '">' + (p + 1) + '</a>';
    }
    prev = p;
  }
  return html;
}
