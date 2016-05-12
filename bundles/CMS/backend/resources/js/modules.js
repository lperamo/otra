(function(d, w, undef)
{
  "use strict";
  var __table0 = d.getElementById('table0'),
      __modulesMgt = d.getElementById('modulesMgt'),
      __elementsMgt = d.getElementById('elementsMgt'),
      __articlesMgt = d.getElementById('articlesMgt'),
      ajaxInit = {
        method: 'post',
        headers: {
          'Accept': 'application/json, text/plain, */*',
          "Content-Type": "application/x-www-form-urlencoded"
        },
        credentials: 'same-origin'
      },
      s = {
        replaceModulesContent(data) {
          changeModuleTab.call(__modulesMgt);
          __table0.innerHTML = data
        },

        checkStatus(response)
        {
        console.log('checkStatus');
        //console.log(response.headers.get("content-type"));
        //console.log(response.text());
        console.log(response.ok);
          if (true !== response.ok)
          {
          console.log('test1');
            response.text().then(text => function()
            {
              console.log('test2');
              d.getElementsByTagName('html')[0].innerHTML = text;
              console.log('Looks like there was a problem. Status Code: ' + response.status);
              return
            });
          }

          return response.text()
        },

        replaceElementsContent(data)
        {
          changeModuleTab.call(__elementsMgt);
          __table0.innerHTML = data
        },

        replaceArticlesContent(data)
        {
          changeModuleTab.call(__articlesMgt);
          __table0.innerHTML = data
        }
      };

      function moduleSearch(e)
      {
        if(13 === e.which)
          w.fetch(this.dataset.href, { search: this.value }).then(s.checkStatus).then(s[this.dataset.fn])
      }

      function getElements()
      {
        w.fetch('/backend/ajax/modules/get/elements', { id: this.dataset.id}).then(s.checkStatus).then(function(){
          console.log('coucou')
        })
      }

      function changeModuleTab(evt)
      {
        console.log('changeModuleTab');
        $('.activeTab', '#content').removeClass('activeTab');
        this.classList.add('activeTab');

        if(undef !== evt)
          w.fetch(new Request(this.dataset.href, ajaxInit)).then(s.checkStatus).then(s[this.dataset.fn])
      }

  $(function()
  {
    $('#content').on('keyup', '._genericSearch', moduleSearch)
                 .on('mouseup', '.seeDetails', getElements)
                 .on('mouseup', '.tab', changeModuleTab)
                 .on('mouseup', 'th:nth-child(1), td:nth-child(1)', backend.triggerCheckbox)
    $('#modules_all').on('mouseup', backend.selectAll);
  })
})(document, window)
