(function(w, d, u){
    'use strict';

    function toggle()
    {
        'none' === bar.style.display
            ? (bar.style.display = 'block', barXS.style.display = 'none')
            : (bar.style.display = 'none', barXS.style.display = 'block')
    }

    function hideProfiler(){ d.body.removeChild(d.getElementById('profiler').parentNode.parentNode.parentNode) }

    function clearLogs(){
        var xhr = new XMLHttpRequest;

        xhr.onreadystatechange = function() {
            if (4 === xhr.readyState && 200 === xhr.status)
            {
                var SQLLogsSel = d.getElementById('dbgSQLLogs');
                d.getElementById('dbgSQLLogs').innerHTML = xhr.responseText + '</div></div></div>';
            }
        }

        xhr.open('GET', '/dbg/clearSQLLogs', true), xhr.send()
    }

    function refreshLogs(){
        var xhr = new XMLHttpRequest;

        xhr.onreadystatechange = function() {
            if (4 === xhr.readyState && 200 === xhr.status)
            {
                var SQLLogsSel = d.getElementById('dbgSQLLogs');
                d.getElementById('dbgSQLLogs').innerHTML = xhr.responseText + '</div></div></div>';
            }
        }

        xhr.open('GET', '/dbg/refreshSQLLogs', true), xhr.send()
    }

    function copySqlToClipBoard(){
        var elt = this.previousSibling.children[2]

        if (w.getSelection)
        {
            var selection = w.getSelection(),
                range = d.createRange();

            range.selectNodeContents(elt);
            selection.removeAllRanges();
            selection.addRange(range)
        }
    }

    function initEvents(){
        var btns = d.querySelectorAll('#profiler .lbBtn');

        for(var i = 3, len = btns.length; i< len; ++i) {
            btns[i].addEventListener('click', copySqlToClipBoard, false)
        }

        d.getElementById('dbgHideProfiler').addEventListener('click', hideProfiler, false);
        d.getElementById('dbgClearSQLLogs').addEventListener('click', clearLogs, false)
        d.getElementById('dbgRefreshSQLLogs').addEventListener('click', refreshLogs, false)
    }

    function runProfiler()
    {
        var profilerLightBox = d.getElementById('profilerLightBox')
        if(null === profilerLightBox)
        {
            if(u === w.lightbox)
            {
                var s = d.createElement('script');
                s.src = '/lib/myLibs/core/js/lightbox.js';
                s.id = 'profilerLightBox';
                s.onload = function(){ w.lightBox(d.body, '/dbg', initEvents) };

                d.body.appendChild(s)
            }
        }else
            w.lightBox(d.body, '/dbg', initEvents)
    }

    w.debug =
    {
        postLog: function(e, data)
        {
            try { console.log(e) } catch (e2) { alert(e) }
            d.getElementsByTagName('body')[0].innerHTML += '<div class="divError">' + data + '</div>'
        }
    }

    w.onload = function()
    {
        var bar = d.getElementById('dbgBar'),
            barXS = bar.nextSibling,
            toggleElt = d.getElementById('toggle'),
            toggleXSElt = d.getElementById('toggleSmall');

        toggleXSElt.onmouseup = toggleElt.onmouseup = toggle;
        d.getElementById('showSQL').addEventListener('click', runProfiler, false)
    }
})(window, document)
