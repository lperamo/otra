(function(document : Document) : void
{
  'use strict';

  function toggleCfgSection() : void
  {
    let next : Element = this;

    while (next = next.nextElementSibling)
    {
      if (next.classList.contains('cfg-line') === false)
        break;

      next.classList.contains('hidden')
        ? next.classList.remove('hidden')
        : next.classList.add('hidden')
    }
  }

  function delegateMouseupEvents(event : Event): void
  {
    if (false === toolsBase.matches(<IHTMLElement> event.target, '.cfg-section'))
      return;

    toggleCfgSection.call(event.target);
  }

  function pageReady() : void
  {
    document.getElementById('main-cfg-div').addEventListener('mouseup', delegateMouseupEvents);
  }

  'loading' !== document.readyState
    ? pageReady()
    : document.addEventListener('DOMContentLoaded', pageReady);
})(document)
