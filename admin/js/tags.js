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
      span.title = p_ai_tags.str_ai;
      span.setAttribute('aria-label', p_ai_tags.str_ai);
      span.style.marginRight = '6px';
      name.parentNode.insertBefore(span, name);

      // the options of the core for its .tiptip (admin footer.tpl)
      $(span).tipTip({ delay: 0, fadeIn: 200, fadeOut: 200 });
    } else if (!is_ai && robot) {
      // a recycled box keeps its children: the robot of the previous tag goes
      $(robot).remove();
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
});

// the button that indexes the tags without embedding, next to "Add a tag". It follows the
// indexation, whoever started it, as the card of the overview does
$(function() {
  const add_tag = document.querySelector('.tag-header #add-tag');
  if (!add_tag || typeof p_ai_tags === 'undefined') return;

  // built as the "Add a tag" button of the core, and hidden with it in selection mode
  const button = $('<label class="p-ai-tag-action head-button-2 icon-robot-head not-in-selection-mode"><p></p></label>');
  $(add_tag).after(button);

  // the header of the core is one line that cuts what does not fit: with a button more, its
  // messages (orphan tags, done, error) go on a line of their own, under the buttons
  const header = add_tag.parentNode;
  const first_info = header.querySelector('.tag-info');
  if (first_info) {
    header.classList.add('p-ai-tag-header');
    $('<div class="p-ai-tag-break not-in-selection-mode"></div>').insertBefore(first_info);
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

    let label = p_ai_tags.str_all_tags_indexed;
    let disabled = true;
    if (state.in_progress) {
      label = p_ai_tags.str_indexation_in_progress;
    } else if (state.to_index > 0) {
      label = state.to_index === 1 ? p_ai_tags.str_index_one_tag : p_ai_tags.str_index_tags.replace('%d', state.to_index);
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
      data: { pwg_token: p_ai_tags.pwg_token },
      success: function(res) {
        if (res.stat === 'ok') {
          p_ai_realtime.refresh();
          showMessage(res.result.message || p_ai_tags.str_indexation_started);
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
