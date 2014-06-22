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
            container.insertAdjacentHTML('beforeend', '<div class="lightboxContainer"><div class="lightbox oldLightbox tcenter"><div class="alignLeft">' + xhr.responseText + '</div></div></div>');
            if (u !== callback) callback()
        }

        xhr.open('GET', '/dbg/clearSQLLogs', true), xhr.send()
    }

    function copySqlToClipBoard(elt){
        if (d.body.createTextRange) { // ms
            var range = d.body.createTextRange();
            range.moveToElementText(elt);
            range.select()
        } else if (w.getSelection) { // moz, opera, webkit
            console.log(elt);
            var selection = w.getSelection(),
                range = d.createRange();

            range.selectNodeContents(elt);
            selection.removeAllRanges();
            selection.addRange(range)
        }
    }

    function initEvents(){
        var btns = d.querySelectorAll('#profiler .lbBtn')
        for(var i = 1, len = btns.length; i< len; ++i) {
            btns[i].addEventListener('click', copySqlToClipBoard(btns[i].previousSibling.children[2].firstChild), false)
        }

        console.log(btns);
        d.getElementById('dbgHideProfiler').addEventListener('click', hideProfiler, false);
        d.getElementById('dbgClearSQLLogs').addEventListener('click', clearLogs, false)

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
            (null === d.getElementById('profiler')) ? w.lightBox(d.body, '/dbg', initEvents) : hideProfiler
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
