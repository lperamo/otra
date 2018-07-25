(function(document : Document, undef : undefined)
{
  'use strict';

  let formThis;

  function connectReturn(response : Response) : void
  {
    // fail
    if (undef === response.status)
      document.getElementById('connection-info').innerHTML = response[0];
    else // success
      document.location.href = response.url;
  }

  function checkStatus(response : Response) : Promise<any>
  {
    if (true !== response.ok)
    {
      console.log('Looks like there was a problem. Status Code: ' + response.status);
      return
    }

    return response.json()
  }

  function connect(evt : Event) : boolean
  {
    evt.preventDefault();
    formThis = this;

    window.fetch(this.dataset.href,
      {
        body: 'email=' + (<HTMLInputElement> document.getElementById('email')).value +
              '&pwd=' + (<HTMLInputElement> document.getElementById('pwd')).value,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        method: 'post'
      })
      .then(checkStatus).then(connectReturn)

    return false
  }

  function pageReady() : void
  {
    document.getElementById('connection-form').addEventListener('submit', connect, false)
  }

  'loading' !== document.readyState
    ? pageReady()
    : document.addEventListener('DOMContentLoaded', pageReady);
})(document, undefined);
