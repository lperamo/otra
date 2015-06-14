window.debug = (function (w, d, u)
{
  'use strict';
  var bar, barXS;

  function toggle()
  {
    'none' === bar.style.display
      ? (bar.style.display = 'block', barXS.style.display = 'none')
      : (bar.style.display = 'none', barXS.style.display = 'block')
  }

  function hideProfiler()
  {
    lightbox.exit.apply(d.getElementById('profiler'));
  }

  function clearLogs()
  {
    var xhr = new XMLHttpRequest;

    xhr.onreadystatechange = function ()
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        var SQLLogsSel = d.getElementById('dbgSQLLogs');
        d.getElementById('dbgSQLLogs').innerHTML = xhr.responseText + '</div></div></div>';
      }
    }

    xhr.open('GET', '/dbg/clearSQLLogs', true), xhr.send()
  }

  function refreshLogs()
  {
    var xhr = new XMLHttpRequest;

    xhr.onreadystatechange = function ()
    {
      if (4 === xhr.readyState && 200 === xhr.status)
      {
        var SQLLogsSel = d.getElementById('dbgSQLLogs');
        d.getElementById('dbgSQLLogs').innerHTML = xhr.responseText + '</div></div></div>';
      }
    }

    xhr.open('GET', '/dbg/refreshSQLLogs', true), xhr.send()
  }

  function copySqlToClipBoard()
  {
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

  function initEvents()
  {
    var btns = d.querySelectorAll('#profiler .lbBtn');

    for (var i = 3, len = btns.length; i < len; ++i)
    {
      btns[i].addEventListener('click', copySqlToClipBoard)
    }

    d.getElementById('dbgHideProfiler').addEventListener('click', hideProfiler);
    d.getElementById('dbgClearSQLLogs').addEventListener('click', clearLogs)
    d.getElementById('dbgRefreshSQLLogs').addEventListener('click', refreshLogs)
  }

  function runProfiler()
  {
    var profilerlightbox = d.getElementById('profilerlightbox')

    // Takes into account that lightbox can already be loaded by the site
    if (null === profilerlightbox && u === lightbox)
    {
      var s = d.createElement('script');
      s.src = '/lib/myLibs/core/js/lightbox.js';
      s.id = 'profilerlightbox';
      s.onload = function ()
      {
        lightbox.basic({url: '/dbg', callback: initEvents})
      };

      d.body.appendChild(s)
    } else
    {
      lightbox.basic({url: '/dbg', callback: initEvents})
    }
  }

  /**
   * Shows error information to the screen
   *
   * @param {Event}  e    Event
   * @param {string} data Data to display
   */
  function postLog(e, data)
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

  w.onload = function ()
  {
    bar = d.getElementById('dbgBar'),
    barXS = bar.nextSibling;
    var toggleElt = d.getElementById('toggle'),
        toggleXSElt = d.getElementById('toggleSmall');

    toggleXSElt.onmouseup = toggleElt.onmouseup = toggle;
    d.getElementById('showSQL').addEventListener('click', runProfiler)
  }

  var dummy = {postLog: postLog};

  return dummy
})(window, document, void 0)
