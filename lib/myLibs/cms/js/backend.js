"use strict";

var baseUrl = location.protocol + "//" + location.hostname + (location.port && ":" + location.port);

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
	$('.tab').click(this, function()
	{
		if(window.location.href != baseUrl + this.dataset.href)
		{
			var $this = $(this);
			$('.activeTab').removeClass('activeTab');
			$this.addClass('activeTab');
			var ajaxLink = this.dataset.href.replace('backend/', 'backend/ajax/'),
					title = 'Backend administration - ' + this.dataset.tooltip;
			history.pushState({link: ajaxLink, title: title}, title, this.dataset.href);
			$.get(
				//this.dataset.href.replace('article', 'ajaxArticle'),
				ajaxLink,
				function(response) {
					$('#content').html(response); $('title').text(title);
					if(window.initUsers && 'Users' === $this.text())
						window.initUsers('test***');
			 }
			);
		}
	});
});
