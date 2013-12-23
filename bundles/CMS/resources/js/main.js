"use strict";

var baseUrl = location.protocol + "//" + location.hostname + (location.port && ":" + location.port);

$(function()
{
	$('#headerTitle, .menuHLi').click(this, function()
	{
		if(window.location.href != baseUrl + this.dataset.href)
		{
			history.pushState(null, 'Welcome to the LPCMS -' + this.title, this.dataset.href);
			$.get(
				this.dataset.href.replace('article', 'ajaxArticle'),
				function(response) { $('#article1').html(response); }
			);
		}
	});
});
