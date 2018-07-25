var FWK_DEBUG = (function (w, d, u) {
    'use strict';
    var bar, barXS;
    function toggle() {
        'none' === bar.style.display
            ? (bar.style.display = 'block', barXS.style.display = 'none')
            : (bar.style.display = 'none', barXS.style.display = 'block');
    }
    function hideProfiler() {
        LIB_LIGHTBOX.exit.apply(d.getElementById('profiler'));
    }
    function clearLogs() {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (4 === xhr.readyState && 200 === xhr.status) {
                var SQLLogsSel = d.getElementById('dbg-sql-logs');
                SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
            }
        };
        xhr.open('GET', '/dbg/clearSQLLogs', true), xhr.send();
    }
    function refreshLogs() {
        var xhr = new XMLHttpRequest;
        xhr.onreadystatechange = function () {
            if (4 === xhr.readyState && 200 === xhr.status) {
                var SQLLogsSel = d.getElementById('dbg-sql-logs');
                SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
            }
        };
        xhr.open('GET', '/dbg/refreshSQLLogs', true), xhr.send();
    }
    function copySqlToClipBoard() {
        var elt = this.previousSibling.children[2];
        if (w.getSelection) {
            var selection = w.getSelection(), range = d.createRange();
            range.selectNodeContents(elt);
            selection.removeAllRanges();
            selection.addRange(range);
        }
    }
    function initEvents() {
        var btns = d.querySelectorAll('#profiler .lb-btn');
        for (var i = 3, len = btns.length; i < len; ++i) {
            btns[i].addEventListener('click', copySqlToClipBoard);
        }
        d.getElementById('dbg-hide-profiler').addEventListener('click', hideProfiler);
        d.getElementById('dbg-clear-sql-logs').addEventListener('click', clearLogs);
        d.getElementById('dbg-refresh-sql-logs').addEventListener('click', refreshLogs);
    }
    function initLightBox() {
        LIB_LIGHTBOX.basic({ url: '/dbg', callback: initEvents });
    }
    function runProfiler() {
        var profilerlightbox = d.getElementById('profiler-light-box');
        // Takes into account that lightbox can already be loaded by the site
        if (null === profilerlightbox && u === LIB_LIGHTBOX) {
            var s = d.createElement('script');
            s.src = '/lib/myLibs/core/js/lightbox.js';
            s.id = 'profiler-light-box';
            s.onload = initLightBox;
            d.body.appendChild(s);
        }
        else
            initLightBox();
    }
    /**
     * Shows error information to the screen
     *
     * @param {Event}  e    Event
     * @param {string} data Data to display
     */
    function postLog(e, data) {
        try {
            console.log(e);
        }
        catch (e2) {
            alert(e);
        }
        d.getElementsByTagName('body')[0].innerHTML += '<div class="divError">' + data + '</div>';
    }
    w.onload = function () {
        bar = d.getElementById('dbg-bar');
        barXS = bar.nextSibling;
        var toggleElt = d.getElementById('toggle'), toggleXSElt = d.getElementById('toggle-small');
        toggleXSElt.onmouseup = toggleElt.onmouseup = toggle;
        d.getElementById('show-sql').addEventListener('click', runProfiler);
    };
    var dummy = { postLog: postLog };
    return dummy;
})(window, document, undefined);
//# sourceMappingURL=debug_bar.js.map