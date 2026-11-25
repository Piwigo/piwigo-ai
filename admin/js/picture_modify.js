$(function() {
  if (typeof p_ai_analysis === 'undefined') return;

  p_ai_set_analysis_state(p_ai_analysis.state, p_ai_analysis.date);
});

function p_ai_set_analysis_state(state, date) {
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
