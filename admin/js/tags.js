$(function() {
  const container = document.querySelector('.tag-container');
  if (!container || typeof p_ai_tags === 'undefined') return;

  const ai_ids = new Set(p_ai_tags.ai.map(String));

  function mark_tag_box(box) {
    const name = box.querySelector('.tag-name');
    if (!name) return;

    const is_ai = ai_ids.has(box.getAttribute('data-id'));
    const robot = box.querySelector('.p-ai-tag-robot');

    if (is_ai && !robot) {
      // next to the name, never inside it: the core reads the name with .html()
      const span = document.createElement('span');
      span.className = 'p-ai-tag-robot icon-robot-head';
      span.title = p_ai_str_ai;
      span.setAttribute('aria-label', p_ai_str_ai);
      span.style.marginRight = '6px';
      name.parentNode.insertBefore(span, name);

      // the options of the core for its .tiptip (admin footer.tpl)
      $(span).tipTip({ delay: 0, fadeIn: 200, fadeOut: 200 });
    } else if (!is_ai && robot) {
      // a recycled box keeps its children: the robot of the previous tag goes
      $(robot).remove();
    }

    // the "Similar tags" entry of the menu of the tag, before Delete. With the class of the core
    // entries: the core closes the menu on any click outside a .dropdown-option
    const menu = box.querySelector('.dropdown-content');
    if (menu && !menu.querySelector('.p-ai-similar')) {
      const entry = document.createElement('a');
      entry.className = 'dropdown-option icon-search p-ai-similar';
      entry.textContent = ' ' + p_ai_str_similar;
      menu.insertBefore(entry, menu.querySelector('.dropdown-option.delete'));
    }
  }

  container.querySelectorAll('.tag-box').forEach(mark_tag_box);

  // the core creates and recycles its tag boxes (pagination, search, add, duplicate)
  new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      // a recycled box: same element, another tag id
      if (mutation.type === 'attributes') {
        if (mutation.target.classList.contains('tag-box')) mark_tag_box(mutation.target);
        return;
      }

      mutation.addedNodes.forEach(function(node) {
        if (node.nodeType !== 1) return;
        if (node.classList.contains('tag-box')) mark_tag_box(node);
        node.querySelectorAll('.tag-box').forEach(mark_tag_box);
      });
    });
  }).observe(container, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-id'] });

  // one handler for every box, present or to come
  $(container).on('click', '.p-ai-similar', function() {
    const box = $(this).closest('.tag-box');
    box.find('.tag-dropdown-block').hide();

    // attr, not data: jQuery keeps the first data-id of a recycled box
    p_ai_show_similar_tags(box.attr('data-id'), box.find('.tag-name').text());
  });
});

// the tag whose similar tags are shown: the destination of a merge
let p_ai_similar_tag = null;

// the tags closest to a tag, in the modal of admin/template/tags.tpl: name, similarity and
// number of photos, or a message when there is nothing to list
function p_ai_show_similar_tags(tag_id, tag_name) {
  p_ai_similar_tag = { id: tag_id, name: tag_name };

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.similar_tags',
    type: 'GET',
    dataType: 'json',
    data: { tag_id: tag_id },
    success: function(res) {
      if (res.stat !== 'ok') {
        p_ai_open_similar_tags([], p_ai_str_similar_error);
      } else if (!res.result.indexed) {
        p_ai_open_similar_tags([], p_ai_str_similar_not_indexed);
      } else if (res.result.tags.length === 0) {
        p_ai_open_similar_tags([], p_ai_str_similar_none);
      } else {
        p_ai_open_similar_tags(res.result.tags, null);
      }
    },
    error: function() {
      p_ai_open_similar_tags([], p_ai_str_similar_error);
    }
  });
}

function p_ai_open_similar_tags(tags, message) {
  $('#p_ai_similar_title').text(p_ai_str_similar_title.replace('%s', p_ai_similar_tag.name));
  $('#p_ai_similar_merge_into').text(p_ai_str_similar_merge_into.replace('%s', p_ai_similar_tag.name));
  $('#p_ai_similar_message').text(message || '').toggle(message !== null);
  $('#p_ai_similar_list, #p_ai_similar_select_block').toggle(message === null);

  // names are set as text, never as HTML. The round box is the one of the core, shown in
  // selection mode only
  const rows = $('#p_ai_similar_rows').empty();
  tags.forEach(function(tag) {
    rows.append($('<div class="p-ai-similar-row user-list-checkbox" data-selected="0">').attr('data-id', tag.id)
      .append('<span class="p-ai-similar-check"><span class="select-checkbox"></span></span>')
      .append($('<span class="p-ai-similar-name font-bold truncate">').text(tag.name))
      .append($('<span>').text(Math.round(tag.score * 100) + '%'))
      .append($('<span>').append('<i class="icon-picture"></i> ').append(document.createTextNode(tag.counter))));
  });

  // each opening starts out of the selection mode
  $('#p_ai_similar_select').prop('checked', false);
  p_ai_set_similar_selection(false);

  $('#p_ai_similar_modal').fadeIn();
}

function p_ai_set_similar_selection(on) {
  $('#p_ai_similar_list').toggleClass('p-ai-similar-select', on);
  $('#p_ai_similar_rows .p-ai-similar-row').attr('data-selected', '0');
  // the line that names the destination keeps its place, so the modal keeps its height
  $('#p_ai_similar_merge_into').css('visibility', on ? 'visible' : 'hidden');
  $('#p_ai_similar_merge').toggle(on);
  $('#p_ai_similar_cancel').toggle(!on);
  p_ai_update_similar_merge();
}

// nothing to merge until a tag is selected
function p_ai_update_similar_merge() {
  const none = $('#p_ai_similar_rows .p-ai-similar-row[data-selected="1"]').length === 0;
  $('#p_ai_similar_merge').css({ opacity: none ? 0.5 : 1, 'pointer-events': none ? 'none' : '' });
}

// the selected tags go into the tag of the modal, with the merge of the core (pwg.tags.merge):
// their photos get that tag, then they are deleted
function p_ai_merge_similar_tags() {
  const selected = $('#p_ai_similar_rows .p-ai-similar-row[data-selected="1"]');
  if (!selected.length) return;

  const ids = selected.map(function() {
    return $(this).attr('data-id');
  }).get();
  // as HTML: the message of the core is set with .html()
  const names = selected.map(function() {
    return $('<span>').text($(this).find('.p-ai-similar-name').text()).html();
  }).get();
  const destination = p_ai_similar_tag;

  $('#p_ai_similar_merge').css({ opacity: 0.5, 'pointer-events': 'none' });

  $.ajax({
    url: 'ws.php?format=json&method=pwg.tags.merge',
    type: 'POST',
    dataType: 'json',
    data: { destination_tag_id: destination.id, merge_tag_id: ids, pwg_token: p_ai_pwg_token },
    success: function(res) {
      $('#p_ai_similar_modal').fadeOut();
      if (res.stat !== 'ok') {
        showError(res.message);
        return;
      }

      p_ai_apply_merge(res.result);
      if (typeof str_merged_into !== 'undefined') {
        showMessage(str_merged_into
          .replace('%s1', names.join(', '))
          .replace('%s2', $('<span>').text(destination.name).html()));
      }
    },
    error: function(e) {
      $('#p_ai_similar_modal').fadeOut();
      showError(e.responseJSON?.message ?? e.statusText);
    }
  });
}

// what the core does to its own page after one of its merges (mergeGroups in its tags.js): the
// merged tags leave the page and its list, the destination gets its new number of photos. Its
// mergeGroups is not called: it reads the names in the boxes, and a similar tag may be on
// another page of the list. The page is reloaded if the core no longer has what is used here
function p_ai_apply_merge(result) {
  if (typeof dataTags === 'undefined' || typeof updatePaginationMenu !== 'function'
    || typeof updateBadge !== 'function' || typeof updateSearchInfo !== 'function') {
    window.location.reload();
    return;
  }

  result.deleted_tag.forEach(function(id) {
    if (id == result.destination_tag) return;

    $('.tag-box[data-id=' + id + ']').remove();
    dataTags = dataTags.filter(function(tag) {
      return tag.id != id;
    });
  });

  const count = result.images_in_merged_tag.length;
  if (count > 0) {
    const box = $('.tag-box[data-id=' + result.destination_tag + ']');
    box.find('.dropdown-option.view, .dropdown-option.manage, .tag-dropdown-header i').show();
    if (typeof str_number_photos !== 'undefined') {
      box.find('.tag-dropdown-header i').html(str_number_photos.replace('%d', count));
    }

    const destination = dataTags.find(function(tag) {
      return tag.id == result.destination_tag;
    });
    if (destination) {
      destination.counter = count;
    }
  }

  updatePaginationMenu();
  updateBadge();
  updateSearchInfo();
}

$(function() {
  $('#p_ai_similar_close, #p_ai_similar_cancel').on('click', function() {
    $('#p_ai_similar_modal').fadeOut();
  });

  $('#p_ai_similar_select').on('change', function() {
    p_ai_set_similar_selection($(this).prop('checked'));
  });

  $('#p_ai_similar_rows').on('click', '.p-ai-similar-row', function() {
    if (!$('#p_ai_similar_select').prop('checked')) return;

    $(this).attr('data-selected', $(this).attr('data-selected') === '1' ? '0' : '1');
    p_ai_update_similar_merge();
  });

  $('#p_ai_similar_merge').on('click', p_ai_merge_similar_tags);
});

// the button that indexes the tags without embedding, next to "Add a tag". It follows the
// indexation, whoever started it, as the card of the overview does
$(function() {
  const add_tag = document.querySelector('.tag-header #add-tag');
  const button = $('#p_ai_index_tags');
  if (!add_tag || !button.length || typeof p_ai_tags === 'undefined') return;

  // the button comes from admin/template/tags.tpl
  button.insertAfter(add_tag).show();

  // the header of the core is one line that cuts what does not fit: with a button more, its
  // messages (orphan tags, done, error) go on a line of their own, under the buttons
  const header = add_tag.parentNode;
  const first_info = header.querySelector('.tag-info');
  if (first_info) {
    header.classList.add('p-ai-tag-header');
    $('#p_ai_tag_break').insertBefore(first_info);
  }

  let state = p_ai_tags.indexation;
  // the text of the message shown at the start of an indexation, while it is on the page
  let started_text = null;

  function render(new_state) {
    state = new_state;

    // the indexation is over: its start message goes, unless the core shows another one by now
    if (!state.in_progress && started_text !== null) {
      if ($('.info-message p').text() === started_text) {
        $('.info-message').hide();
      }
      started_text = null;
    }

    let label = p_ai_str_all_tags_indexed;
    let disabled = true;
    if (state.in_progress) {
      label = p_ai_str_indexation_in_progress;
    } else if (state.to_index > 0) {
      label = state.to_index === 1 ? p_ai_str_index_one_tag : p_ai_str_index_tags.replace('%d', state.to_index);
      disabled = false;
    }
    button.find('p').text(label);
    button.css({ opacity: disabled ? 0.5 : 1, 'pointer-events': disabled ? 'none' : '' });
  }

  button.on('click', function() {
    if (state.in_progress || state.to_index === 0) return;

    // shown in progress at once; back to the state before if the server refuses
    const before = state;
    render($.extend({}, before, { in_progress: true }));

    $.ajax({
      url: 'ws.php?format=json&method=pwg.ai.index_tags',
      type: 'POST',
      dataType: 'json',
      data: { pwg_token: p_ai_pwg_token },
      success: function(res) {
        if (res.stat === 'ok') {
          p_ai_realtime.refresh();
          showMessage(res.result.message || p_ai_str_indexation_started);
          started_text = $('.info-message p').text();
          return;
        }
        render(before);
        showError(res.message);
      },
      error: function(e) {
        render(before);
        showError(e.responseJSON?.message ?? e.statusText);
      }
    });
  });

  render(state);
  p_ai_realtime.subscribe({
    method: 'pwg.ai.tags_indexation',
    change: render,
    active: function(new_state) {
      return new_state.in_progress;
    },
  });

  // a tag added, renamed, merged or deleted changes how many are left to index
  $(document).ajaxSuccess(function(event, xhr, settings) {
    if (settings.url.indexOf('method=pwg.tags.') !== -1) {
      p_ai_realtime.refresh();
    }
  });
});
