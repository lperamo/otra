const LIB_NOTIF = (function() : {ERROR : string, INFO : string, WARNING : string, run : any}
{
  'use strict';
  let ERROR : string = 'orange-notif',
      INFO : string = 'vert-notif',
      WARNING : string = 'jaune-notif',
      exists : boolean  = false;

  /**
   * Decrease opacity at tick time and remove the notification at the end.
   *
   * @param {HTMLElement} el       The notification DOM element.
   * @param {Date}    last     Last time (useful because of the recursion)
   * @param {number}  duration How many time the notification has to last
   */
  function tick(el : HTMLElement, last : Date, duration : number)
  {
    let temp : Date = new Date();
    el.style.opacity -= ((<any> temp) - (<any> last)) / duration;
    last = temp;

    if (0 < parseFloat(el.style.opacity))
      setTimeout(tick, 1, el, last, duration);
    else if (null !== el.parentNode) // fixes some bugs
    {
      el.parentNode.removeChild(el);
      exists = false;
    }
  }

  /**
   * Shows a notification after a html content
   *
   * @param {HTMLElement} selector The html content selector
   * @param {string}      text     The text to show in the notification
   * @param {string}      type     ERROR, WARNING, INFO ...
   * @param {string}      cssClass The css additional class to apply (optional)
   * @param {number}      duration Duration in milliseconds for the fade effect
   */
  function run(
    selector : HTMLElement,
    text : string,
    type : string,
    cssClass : string = '',
    duration : number = 1E4
  )
  {
    let next : HTMLElement = <HTMLElement> selector.nextElementSibling;

    // If a notification exist already then it removes the previous one and creates another
    if (true === exists)
      next.parentNode.removeChild(next);

    exists = true;
    selector.insertAdjacentHTML(
      'afterend',
      '<div class="notif-wrapper fl"><div class="notification ' + cssClass + ' ' + type + '">' + text + '</div></div>'
    );

    next = <HTMLElement> selector.nextElementSibling;
    let last = new Date();

    next.style.opacity = '1';
    tick(next, last, duration)
  }

  let dummy : {ERROR : string, INFO : string, WARNING : string, run : any} = { ERROR, INFO, WARNING, run};

  return dummy
  // Why the following code doesn't work ?!
  // return
  // ({
  //   ERROR,
  //   INFO,
  //   WARNING,
  //   run
  // })
})();