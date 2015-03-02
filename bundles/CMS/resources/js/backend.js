(function(d)
{
  "use strict";

  var tabs = 0;
  function updateURL( event )
  {
    if (event.originalEvent.state)
    {
      $.get(
        event.originalEvent.state.link,
        function(response) {
          d.getElementById('content').innerHTML = response;
          d.getElementsByTagName('title')[0].textContent = event.originalEvent.state.title;
        }
      );
    }
  }

  /** Updates the tab CSS and updates the actual content */
  function changeTab($this, index)
  {
   $('#tab' + index).show();
   $('#tab' + $('.activeTab', '#menus').removeClass('activeTab').index()).hide();
   $this.addClass('activeTab')
  }

  function activateTab()
  {
    var $this = $(this),
    index = $this.index();

    if(! $this.hasClass('activeTab'))
    {
      var ajaxLink = this.dataset.href.replace('backend/', 'backend/ajax/'),
          title = this.textContent + ' - Backend';

      if(!((tabs & Math.pow(2, index)) >> index)) // if this tab isn't already loaded
      {
        $.get(
          ajaxLink,
          function(response)
          {
            try {
                response = JSON.parse(response);
                document.getElementsByTagName("html")[0].innerHTML = response.msg;
            } catch (e) {
                $('#content').append(response);
            }
            tabs += Math.pow(2, index);
            changeTab($this, index);
          }
        );
      } else
        changeTab($this, index);

      d.getElementsByTagName('title')[0].textContent = title;
      history.pushState({link: ajaxLink, title: title}, title, this.dataset.href);
    }
  }

  $(window).on('popstate', updateURL);

  $(function()
  {
    tabs = Math.pow(2, $('.activeTab').index());
    $('#menus').on('click', '.tab', activateTab);
  });
})(document);
