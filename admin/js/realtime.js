// Follows what changes on the server, for the pages of the admin. A page subscribes to a read ws
// method and is told when its answer changes:
//
//   p_ai_realtime.subscribe({
//     method: 'pwg.ai.analysis_state',
//     params: { image_id: 145 },
//     change: function(result) {},                               // first answer, then each change
//     active: function(result) { return result.state === 'pending'; },  // optional: waiting for something
//   });
//   p_ai_realtime.refresh();                                     // ask now, after an action of the page
//
// All the subscriptions of the page are asked at once (pwg.ai.status): often while one of them is
// active, rarely otherwise (p_ai_realtime_delays), and never while the tab is hidden, or left
// alone with nothing awaited.
const p_ai_realtime = (function() {
  const subscriptions = [];
  let timer = null;
  let in_flight = false;
  let again = false;
  let stopped = false;
  let last_activity = Date.now();

  function key_of(subscription) {
    return subscription.method + ' ' + JSON.stringify(subscription.params);
  }

  // read when needed: the template that sets the delays may come after this file
  function delays() {
    return window.p_ai_realtime_delays || { active: 5000, idle: 30000, sleep: 300000 };
  }

  function is_active() {
    return subscriptions.some(function(subscription) {
      return subscription.is_active;
    });
  }

  function delay() {
    return is_active() ? delays().active : delays().idle;
  }

  // nobody at the keyboard and nothing awaited: the page stops asking until someone comes back
  function is_asleep() {
    return !is_active() && Date.now() - last_activity > delays().sleep;
  }

  function schedule(ms) {
    clearTimeout(timer);
    timer = setTimeout(poll, ms);
  }

  function subscribe(options) {
    subscriptions.push({
      method: options.method,
      params: options.params || {},
      change: options.change,
      active: options.active || null,
      last: null,
      is_active: false,
    });
    refresh();
  }

  // several calls in a row make one request
  function refresh() {
    if (in_flight) {
      again = true;
      return;
    }
    schedule(0);
  }

  function receive(keys, answers) {
    subscriptions.forEach(function(subscription) {
      const answer = answers[keys.indexOf(key_of(subscription))];
      if (!answer || answer.stat !== 'ok') return;

      const serialized = JSON.stringify(answer.result);
      if (serialized === subscription.last) return;

      subscription.last = serialized;
      subscription.is_active = subscription.active ? !!subscription.active(answer.result) : false;

      // a page that fails must not stop the others
      try {
        subscription.change(answer.result);
      } catch (e) {
        console.error(e);
      }
    });
  }

  function poll() {
    timer = null;
    // a hidden tab and a page asleep ask nothing: coming back starts again
    if (stopped || !subscriptions.length || document.hidden || is_asleep()) return;

    // one call per distinct method and params
    const keys = [];
    const calls = [];
    subscriptions.forEach(function(subscription) {
      const key = key_of(subscription);
      if (keys.indexOf(key) !== -1) return;
      keys.push(key);
      calls.push({ method: subscription.method, params: subscription.params });
    });

    in_flight = true;
    $.ajax({
      url: 'ws.php?format=json&method=pwg.ai.status',
      type: 'GET',
      dataType: 'json',
      data: { calls: JSON.stringify(calls) },
      success: function(res) {
        if (res.stat === 'ok') {
          receive(keys, res.result);
        }
      },
      error: function(e) {
        // the session is over: asking again would only fail
        if (e.status === 401 || e.status === 403) {
          stopped = true;
        }
      },
      complete: function() {
        in_flight = false;
        if (stopped) return;

        if (again) {
          again = false;
          schedule(0);
          return;
        }
        schedule(delay());
      }
    });
  }

  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      refresh();
    }
  });

  ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function(name) {
    document.addEventListener(name, function() {
      const was_asleep = is_asleep();
      last_activity = Date.now();
      if (was_asleep) {
        refresh();
      }
    }, { passive: true });
  });

  return { subscribe: subscribe, refresh: refresh };
})();
