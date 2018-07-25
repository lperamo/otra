var LIB_NOTIF = (function () {
    'use strict';
    var ERROR = 'orange-notif', INFO = 'vert-notif', WARNING = 'jaune-notif', exists = false;
    /**
     * Decrease opacity at tick time and remove the notification at the end.
     *
     * @param {HTMLElement} el       The notification DOM element.
     * @param {Date}    last     Last time (useful because of the recursion)
     * @param {number}  duration How many time the notification has to last
     */
    function tick(el, last, duration) {
        var temp = new Date();
        el.style.opacity -= (temp - last) / duration;
        last = temp;
        if (0 < parseFloat(el.style.opacity))
            setTimeout(tick, 1, el, last, duration);
        else if (null !== el.parentNode) {
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
    function run(selector, text, type, cssClass, duration) {
        if (cssClass === void 0) { cssClass = ''; }
        if (duration === void 0) { duration = 1E4; }
        var next = selector.nextElementSibling;
        // If a notification exist already then it removes the previous one and creates another
        if (true === exists)
            next.parentNode.removeChild(next);
        exists = true;
        selector.insertAdjacentHTML('afterend', '<div class="notif-wrapper fl"><div class="notification ' + cssClass + ' ' + type + '">' + text + '</div></div>');
        next = selector.nextElementSibling;
        var last = new Date();
        next.style.opacity = '1';
        tick(next, last, duration);
    }
    var dummy = { ERROR: ERROR, INFO: INFO, WARNING: WARNING, run: run };
    return dummy;
    // Why the following code doesn't work ?!
    // return
    // ({
    //   ERROR,
    //   INFO,
    //   WARNING,
    //   run
    // })
})();
//# sourceMappingURL=notifications.js.map