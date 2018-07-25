(function(d : Document, w : Window, undef : undefined)
{
  'use strict';

  let $content : HTMLElement,
      localTable0 : HTMLElement,
      localModulesManagement : HTMLElement,
      localElementsManagement : HTMLElement,
      localArticlesManagement : HTMLElement,
      ajaxInit : {credentials: string, headers : any, method: string} = {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json, text/plain, */*',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        method: 'post'
      },
      s = {
        replaceModulesContent(data : string)
        {
          localTable0.innerHTML = data
        },

        replaceElementsContent(data : string)
        {
          localTable0.innerHTML = data
        },

        replaceArticlesContent(data : string)
        {
          localTable0.innerHTML = data
        }
      };

  /**
   *
   * @param {MouseEvent} e
   */
  function moduleSearch(e : MouseEvent) : void
  {
    if (13 === e.which)
      w.fetch(
        this.dataset.href,
        <any> { search: this.value }
      ).then(toolsBase.checkStatus).then(s[this.dataset.fn]);
  }

  function getElements() : void
  {
    w.fetch(
      '/backend/ajax/modules/get/elements',
      <any> { id: this.dataset.id}
    ).then(toolsBase.checkStatus)
    .then(function(){ })
  }

  /**
   * Menu management. (all menus)
   *
   * @param {MouseEvent|HTMLElement} evt
   */
  function changeModuleTab(evt : MouseEvent|HTMLElement)
  {
    let that : any;

    if ((<any> evt).target === undef)
      that = evt;
    else
      that = ((<any> evt).target.nodeName === 'LI') ? (<any> evt).target : (<any> evt).target.parentElement;

    that.parentElement.querySelector('li.active-tab').classList.remove('active-tab');
    that.classList.add('active-tab');

    // if (undef !== evt)
      w.fetch(new Request((<any> that.dataset).href, <any> ajaxInit))
       .then(toolsBase.checkStatus)
       .then(s[(<any> that.dataset).fn])
  }

  /**
   *
   * @param {KeyboardEvent} evt
   */
  function delegateKeyUpEvents(this : HTMLElement, evt: KeyboardEvent) : void
  {
    if (false === toolsBase.matches(<IHTMLElement> evt.target, 'input._generic-search'))
      return;

    moduleSearch.call(this, evt);
  }

  /**
   *
   * @param {MouseEvent} evt
   */
  function delegateMouseUpEvents(this : HTMLElement, evt: MouseEvent) : void
  {
    if (false === toolsBase.matches(
        <IHTMLElement> evt.target, 'a.see-details, li.tab, th:nth-child(1), td:nth-child(1), label[for=modules-all]'
      )
    )
      return;

    if (true === toolsBase.matches(<IHTMLElement> evt.target, 'a.see-details'))
      getElements.call(this, evt);

    if (true === toolsBase.matches(<IHTMLElement> evt.target, 'li.tab'))
      changeModuleTab.call(this, evt);

    if (true === toolsBase.matches(<IHTMLElement> evt.target, 'td:nth-child(1)'))
      backend.triggerCheckbox.call(this, evt);

    if (true === toolsBase.matches(<IHTMLElement> evt.target, 'th:nth-child(1), label[for=modules-all]'))
      backend.selectAll.call(this, evt);
  }

  /**
   *
   * @param {MouseEvent} evt
   */
  function delegateMouseUpEventsForMenu(this : HTMLElement, evt: MouseEvent) : void
  {
    if (false === toolsBase.matches(
        <IHTMLElement> evt.target, 'a, li.tab'
      )
    )
      return;

    if (true === toolsBase.matches(<IHTMLElement> evt.target, 'a, li.tab'))
      changeModuleTab.call(this, evt);
  }

  function pageReady() : void
  {
    /** CACHING */
    if (backend.$menus === undefined)
      backend.$menus = document.getElementById('menus');

    $content = document.getElementById('content');
    localTable0 = d.getElementById('table0');
    localModulesManagement = d.getElementById('modules-mgt');
    localElementsManagement = d.getElementById('elements-mgt');
    localArticlesManagement = d.getElementById('articles-mgt');

    /** EVENTS */
    $content.addEventListener('keyup', delegateKeyUpEvents);
    $content.addEventListener('mouseup', delegateMouseUpEvents);
    backend.$menus.addEventListener('mouseup', delegateMouseUpEventsForMenu);
  }

  'loading' !== document.readyState
    ? pageReady()
    : document.addEventListener('DOMContentLoaded', pageReady);
})(document, window, undefined);
