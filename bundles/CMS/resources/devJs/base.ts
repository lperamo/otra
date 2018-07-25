// export const portfolioBase = ((window: Window, undef: undefined) =>

interface IEventTarget extends EventTarget {
  dataset: any
}

Element.prototype.closest =
  function(s : string) : HTMLElement
  {
    let matches = (this.document || this.ownerDocument).querySelectorAll(s),
      i,
      el = this;

    do
    {
      i = matches.length;
      while (--i >= 0 && matches.item(i) !== el) {}
    } while ((i < 0) && (el = el.parentElement));

    return el;
  };

const toolsBase = ((window : Window, undef : undefined) =>
{
  'use strict';

  /**
   * Checks any server error, warning ...whatever.
   *
   * @param {Response} response
   *
   * @returns Promise
   */
  let checkStatus = function checkStatus(response: Response)
  {
    if (true !== response.ok)
    {
      console.log('Looks like there was a problem. Status Code: ' + response.status);
      return;
    }

    return response.text();
  };

  /**
   * Adds some default request parameters to the request parameters passed...as parameters.
   *
   * @param {any} params
   *
   * @returns {{body: string, credentials: string, headers: any, method: string}}
   */
  function getRequestParameters(params: any) : { body : string, credentials : string, headers : any, method : string }
  {
    let headers : any = params.headers | <any> {
        Accept: 'text/plain',
        'Content-Type': 'application/x-www-form-urlencoded'
      };

    return {
      body: <string> params.body,
      credentials: 'same-origin',
      headers,
      method: <string> params.method
    };
  }

  /**
   * Use modern fetch or old fetch if we are under Safari.
   *
   * @param parameters
   */
  function fetch(parameters: {href : string, parameters : any, callback: any, errorCallback : any}) : void
  {
    if (undef !== self.fetch)
    {
      let myPromise = window.fetch(
        parameters.href,
        <RequestInit> getRequestParameters(parameters.parameters)
      );

      myPromise = myPromise.then(toolsBase.checkStatus);
      myPromise.then(parameters.callback);

      if (undef !== parameters.errorCallback)
        myPromise.then(parameters.errorCallback);
      else
        console.log('error'); // TODO implement it !
    } else
    {
      let localParameters : any = getRequestParameters(parameters),
          request = new XMLHttpRequest();

      // request.open(localParameters.method, localParameters, true);
      request.open(localParameters.method, parameters.href, true);
      request.onload = () =>
      {
        if (request.status >= 200 && request.status < 400)
        {
          let resp = request.responseText;
          parameters.callback();
          // return new Response('', { })
        } else
        {
          // We reached our target server, but it returned an error
          if (undef !== parameters.errorCallback)
            parameters.errorCallback();
          else
            console.log('error'); // TODO implement it !
        }
      };

      request.onerror = function()
      {
        // There was a connection error of some sort
      };

      request.send();
    }
  }

  /**
   * Polyfill for native function 'closest'.
   *
   * @param selector
   */
  // function closest(selector1 : HTMLElement, selector2 : string) : void
  // {
  //   if ((<any> window).Element !== undef && Element.prototype.closest !== undef)
  //     return this.closest(selector2);
  //
  //   Element.prototype.closest =
  //     function(s) {
  //       let matches = (this.document || this.ownerDocument).querySelectorAll(s),
  //         i,
  //         el = this;
  //
  //       do
  //       {
  //         i = matches.length;
  //         while (--i >= 0 && matches.item(i) !== el) {};
  //       } while ((i < 0) && (el = el.parentElement));
  //
  //       return el;
  //     };
  // }

  /**
   * Polyfill for native function 'matches'.
   *
   * @param el
   * @param {string} selector
   *
   * @returns {any}
   */
  function matches(el : IHTMLElement , selector : string) : any // TODO precise the types
  {
    return (el.matches || el.msMatchesSelector || el.webkitMatchesSelector).call(el, selector);
  }

  let dummy : any = {
    checkStatus,
    // closest,
    fetch,
    getRequestParameters,
    matches
  };

  // for some reasons we cannot send directly the structure...
  return dummy
})(window, undefined);
