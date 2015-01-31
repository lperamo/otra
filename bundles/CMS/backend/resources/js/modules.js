(function()
{
  "use strict";
  var $table0 = $('#table0'),
      $breadCrumb = $('#breadCrumb'),
      s = {
        replaceModulesContent(data) {
          $breadCrumb.text('Modules');
          $table0.html(data)
        },

        replaceElementsContent(data) {
          $breadCrumb.text('Modules >> Elements');
          $table0.html(data)
        },

        replaceArticlesContent(data) {
          $breadCrumb.text('Modules >> Elements >> Articles');
          $table0.html(data)
        },
      },

      search = function moduleSearch(e)
      {
        if(13 === e.which)
          $.get(this.dataset.href, { search: this.value }, s[this.dataset.fn])
      },

      getElements = function getElements()
      {
        $.get('/backend/ajax/modules/get/elements', { id: this.dataset.id}, function(){
          console.log('coucou');
        });
      };

  $(function()
  {
      $('#content').on('keyup', '._genericSearch', search)
                   .on('click', '.seeElements', getElements);
  })
})()
