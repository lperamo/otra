/// <reference path="./lightbox.ts" />

const FWK_DEBUG  = (function(w : Window, d : Document, u : undefined)
{
  'use strict';
  let bar : HTMLElement, barXS : HTMLElement;

  function toggle()
  {
    bar.classList.toggle('dbg-hide');
    barXS.classList.toggle('dbg-hide');
  }

  function hideProfiler() : void
  {
    /** @var Lib_LightboxInterface LIB_LIGHTBOX */
    window['LIB_LIGHTBOX'].exit.apply(d.getElementById('profiler'));
  }

  function clearLogs() : void
  {
    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function()
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        const SQLLogsSel = d.getElementById('dbg-sql-logs');
        SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
      }
    };

    xhr.open('GET', '/dbg/clearSQLLogs', true);
    xhr.send()
  }

  /**
   * Shows the actual SQL logs instead of the SQL logs that were showed when we opened the profiler.
   */
  function refreshLogs() : void
  {
    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function() : void
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        const SQLLogsSel = d.getElementById('dbg-sql-logs');
        SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
      }
    };

    xhr.open('GET', '/dbg/refreshSQLLogs', true);
    xhr.send()
  }

  function copySqlToClipBoard() : void
  {
    const elt = this.previousSibling.children[2];

    if (w.getSelection)
    {
      const selection = w.getSelection(),
            range = d.createRange();

      range.selectNodeContents(elt);
      selection.removeAllRanges();
      selection.addRange(range)
    }
  }

  /**
   * Initialize the events of the three buttons of the profiler :
   * 'Hide the profiler', 'Clear SQL logs' and 'Refresh SQL logs'/
   */
  function initEvents() : void
  {
    const btns : any = d.querySelectorAll('#profiler .lb-btn');

    for (let i : number = 3, len = btns.length; i < len; ++i)
    {
      btns[i].addEventListener('mouseup', copySqlToClipBoard)
    }

    d.getElementById('dbg-hide-profiler').addEventListener('mouseup', hideProfiler);
    d.getElementById('dbg-clear-sql-logs').addEventListener('mouseup', clearLogs);
    d.getElementById('dbg-refresh-sql-logs').addEventListener('mouseup', refreshLogs)
  }

  function initLightBox() : void
  {
    window['LIB_LIGHTBOX'].basic({url: '/dbg', callback: initEvents})
  }

  function runProfiler() : void
  {
    const profilerlightbox : HTMLElement = d.getElementById('profiler-light-box');

    // Takes into account that lightbox can already be loaded by the site
    if (null === profilerlightbox && u === window['LIB_LIGHTBOX'])
    {
      const s = d.createElement('script');
      s.src = '/lib/myLibs/core/js/lightbox.js';
      s.id = 'profiler-light-box';
      s.onload = initLightBox;

      d.body.appendChild(s)
    } else
      initLightBox();
  }

  /**
   * Shows error information to the screen
   *
   * @param {Event}  e    Event
   * @param {string} data Data to display
   */
  function postLog(e : Event, data : string) : void
  {
    try
    {
      console.log(e)
    } catch (e2)
    {
      alert(e)
    }

    d.getElementsByTagName('body')[0].innerHTML += '<div class="divError">' + data + '</div>'
  }

  w.onload = function() : void
  {
    bar = d.getElementById('dbg-bar');
    barXS = <HTMLElement> bar.nextSibling;
    const toggleElt : HTMLElement = d.getElementById('dbg--toggle'),
        toggleXSElt : HTMLElement = d.getElementById('dbg--toggle-small');

    toggleXSElt.onmouseup = toggleElt.onmouseup = toggle;
    d.getElementById('show-sql').addEventListener('mouseup', runProfiler)
  };

  const dummy : any = {postLog};

  return dummy;
})(window, document, undefined);