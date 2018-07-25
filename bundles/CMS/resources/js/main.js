(function (d, window) {
    'use strict';
    var baseUrl = location.protocol + '//' + location.hostname + (location.port && ':' + location.port), boolMenuH = false;
    function changeArticle(response) {
        document.getElementsByClassName('article1')[0].innerHTML = response;
        boolMenuH = false;
    }
    /** Avoids to block the menu if there is any error */
    function menuErrorCallback() { boolMenuH = false; }
    /**
     * Fetch the article and shows it.
     *
     * @param {Event} evt
     */
    function fetchArticle(evt) {
        if (false === toolsBase.matches(evt.target, 'li.menu-h-li, .header-title'))
            return;
        if (true === boolMenuH)
            return;
        boolMenuH = true;
        if (window.location.href !== baseUrl + evt.target.dataset.href) {
            history.pushState(null, 'Welcome to the LPCMS -' + evt.target.title, evt.target.dataset.href);
            toolsBase.fetch({
                callback: changeArticle,
                errorCallback: menuErrorCallback,
                href: evt.target.dataset.href.replace('article', 'ajaxArticle')
            });
        }
    }
    function animateDeprecated(xmlhttp) {
        if (4 === xmlhttp.readyState && 200 === xmlhttp.status) {
            d.body.innerHTML += xmlhttp.responseText;
            d.getElementById('container').className += ' animate';
        }
    }
    function pageReady() {
        var html = d.getElementsByTagName('html')[0];
        if (false === toolsBase.matches(html, '.cli') && true === toolsBase.matches(html, '.ie6, .ie7, .ie8')) {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = animateDeprecated.bind(this, xmlhttp);
            xmlhttp.open('GET', '/bundles/CMS/views/jsTpls/deprecatedBrowser.phtml', true);
            xmlhttp.send();
        }
        document.getElementsByClassName('main-frame')[0].addEventListener('mouseup', fetchArticle);
    }
    'loading' !== document.readyState
        ? pageReady()
        : document.addEventListener('DOMContentLoaded', pageReady);
})(document, window);
//# sourceMappingURL=main.js.map