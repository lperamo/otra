const FWK_DEBUG = (function (w : Window, d : Document, u : undefined)
{
  'use strict';
  let bar : HTMLElement, barXS : HTMLElement;

  function toggle()
  {
    'none' === bar.style.display
      ? (bar.style.display = 'block', barXS.style.display = 'none')
      : (bar.style.display = 'none', barXS.style.display = 'block')
  }

  function hideProfiler() : void
  {
    LIB_LIGHTBOX.exit.apply(d.getElementById('profiler'));
  }

  function clearLogs() : void
  {
    let xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function ()
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        let SQLLogsSel = d.getElementById('dbg-sql-logs');
        SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
      }
    }

    xhr.open('GET', '/dbg/clearSQLLogs', true), xhr.send()
  }

  function refreshLogs() : void
  {
    let xhr = new XMLHttpRequest;

    xhr.onreadystatechange = function () : void
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        let SQLLogsSel = d.getElementById('dbg-sql-logs');
        SQLLogsSel.innerHTML = xhr.responseText + '</div></div></div>';
      }
    }

    xhr.open('GET', '/dbg/refreshSQLLogs', true), xhr.send()
  }

  function copySqlToClipBoard() : void
  {
    let elt = this.previousSibling.children[2]

    if (w.getSelection)
    {
      let selection = w.getSelection(),
          range = d.createRange();

      range.selectNodeContents(elt);
      selection.removeAllRanges();
      selection.addRange(range)
    }
  }

  function initEvents() : void
  {
    let btns : any = d.querySelectorAll('#profiler .lb-btn');

    for (let i : number = 3, len = btns.length; i < len; ++i)
    {
      btns[i].addEventListener('click', copySqlToClipBoard)
    }

    d.getElementById('dbg-hide-profiler').addEventListener('click', hideProfiler);
    d.getElementById('dbg-clear-sql-logs').addEventListener('click', clearLogs)
    d.getElementById('dbg-refresh-sql-logs').addEventListener('click', refreshLogs)
  }

  function initLightBox() : void
  {
    LIB_LIGHTBOX.basic({url: '/dbg', callback: initEvents})
  }

  function runProfiler() : void
  {
    let profilerlightbox : HTMLElement = d.getElementById('profiler-light-box')

    // Takes into account that lightbox can already be loaded by the site
    if (null === profilerlightbox && u === LIB_LIGHTBOX)
    {
      let s = d.createElement('script');
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

  w.onload = function () : void
  {
    bar = d.getElementById('dbg-bar');
    barXS = <HTMLElement> bar.nextSibling;
    let toggleElt : HTMLElement = d.getElementById('toggle'),
        toggleXSElt : HTMLElement = d.getElementById('toggle-small');

    toggleXSElt.onmouseup = toggleElt.onmouseup = toggle;
    d.getElementById('show-sql').addEventListener('click', runProfiler)
  }

  let dummy : any = {postLog};

  return dummy
})(window, document, undefined)
