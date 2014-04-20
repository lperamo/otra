(function(){
	"use strict";

	$(window).on('popstate', function( event ){
	  if (event.originalEvent.state) {
	  	$.get(
			event.originalEvent.state.link,
			function(response) { $('#content').html(response); $('title').text(event.originalEvent.state.title); }
		);
	  }
	});

	$(function()
	{
		//var baseUrl = location.protocol + "//" + location.hostname + (location.port && ":" + location.port),
		var tabs = Math.pow(2, $('.activeTab').index()),
			changeContent = function($this, index){
				//console.log($('#content'), index, $('#content').eq(index), $('#content').eq(index).end().eq($('.activeTab').index()));
			 $('#table' + index).show();
			 $('#table' + $('.activeTab').index()).hide();
			 $('.activeTab').removeClass('activeTab');
			 $this.addClass('activeTab')
			};

		$('.tab').click(this, function()
		{
			var $this = $(this),
			index = $(this).index();

			if(! $this.hasClass('active'))
			{
				var ajaxLink = this.dataset.href.replace('backend/', 'backend/ajax/'),
						title = 'Backend administration - ' + this.dataset.tooltip;

				console.log(tabs);

				if(!((tabs & Math.pow(2, index)) >> index)) // if this tab is already loaded
			//if(window.location.href != baseUrl + this.dataset.href)
				{
					$.get(
						ajaxLink,
						function(response) {
							$('#content').append(response); $('title').text(title);
							tabs += Math.pow(2, index);
							changeContent($this, index);
					 }
					);
				}else
					changeContent($this, index);




				history.pushState({link: ajaxLink, title: title}, title, this.dataset.href);
			}
		});
	});
})();
