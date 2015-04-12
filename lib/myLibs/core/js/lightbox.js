window.lightbox = (function(d, u)
{
  "use strict";

  var container,
      callback,
      callbackYes,
      callbackNo,
      beginning = '<div class="lightboxContainer"><div class="lightbox oldlightbox tcenter"><div class="finalDiv',
      lightboxBeginning = beginning + '">',
      confirmBeginning = beginning + ' confirm">',
      ending = '</div></div></div>',
      confirmEnding = '<br><br><a id="lightboxCancel" class="softBtn confirmButton">Cancel</a><a id="lightboxOK" class="softBtn confirmButton">OK</a>' + ending;

  /**
   * We use either html or the url.
   *
   * @param {Object} params
   */
  var lightbox = function lightbox(params)
  {
    var params = {
      container: params.container || d.body,
      callback: params.callback,
      html: params.html,
      url: params.url,
      type: params.type
    }

    container = params.container;

    if(u === params.url)
    {
      params.container.insertAdjacentHTML('beforeend', lightboxBeginning + '>' + params.html + ending);
      u !== params.callback && params.callback()
    } else
    {
      var xhr = new XMLHttpRequest;

      xhr.onreadystatechange = function()
      {
        if (4 === xhr.readyState && 200 === xhr.status)
        {
          params.container.insertAdjacentHTML('beforeend', lightboxBeginning + xhr.responseText + ending);
          u !== params.callback && params.callback()
        }
      }

      xhr.open('GET', params.url, true), xhr.send()
    }
  }

  /* Confirm functions ******/
  function lightboxCancel()
  {
    confirmExit.call(this, callbackNo || u)
  }

  function lightboxOK()
  {
    confirmExit.call(this, callbackYes);
  }

  /**
   * Exiting a confirm dialog
   *
   * @param  {Function} callback - Callback to launch on yes or no (depending on what function has loaded this function)
   */
  function confirmExit(callback)
  {
    removeEventListener('mouseup', lightboxCancel, false);
    removeEventListener('mouseup', lightboxOK, false);

    exit.apply(this);

    // Calls the callback related to the pressed button
    u !== callback && callback.call(this)
  }

  function exit()
  {
    var lightboxContainer = this.parentNode.parentNode.parentNode;
    lightboxContainer.classList.add('lightboxEnding'); // animates the end
    setTimeout(function() { container.removeChild(lightboxContainer) }, 100);
  }

  /**
   * Creates the dialog html and assigns events.
   *
   * @param {string} code Code to put into the dialog.
   */
  function createConfirmDialog(code)
  {
    container.insertAdjacentHTML('beforeend', code);
    d.getElementById('lightboxCancel').addEventListener('mouseup', lightboxCancel);
    d.getElementById('lightboxOK').addEventListener('mouseup', lightboxOK)
  }

  /**
   * We use either html or the url.
   *
   * @param {string} html
   * @param {Function} _callbackYes
   * @param {Function} _callbackNo
   * @param {Object} _container
   */
  function confirm(html, _callbackYes, _callbackNo, _container)
  {
    container = _container || d.body;
    callbackYes = _callbackYes;
    callbackNo = _callbackNo;
    createConfirmDialog(confirmBeginning + html + confirmEnding)
  }

  /**
   * We use either html or the url.
   *
   * @param {Object} params
   */
  function advConfirm(params)
  {
    var params = {
      container: params.container || d.body,
      callbackYes: params.callbackYes,
      callbackNo: params.callbackNo,
      html: params.html,
      url: params.url
    };

    callbackYes = params.callbackYes;
    callbackNo = params.callbackNo;
    container = params.container

    if(u === params.url) // HTML content
      createConfirmDialog(confirmBeginning + params.html + confirmEnding);
    else // URL content
    {
      var xhr = new XMLHttpRequest;

      xhr.onreadystatechange = function()
      {
        4 === xhr.readyState && 200 === xhr.status && createConfirmDialog(confirmBeginning + xhr.responseText + confirmEnding)
      }

      xhr.open('GET', params.url, true), xhr.send()
    }
  }

  /* END Confirm functions ******/

  var dummy = {
    basic: lightbox,
    confirm: confirm,
    advConfirm: advConfirm,
    exit: exit
  };

  return dummy

})(document, void 0)
