var notif = (function()
{
  'use strict';
  var ERROR = 'orangeNotif',
      INFO = 'vertNotif',
      WARNING = 'jauneNotif';

  /**
   * Decrease opacity at tick time and remove the notification at the end.
   *
   * @param el       The notification DOM element.
   * @param last     Last time (useful because of the recursion)
   * @param duration How many time the notification has to last
   */
  function tick(el, last, duration)
  {
    var temp = new Date;
    el.style.opacity -= (temp - last) / duration;
    last = temp;

    0 < el.style.opacity
    ? setTimeout(tick, 1, el, last, duration)
    : null !== el.parentNode && // fixes some bugs
      (
        el.parentNode.removeChild(el),
        window.notifExists = false
      )
  }

  /**
   * Shows a notification after a html content
   *
   * @param selector The html content selector
   * @param texte    The text to show in the notification
   * @param type     ERROR, WARNING, INFO ...
   * @param cssClass The css additional class to apply (optional)
   * @param duration Duration in milliseconds for the fade effect
   */
  function run(selector, texte, type, cssClass, duration)
  {
    var cssClass = cssClass || '',
        duree = duree || 1E4,
        next = selector.nextElementSibling;

    // If a notification exist already then it removes the previous one and creates another
    if(true === window.notifExists)
      next.parentNode.removeChild(next);

    window.notifExists = true;
    selector.insertAdjacentHTML('afterend', '<div class="notifWrapper fl"><div class="notification ' + cssClass + ' ' + type + '">' + texte + '</div></div>');

    var next = selector.nextElementSibling,
      last = new Date;

    next.style.opacity = 1;
    tick(next, last, duration)
  }

  var dummy = {
    ERROR: ERROR,
    'INFO' : INFO,
    'WARNING' : WARNING,
    run : run
  };

  return dummy
  // Why the fucking following code doesn't work !!
  // return
  // ({
  //   ERROR: ERROR,
  //   'INFO' : INFO,
  //   'WARNING' : WARNING,
  //   'run' : run
  // })
})()