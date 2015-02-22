(function ()
{
  "use strict";

  function unfold()
  {
    var eltTemp = el;
    if(eltTemp.nextSibling.className === "deepContent")
    {
      while(eltTemp.nextSibling !== null && eltTemp.nextSibling.className === "deepContent")
      {
        eltTemp.nextSibling.className = "";
        eltTemp = eltTemp.nextSibling;
      }
    }else{
      while(eltTemp.nextSibling !== null && eltTemp.nextSibling.className === "")
      {
        eltTemp.nextSibling.className = "deepContent";
        eltTemp = eltTemp.nextSibling;
      }
    }
  }

  function showArgs()
  {
    var eltTemp = el.parentNode.parentNode;

    eltTemp.nextSibling.className = (eltTemp.nextSibling.className === "deepContent")
    ? ""
    : "deepContent";
  }

  function addUnfoldListener(el) { el.addEventListener('click', unfold, false) }
  function addShowArgsListener(el) { console.log('test');el.addEventListener('click', showArgs, false) }

  [].forEach.call( document.querySelectorAll('.foldable'), addUnfoldListener);
  [].forEach.call( document.querySelectorAll('.showArgs'), addShowArgsListener)
})()
