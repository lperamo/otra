(function (document) {
    'use strict';
    function toggleCfgSection() {
        var next = this;
        while (next = next.nextElementSibling) {
            if (next.classList.contains('cfg-line') === false)
                break;
            next.classList.contains('hidden')
                ? next.classList.remove('hidden')
                : next.classList.add('hidden');
        }
    }
    function delegateMouseupEvents(event) {
        if (false === toolsBase.matches(event.target, '.cfg-section'))
            return;
        toggleCfgSection.call(event.target);
    }
    function pageReady() {
        document.getElementById('main-cfg-div').addEventListener('mouseup', delegateMouseupEvents);
    }
    'loading' !== document.readyState
        ? pageReady()
        : document.addEventListener('DOMContentLoaded', pageReady);
})(document);
//# sourceMappingURL=general.js.map