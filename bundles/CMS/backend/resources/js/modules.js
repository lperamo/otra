(function(){
  var moduleSearch = function(e){
    if(13 === e.which)
      $.get('/backend/ajax/modules/search/module', { search: $(this).val() }, function(data){
        $('#content').html(data)
      })

  },
  elementSearch = function(e){
    if(13 === e.which)
      $.get('/backend/ajax/modules/search/element', { search: $(this).val() }, function(data){
        $('#content').html(data)
      })

  },
  articleSearch = function(e){
    if(13 === e.which)
      $.get('/backend/ajax/modules/search/article', { search: $(this).val() }, function(data){
        $('#content').html(data)
      })
  },
  getElements = function(){
    $.get('/backend/ajax/modules/get/elements', { id: this.getAttribute('data-id')}, function(){
      console.log('coucou');
    });
  }

  $(function(){
      $('#moduleSearch').keyup(moduleSearch);
      $('#elementSearch').keyup(elementSearch);
      $('#articleSearch').keyup(articleSearch);
      $('.seeElements').click(getElements);
  })
})()
