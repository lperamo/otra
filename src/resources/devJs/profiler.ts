/// <reference path="./lightbox.ts" />

const FWK_DEBUG = (function(w : Window, d : Document)
{
  'use strict';
  const DONE = 4;

  /**
   * Calls the backend to update the logs by clearing or refreshing them
   *
   * @param {string} urlChunk
   * @param {Event}  event
   */
  function updateLogs(urlChunk: string, event: Event) : void
  {
    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = updateLogsCallback;
    xhr.open('GET', '/dbg/' + urlChunk + 'SQLLogs', true);
    xhr.send()
  }

  /**
   * Updates the logs by clearing or refreshing them
   */
  function updateLogsCallback(this :XMLHttpRequest) : void
  {
    if (DONE === this.readyState && 200 === this.status)
      d.getElementById('profiler--sql--logs').innerHTML = this.responseText + '</div></div>';
  }

  function copySqlToClipBoard() : void
  {
    const elt = this.previousElementSibling.children[2];

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
   * Shows error information to the screen
   *
   * @param {string} data  Data to display
   * @param {Event}  event Event
   */
  function postLog(data: string, event: Event) : void
  {
    try
    {
      console.log(event)
    } catch (exception)
    {
      alert(event)
    }

    d.getElementsByTagName('body')[0].innerHTML += '<div class="div-error">' + data + '</div>'
  }

  function copyFileAndLine():void
  {
    let  elt;
    if (this.classList.contains('profiler--sql-logs--element--file'))
    {
      elt = this;
    } else {
      elt = this.previousElementSibling;
    }

    if (w.getSelection)
    {
      const selection = w.getSelection(),
        range = d.createRange();

      range.setStart(elt,0);
      range.setEnd(elt.nextElementSibling,1);
      selection.removeAllRanges();
      selection.addRange(range);
    }
  }

  w.onload = function() : void
  {
    const
      BUTTONS : NodeListOf<HTMLButtonElement> = d.querySelectorAll('.profiler--sql-logs--element--ripple'),
      FILES_AND_LINES : NodeListOf<HTMLSpanElement> =
        d.querySelectorAll('.profiler--sql-logs--element--file, .profiler--sql-logs--element--line');

    for (let buttonsIndex: number = 0, length = BUTTONS.length; buttonsIndex < length; ++buttonsIndex)
    {
      BUTTONS[buttonsIndex].addEventListener('mouseup', copySqlToClipBoard);
    }

    for (
      let filesAndLinesIndex: number = 0, length = FILES_AND_LINES.length;
      filesAndLinesIndex < length;
      ++filesAndLinesIndex)
    {
      FILES_AND_LINES[filesAndLinesIndex].addEventListener('mouseup', copyFileAndLine);
    }

    d.getElementById('profiler--clear-sql-logs')
      .addEventListener('mouseup', updateLogs.bind(this, 'clear'));
    d.getElementById('profiler--refresh-sql-logs')
      .addEventListener('mouseup', updateLogs.bind(this, 'refresh'));
  };

  return {postLog};
})(window, document);
