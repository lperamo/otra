interface IMyEvent extends Event {
  target: HTMLElement
}

interface IHTMLElement extends HTMLElement {
  matchesSelector : any,
  mozMatchesSelector : any,
  oMatchesSelector : any
}

(function(d : Document, window : Window) : void
{
  'use strict';

  let baseUrl : string = location.protocol + '//' + location.hostname + (location.port && ':' + location.port),
      boolMenuH : boolean = false;

  function changeArticle(response : string) : void
  {
    document.getElementsByClassName('article1')[0].innerHTML = response;
    boolMenuH = false;
  }

  /** Avoids to block the menu if there is any error */
  function menuErrorCallback() : void { boolMenuH = false; }

  /**
   * Fetch the article and shows it.
   *
   * @param {Event} evt
   */
  function fetchArticle(this, evt: IMyEvent) : void
  {
    if (false === toolsBase.matches(<IHTMLElement> evt.target, 'li.menu-h-li, .header-title'))
      return;

    if (true === boolMenuH)
      return;

    boolMenuH = true;

    if (window.location.href !== baseUrl + (<any> evt.target.dataset).href)
    {
      history.pushState(null, 'Welcome to the LPCMS -' + evt.target.title, (<any> evt.target.dataset).href);
      toolsBase.fetch(
        {
          callback : changeArticle,
          errorCallback: menuErrorCallback,
          href : (<any> evt.target.dataset).href.replace('article', 'ajaxArticle')
        }
      );
    }
  }

  function animateDeprecated(xmlhttp : XMLHttpRequest) : void
  {
    if (4 === xmlhttp.readyState && 200 === xmlhttp.status) {
      d.body.innerHTML += xmlhttp.responseText;
      d.getElementById('container').className += ' animate'
    }
  }

  function pageReady() : void
  {
    let html : HTMLHtmlElement = d.getElementsByTagName('html')[0];

    if (false === toolsBase.matches(<any> html, '.cli') && true === toolsBase.matches(<any> html, '.ie6, .ie7, .ie8'))
    {
      let xmlhttp = new XMLHttpRequest();

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
