$(function() {
  if (typeof p_ai_analysis === 'undefined') return;

  p_ai_set_analysis_state(p_ai_analysis.state, p_ai_analysis.date);
  p_ai_add_analyze_action();
  p_ai_add_analysis_done();

  // the robot follows the analysis, whoever asked it
  p_ai_realtime.subscribe({
    method: 'pwg.ai.analysis_state',
    params: { image_id: p_ai_analysis.image_id },
    change: function(result) {
      p_ai_set_analysis_state(result.state, result.date);

      // an analysis ended after this page was loaded: its tags and texts are out of date
      if (result.state === 'completed' && result.date !== p_ai_analysis.date) {
        p_ai_analysis_done_date = result.date;
        $('#p_ai_analysis_done').show();
      }
    },
    active: function(result) {
      return result.state === 'pending';
    },
  });
});

// next to the save button, once an analysis ended: refresh the page, or, when the form was
// changed, show the result in the page and keep these changes. Saving the form as it is would
// remove the tags the AI just added, since the form still holds the tags of before
function p_ai_add_analysis_done() {
  const done = $('#p_ai_analysis_done');
  const form = $('#pictureModify');
  if (!done.length || !form.length) return;

  form.find('.savebar-footer-end').prepend(done);

  // a change after a focus, as the core does to warn before leaving the page
  let interacted = false;
  form.on('focusin', ':input', function() {
    interacted = true;
  });
  form.on('change', ':input', function() {
    if (interacted) {
      $('#p_ai_analysis_keep').show();
    }
  });

  $('#p_ai_analysis_refresh').on('click', function(event) {
    event.preventDefault();
    window.location.reload();
  });
  $('#p_ai_analysis_refresh_keep').on('click', function(event) {
    event.preventDefault();
    p_ai_show_analysis_result(form);
  });
}

// the date of the analysis the page is asked to show
let p_ai_analysis_done_date = null;

// a refresh without leaving the page: what the AI changed is shown (its description, the text it
// found, the tags it added), and what was typed in the form stays, not saved yet. The core
// method gives the whole row of the photo, the columns of the plugin with it, and its tags
function p_ai_show_analysis_result(form) {
  $.ajax({
    url: 'ws.php?format=json&method=pwg.images.getInfo',
    type: 'GET',
    dataType: 'json',
    data: { image_id: p_ai_analysis.image_id },
    success: function(res) {
      if (res.stat !== 'ok') {
        pwgToaster({ text: res.message, icon: 'error' });
        return;
      }

      // the text found in the photo is stored as a JSON list of lines
      let ocr = [];
      try {
        ocr = JSON.parse(res.result.ocr) || [];
      } catch (e) {}

      $('#p_ai_description').val(res.result.ai_description || '');
      p_ai_set_ocr(ocr);
      p_ai_add_tags(form, res.result.tags || []);

      // the page shows this analysis now
      p_ai_analysis.date = p_ai_analysis_done_date;
      $('#p_ai_analysis_done').hide();
    },
    error: function(e) {
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

// the tags the photo got since the page was loaded join those of the form. A tag the form
// already knew is left as the form has it, kept or removed
let p_ai_known_tags = null;

function p_ai_add_tags(form, tags) {
  const select = form.find('[data-selectize=tags]');
  if (!select.length || !select[0].selectize) return;

  if (p_ai_known_tags === null) {
    p_ai_known_tags = (select.data('value') || []).map(function(tag) {
      return String(tag.id);
    });
  }

  tags.forEach(function(tag) {
    // the tags widget of the core names an existing tag ~~id~~
    const id = '~~' + tag.id + '~~';
    if (p_ai_known_tags.indexOf(id) !== -1) return;

    p_ai_known_tags.push(id);
    select[0].selectize.addOption({ id: id, name: tag.name });
    select[0].selectize.addItem(id);
  });
}

function p_ai_add_analyze_action() {
  const actions = document.querySelector('.picture-preview-actions');
  const modal = $('#p_ai_analyze_modal');
  if (!actions || !modal.length) return;

  const robot = document.createElement('a');
  robot.className = 'icon-robot-head cursor-pointer';
  robot.title = p_ai_str_analyze;
  actions.prepend(robot);

  $(robot).on('click', function() {
    modal.fadeIn();
  });
  $('#p_ai_analyze_close, #p_ai_analyze_cancel').on('click', function() {
    modal.fadeOut();
  });
  $('#p_ai_analyze_confirm').on('click', function() {
    p_ai_analyze_image(modal, $(this));
  });
}

function p_ai_analyze_image(modal, button) {
  const sending = 'opacity-50 pointer-events-none';
  button.addClass(sending);

  $.ajax({
    url: 'ws.php?format=json&method=pwg.ai.analyze_image',
    type: 'POST',
    dataType: 'json',
    data: {
      image_id: p_ai_analysis.image_id,
      caption: modal.find('[name=p_ai_caption]').is(':checked'),
      tagging: modal.find('[name=p_ai_tagging]').is(':checked'),
      ocr: modal.find('[name=p_ai_ocr]').is(':checked'),
      embedding: modal.find('[name=p_ai_embedding]').is(':checked'),
      pwg_token: p_ai_pwg_token,
    },
    success: function(res) {
      button.removeClass(sending);
      if (res.stat !== 'ok') {
        pwgToaster({ text: res.message, icon: 'error' });
        return;
      }
      p_ai_set_analysis_state('pending');
      p_ai_realtime.refresh();
      modal.fadeOut();
      pwgToaster({
        text: res.result.status === 'unsent' ? p_ai_str_analyze_unsent : p_ai_str_analyze_sent,
        icon: 'success',
      });
    },
    error: function(e) {
      button.removeClass(sending);
      // refused: an analysis was asked meanwhile, from another tab or by another admin
      if (e.status === 409) {
        p_ai_set_analysis_state('pending');
        p_ai_realtime.refresh();
      }
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

function p_ai_set_analysis_state(state, date) {
  // one analysis at a time per photo: while one is pending, the modal says so and cannot confirm
  $('#p_ai_analyze_pending').toggleClass('hidden', state !== 'pending');
  $('#p_ai_analyze_confirm').toggleClass('opacity-50 pointer-events-none', state === 'pending');

  const image_id = document.querySelector('h1 .image-id');
  if (!image_id) return;

  let title = state === 'pending' ? p_ai_str_analysis_pending : p_ai_str_analysis_none;
  if (state === 'completed') {
    title = p_ai_str_analysis_completed.replace('%s', date);
  }

  // tipTip keeps the title it read when bound: a new robot for each state
  $('.p-ai-analysis-robot').remove();

  const robot = document.createElement('span');
  robot.className = 'p-ai-analysis-robot icon-robot-head ml-2 p-ai-analysis-' + state;
  robot.title = title;
  robot.setAttribute('aria-label', title);
  image_id.after(robot);

  // the options of the core for its .tiptip (admin footer.tpl)
  $(robot).tipTip({ delay: 0, fadeIn: 200, fadeOut: 200 });
}
