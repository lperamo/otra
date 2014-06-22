(function(d){
	'use strict';

	var matches = function(el, selector) {
		el = el[0];
	  return (el.matches || el.matchesSelector || el.msMatchesSelector || el.mozMatchesSelector || el.webkitMatchesSelector || el.oMatchesSelector).call(el, selector);
	},
		baseUrl = location.protocol + '//' + location.hostname + (location.port && ':' + location.port),
		changeArticle = function(){
			if(window.location.href != baseUrl + this.dataset.href)
			{
				history.pushState(null, 'Welcome to the LPCMS -' + this.title, this.dataset.href);
				$.get(
					this.dataset.href.replace('article', 'ajaxArticle'),
					function(response) { $('#article1').html(response) }
				)
			}
		};

	window.onload = function(){
		// Protection against console.log IE problems
		if(!window.console)
			window.console = {log:function(){}};

		var html = d.getElementsByTagName('html');
		if(!matches(html, '.cli') && matches(html, '.ie6, .ie7, .ie8'))
		{
			var xmlhttp;

	    xmlhttp = (window.XMLHttpRequest)
        ? new XMLHttpRequest() // code for IE7+, Firefox, Chrome, Opera, Safari
	    	: new ActiveXObject('Microsoft.XMLHTTP'); // code for IE6, IE5

	    xmlhttp.onreadystatechange = function() {
        if (4 === xmlhttp.readyState && 200 === xmlhttp.status) {
          d.body.innerHTML += xmlhttp.responseText;
          d.getElementById(container).className += ' animate'
        }
	    }

	    xmlhttp.open('GET', '/bundles/CMS/views/jsTpls/deprecatedBrowser.phtml', true);
	    xmlhttp.send();
		}

		//$('#headerTitle, .menuHLi').click(this, changeArticle)
	};
})(document);
