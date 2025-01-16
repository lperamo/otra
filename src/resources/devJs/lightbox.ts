window['LIB_LIGHTBOX'] = (function(d : Document, u : undefined)
{
  'use strict';

  let container,
      callbackYes,
      callbackNo;

  const beginning = '<div class="lightbox--container"><div class="lightbox"><div class="final-div',
      lightboxBeginning = beginning + '">',
      confirmBeginning = beginning + ' confirm">',
      ending = '</div></div></div>',
      confirmEnding = '<br><br><a id="lightbox-cancel" class="soft-btn confirm-button">Cancel</a><a id="lightbox-ok" class="soft-btn confirm-button">OK</a>' + ending;

  /**
   * We use either HTML or the url.
   *
   * @param {Object} params
   */
  const lightbox = function lightbox(params : any) : void
  {
    params = {
      callback: params.callback,
      container: params.container || d.body,
      html: params.html,
      type: params.type,
      url: params.url
    };

    container = params.container;

    if (u === params.url)
    {
      params.container.insertAdjacentHTML('beforeend', lightboxBeginning + '>' + params.html + ending);
      u !== params.callback && params.callback()
    } else
    {
      const xhr : XMLHttpRequest = new XMLHttpRequest();

      xhr.onreadystatechange = function() : void
      {
        if (4 === xhr.readyState && 200 === xhr.status)
        {
          params.container.insertAdjacentHTML('beforeend', lightboxBeginning + xhr.responseText + ending);
          u !== params.callback && params.callback()
        }
      };

      xhr.open('GET', params.url, true);
      xhr.send()
    }
  };

  /* Confirm functions ******/
  function lightboxCancel() : void
  {
    confirmExit.call(this, callbackNo || u)
  }

  function lightboxOK() : void
  {
    confirmExit.call(this, callbackYes);
  }

  /**
   * Exiting a confirm dialog
   *
   * @param  {Function} callback - Callback to launch on yes or no (depending on what function has loaded this function)
   */
  function confirmExit(callback : any)
  {
    d.removeEventListener('mouseup', lightboxCancel, false);
   d. removeEventListener('mouseup', lightboxOK, false);

    exit.apply(this);

    // Calls the callback related to the pressed button
    u !== callback && callback.call(this)
  }

  function exit() : void
  {
    const lightboxContainer : HTMLElement = this.parentNode.parentNode.parentNode;
    lightboxContainer.classList.add('lightbox-ending'); // animates the end
    setTimeout(function removeLightBoxContainer() : void
    {
      container.removeChild(lightboxContainer)
    }, 100);
  }

  /**
   * Creates the dialog HTML and assigns events.
   *
   * @param {string} code Code to put into the dialog.
   */
  function createConfirmDialog(code : string) : void
  {
    container.insertAdjacentHTML('beforeend', code);
    d.getElementById('lightbox-cancel').addEventListener('mouseup', lightboxCancel);
    d.getElementById('lightbox-ok').addEventListener('mouseup', lightboxOK)
  }

  /**
   * We use either HTML or the url.
   *
   * @param {string} html
   * @param {Function} _callbackYes
   * @param {Function} _callbackNo
   * @param {Object} _container
   */
  function confirm(html : string, _callbackYes : any, _callbackNo : any, _container : any) : void
  {
    container = _container || d.body;
    callbackYes = _callbackYes;
    callbackNo = _callbackNo;
    createConfirmDialog(confirmBeginning + html + confirmEnding)
  }

  /**
   * We use either HTML or the url.
   *
   * @param {Object} params
   */
  function advConfirm(params : any)
  {
    params = {
      callbackYes: params.callbackYes,
      container: params.container || d.body,
      callbackNo: params.callbackNo,
      html: params.html,
      url: params.url
    };

    callbackYes = params.callbackYes;
    callbackNo = params.callbackNo;
    container = params.container;

    if (u === params.url) // HTML content
      createConfirmDialog(confirmBeginning + params.html + confirmEnding);
    else // URL content
    {
      const xhr : XMLHttpRequest = new XMLHttpRequest();

      xhr.onreadystatechange = function() : void
      {
        4 === xhr.readyState && 200 === xhr.status
          && createConfirmDialog(confirmBeginning + xhr.responseText + confirmEnding)
      };

      xhr.open('GET', params.url, true);
      xhr.send()
    }
  }
  /* END Confirm functions ******/

  const dummy : any = {
    advConfirm,
    basic: lightbox,
    confirm,
    exit
  };

  return dummy

})(document, undefined);
