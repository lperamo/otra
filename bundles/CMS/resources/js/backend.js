(function()
{
	"use strict";

  var tabs = 0,
    updateURL = function( event )
    {
      if (event.originalEvent.state)
      {
        $.get(
          event.originalEvent.state.link,
          function(response) { $('#content').html(response); $('title').text(event.originalEvent.state.title); }
        );
      }
    },

    /** Updates the tab CSS and updates the actual content */
    changeTab = function($this, index)
    {
     $('#tab' + index).show();
     $('#tab' + $('.activeTab').removeClass('activeTab').index()).hide();
     $this.addClass('activeTab')
    },

    activateTab = function activateTab()
    {
      var $this = $(this),
      index = $(this).index();

      if(! $this.hasClass('activeTab'))
      {
        var ajaxLink = this.dataset.href.replace('backend/', 'backend/ajax/'),
            title = 'Backend administration - ' + this.dataset.tooltip;

        if(!((tabs & Math.pow(2, index)) >> index)) // if this tab isn't already loaded
        {
          $.get(
            ajaxLink,
            function(response)
            {
              $('#content').append(response);
              $('title').text(title);
              tabs += Math.pow(2, index);
              changeTab($this, index);
            }
          );
        } else
          changeTab($this, index);

        history.pushState({link: ajaxLink, title: title}, title, this.dataset.href);
      }
    };

	$(window).on('popstate', updateURL);

	$(function()
	{
		tabs = Math.pow(2, $('.activeTab').index());
		$('#menus').on('click', '.tab', activateTab);
	});
})();
