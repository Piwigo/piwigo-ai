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
    } else if (!is_ai && robot) {
      // a recycled box keeps its children: the robot of the previous tag goes
      robot.remove();
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
