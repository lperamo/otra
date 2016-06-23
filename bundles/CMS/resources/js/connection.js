"use strict";

(function(document, undef)
{
  let formThis;

  function connectReturn(response)
  {
    // fail
    if (undef === response.status)
      $('#connection-info').html(response[0])
    else // success
      document.location.href = formThis.dataset.href_redirect
  }

  function checkStatus(response)
  {
    if (true !== response.ok)
    {
      console.log('Looks like there was a problem. Status Code: ' + response.status);
      return
    }

    return response.json()
  }

  function connect(evt)
  {
    evt.preventDefault();
    formThis = this;

    window.fetch(this.dataset.href,
      {
        method: 'post',
        headers: {
          'Accept': 'application/json, text/plain, */*',
          "Content-Type": "application/x-www-form-urlencoded"
        },
        credentials: 'same-origin',
        body: 'email=' + document.getElementById('email').value +
              '&pwd=' + document.getElementById('pwd').value
      })
      .then(checkStatus).then(connectReturn)

    return 0
  }

  $(function ()
  {
    document.getElementById('connection-form').addEventListener("submit", connect, false)
  })
})(document);
