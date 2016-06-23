(function (doc)
{
  "use strict";

  function unfold()
  {
    var eltTemp = this;

    if(eltTemp.nextSibling.className === 'deep-content')
    {
      while (null !== eltTemp.nextSibling && 'deep-content' === eltTemp.nextSibling.className)
      {
        eltTemp.nextSibling.className = '';
        eltTemp = eltTemp.nextSibling;
      }
    } else {
      while (null !== eltTemp.nextSibling && '' === eltTemp.nextSibling.className)
      {
        eltTemp.nextSibling.className = "deep-content";
        eltTemp = eltTemp.nextSibling;
      }
    }
  }

  function showArgs()
  {
    var eltTemp = this.parentNode.parentNode;

    eltTemp.nextSibling.className = (eltTemp.nextSibling.className === 'deep-content')
      ? ''
      : 'deep-content';
  }

  function addUnfoldListener(el) { el.addEventListener('click', unfold, false) }
  function addShowArgsListener(el) { console.log('test');el.addEventListener('click', showArgs, false) }

  [].forEach.call( doc.querySelectorAll('.foldable'), addUnfoldListener);
  [].forEach.call( doc.querySelectorAll('.show-args'), addShowArgsListener)
})(document)
