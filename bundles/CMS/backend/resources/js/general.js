(function()
{
  "use strict";

  function toggleCfgSection()
  {
    var next = this;

    while(next = next.nextElementSibling)
    {
      if(!next.classList.contains('cfgLine'))
        break;

      next.classList.contains('hidden')
        ? next.classList.remove('hidden')
        : next.classList.add('hidden')
    }
  }

  $(function()
  {
    $('#mainCfgDiv').on('mouseup', '.cfgSection', toggleCfgSection)
  })
})()
