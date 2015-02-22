(function(d, undef)
{
  "use strict";
  var __table0 = d.getElementById('table0'),
      __modulesMgt = d.getElementById('modulesMgt'),
      __elementsMgt = d.getElementById('elementsMgt'),
      __articlesMgt = d.getElementById('articlesMgt'),
      s = {
        replaceModulesContent(data) {
          changeModuleTab.call(__modulesMgt);
          __table0.innerHTML = data
        },

        replaceElementsContent(data)
        {
          // Errors management
          if(-1 !== data.indexOf('exception'))
          {
            data = JSON.parse(data);
            d.getElementsByTagName('html')[0].innerHTML = data.msg;
          }

          changeModuleTab.call(__elementsMgt);
          __table0.innerHTML = data
        },

        replaceArticlesContent(data) {
          changeModuleTab.call(__articlesMgt);
          __table0.innerHTML = data
        },
      },

      search = function moduleSearch(e)
      {
        if(13 === e.which)
          $.get(this.dataset.href, { search: this.value }, s[this.dataset.fn]);
      },

      getElements = function getElements()
      {
        $.get('/backend/ajax/modules/get/elements', { id: this.dataset.id}, function(){
          console.log('coucou');
        });
      },

      changeModuleTab = function changeModuleTab(evt)
      {
        $('.activeTab', '#content').removeClass('activeTab');
        this.classList.add('activeTab');

        if(undef !== evt)
          $.get(this.dataset.href, s[this.dataset.fn])
      };

  $(function()
  {
      $('#content').on('keyup', '._genericSearch', search)
                   .on('mouseup', '.seeElements', getElements)
                   .on('mouseup', '.tab', changeModuleTab)
  })
})(document)
