$(function() {
  if (typeof p_ai_analysis === 'undefined') return;

  p_ai_set_analysis_state(p_ai_analysis.state, p_ai_analysis.date);
  p_ai_add_analyze_action();
});

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
      modal.fadeOut();
      pwgToaster({
        text: res.result.status === 'unsent' ? p_ai_str_analyze_unsent : p_ai_str_analyze_sent,
        icon: 'success',
      });
    },
    error: function(e) {
      button.removeClass(sending);
      pwgToaster({ text: e.responseJSON?.message ?? e.statusText, icon: 'error' });
    }
  });
}

function p_ai_set_analysis_state(state, date) {
  // the modal warns that a new request costs a credit again
  $('#p_ai_analyze_pending').toggleClass('hidden', state !== 'pending');

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
