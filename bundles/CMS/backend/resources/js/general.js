(function()
{
  "use strict";

  function toggleCfgSection()
  {
    var next = this;

    while(next = next.nextElementSibling)
    {
      if(!next.classList.contains('cfg-line'))
        break;

      next.classList.contains('hidden')
        ? next.classList.remove('hidden')
        : next.classList.add('hidden')
    }
  }

  $(function()
  {
    $('#main-cfg-div').on('mouseup', '.cfg-section', toggleCfgSection)
  })
})()
