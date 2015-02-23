var notif = (function()
{
  'use strict';
  var ERROR = 'orangeNotif',
      INFO = 'vertNotif',
      WARNING = 'jauneNotif';

  function tick(el, last, duration)
  {
    var temp = new Date();
    el.style.opacity -= (temp - last) / duration;
    last = temp;

    if (el.style.opacity > 0)
      setTimeout(tick, 1, el, last, duration)
    else
    {
      if(null !== el.parentNode) // fixes some bugs
      {
        el.parentNode.removeChild(el);
        window.notifExists = false
      }
    }
  }

  function fadeOut(el, duration)
  {
    el.style.opacity = 1;
    var last = new Date();
    tick(el, last, duration)
  }

  /**
   * Shows a notification after a html content
   *
   * @param selector The html content selector
   * @param texte    The text to show in the notification
   * @param type     ERROR, WARNING, INFO ...
   * @param cssClass The css additional class to apply (optional)
   * @param duree    Duration in milliseconds for the fade effect
   */
  function run(selector, texte, type, cssClass, duree)
  {
    var cssClass = cssClass || '',
        duree = duree || 10000,
        next = selector.nextElementSibling;

    // If a notification exist already then it removes the previous one and creates another
    if(true === window.notifExists)
      next.parentNode.removeChild(next);

    window.notifExists = true;
    selector.insertAdjacentHTML('afterend', '<div class="notifWrapper fl"><div class="notification ' + cssClass + ' ' + type + '">' + texte + '</div></div>');
    var next = selector.nextElementSibling;

    fadeOut(next, duree)
  }

  var dummy = {
    ERROR: ERROR,
    'INFO' : INFO,
    'WARNING' : WARNING,
    run : run
  };

  return dummy;
  // Why the fucking following code doesn't work !!
  // return
  // ({
  //   ERROR: ERROR,
  //   'INFO' : INFO,
  //   'WARNING' : WARNING,
  //   'run' : run
  // })
})()
