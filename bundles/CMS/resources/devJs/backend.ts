let backend = (function(d : Document) : any // TODO be more precise on the return type ?
{
  'use strict';

  let tabs : number = 0, // mask for active tabs
      $menus : HTMLElement,
      $content : HTMLElement;

  function updateURL( event : any) : void // TODO it is not any but PopStateEvent but problem with originalEvent !
  {
    if (event.originalEvent.state)
    {
      toolsBase.fetch({
        callback: updateURLSuccess.bind(null, event.originalEvent.state),
        errorCallback: updateURLError,
        href: event.originalEvent.state.link,
        parameters: {}
      });
    }
  }

  function updateURLSuccess(response : string, state) : void
  {
    console.log('**************',response, '++++++++++++', state);
    $content.innerHTML = response;
    d.getElementsByTagName('title')[0].textContent = state.title;
  }

  function updateURLError() : void
  {
    // error TODO implement it
  }

  /**
   * Updates the tab CSS and updates the actual content
   * TODO fix it to make it more generic, merge main and sub tabs behaviour
   */
  function changeTab(indexNumber : number) : void
  {
    // For sub menu tabs only TODO ONLY FOR SUB TABS
    document.getElementById('tab' + indexNumber).classList.remove('hidden');

    // Deactivates the old active tabs TODO GENERIC => OK
    this.parentElement.querySelector('.active-tab').classList.remove('active-tab');

    // Hides the old active content TODO ONLY FOR MAIN TABS
    // let oldContentActiveTab : Element = this.parentElement.querySelector('.active-tab')
    let oldContentActiveTab : Element = document.querySelector('tab-content.active-tab')
    oldContentActiveTab.classList.remove('active-tab');
    oldContentActiveTab.classList.add('hidden');

    // Activates the new active li tab TODO GENERIC => OK
    this.classList.add('active-tab');

    // Shows the old active content TODO ONLY FOR MAIN TABS
    let newActiveContentTab : Element = document.getElementById('tab' + index(this));
    newActiveContentTab.classList.remove('hidden');
    newActiveContentTab.classList.add('active-tab');
  }

  /**
   * Activates the section related to the tab and fetch data from network only if needed
   */
  function activateTab() : void
  {
    // if the tab is already active, we left
    if (true === this.classList.contains('active-tab'))
      return;

    let indexNumber : number  = index(this),
        ajaxLink : string = this.dataset.href.replace('backend/', 'backend/ajax/');

    // Checks the tab mask to see whether this tab is already loaded or not
    if (false === Boolean(((tabs & Math.pow(2, indexNumber)) / indexNumber)))
    {
      toolsBase.fetch(
        {
          callback: activateTabSuccess.bind(this, indexNumber),
          errorCallback: activateTabError,
          href: ajaxLink,
          parameters: {}
        }
      );
    } else
      changeTab.call(this, indexNumber);

    // We update the browser history state
    let title : string = this.textContent + ' - Backend';

    d.getElementsByTagName('title')[0].textContent = title;
    history.pushState(
      {
        link: ajaxLink,
        title
      },
      title,
      this.dataset.href
    );
  }

  function activateTabSuccess(indexNumber : number, response : any) : void
  {
    try
    {
      response = JSON.parse(response);
      document.getElementsByTagName('html')[0].innerHTML = response.msg;
    } catch (e)
    {
      $content.innerHTML += response;
      // document.getElementsByTagName('html')[0].innerHTML = response;
    }

    tabs += Math.pow(2, indexNumber);
    changeTab.call(this, indexNumber);
  }

  function activateTabError() : void
  {
    // error TODO implement it
  }

  /**
   *  Allow to click on TDs and THs
   * (and LABELs for custom checkboxes and custom radio buttons) instead of directly on checkboxes
   */
  function triggerCheckbox(this, evt : MouseEvent) : boolean
  {
    // We ensure us that it is really a checkbox
    if ('TD' !== (<any> evt.target).tagName
      && 'TH' !== (<any> evt.target).tagName
      && 'LABEL' !== (<any> evt.target).tagName)
      return false;

    // Triggers an event that checks / unchecks all the checkboxes
    let event = document.createEvent('HTMLEvents');
    event.initEvent(evt.type, true, false);
    (<any> evt.target).children[0].dispatchEvent(event);

    // Check / uncheck the 'all' checkbox. Acts here like a XOR, LABEL => checked, TD => !checked
    // because of different behaviours depending on whether we click on the TD or the LABEL
    (<any> evt.target).children[0].checked =
      ('TD' === (<any> evt.target).tagName || 'TH' === (<any> evt.target).tagName)
      !== (<any> evt.target).children[0].checked;
  }

  /**
   * Select/Deselect all the related checkboxes
   */
  function selectAll(evt : MouseEvent) : void
  {
    if ((<any> evt.target).nodeName === 'TH')
    {
      let $checkAll = (<any> evt.target).querySelector('input[type=checkbox]');
      $checkAll.checked = !$checkAll.checked;
    }

    [].map.call(
      (<any> evt.target).closest('table').querySelectorAll('input[type=checkbox]:not(._select-all)'),
      function (e : HTMLElement) : void { (<any> e).checked = ! (<any> e).checked }
    );
  }

  /**
   * Make a check before launching a CRUD function via AJAX
   */
  function beforeAction(text : string, callback) : any
  {
    let content = document.getElementById('content').querySelector('table'),
      checkboxesChecked = content.querySelectorAll('input[type=checkbox]:checked');

    if (0 === checkboxesChecked.length)
    {
      notif.run(content[0], 'Nothing was selected !', 'WARNING', notif.WARNING, 10000);
      return false
    }

    if (true === confirm('Do you really want to validate all the changes ?'))
    {
      (<any> checkboxesChecked).closest('tr').each(function iterateTR() : void {
        callback.call(this, content)
      });
    }
  }

  /**
   * @param evt
   */
  function delegateMouseUpEvent(evt : MouseEvent) : void
  {
    // if we have a link then we get his parent LI in order to use the same markup in any case
    let that : EventTarget = evt.target,
        parentElement : HTMLElement = (<any> that).parentElement;

    if (parentElement.classList.contains('tab') === true)
      that = parentElement;

    // if we don't clicked on a menu element then we return...
    if (false === toolsBase.matches(<IHTMLElement> that, 'li.tab'))
      return;

    // otherwise we change the page by activating the tab

    activateTab.call(that);
  }

  /**
   * Translation of index() from jQuery with an added filter.
   *
   * @param element
   *
   * @returns {number}
   */
  function index(element : Element, filter?: string) : number
  {
    let sib = element.parentNode.childNodes,
        n = 0;

    for (let i = 0, len = sib.length; i < len; i++)
    {
      if (sib[i] === element) return n;

      if (sib[i].nodeType === 1
        && (filter === undefined
          || true === toolsBase.matches(sib[i], filter)
        )
      )
        ++n;
    }

    return -1;
  }

  function pageReady() : void
  {
    // caching
    $menus = document.getElementById('menus');
    tabs = Math.pow(2, index(document.getElementsByClassName('active-tab')[0]));
    $content = document.getElementById('content');

    // Events
    window.addEventListener('popstate', updateURL);
    $menus.addEventListener('mouseup', delegateMouseUpEvent);
  }

  'loading' !== document.readyState
    ? pageReady()
    : document.addEventListener('DOMContentLoaded', pageReady);

  let dummy : any = {
    $menus,
    beforeAction,
    selectAll,
    triggerCheckbox
  };

  return dummy
})(document);
