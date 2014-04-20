onload = function(){
    "use strict";

    var debugBar = document.getElementById('debugBar'),
        debugBarSmall = debugBar.nextSibling,
        toggleElt = document.getElementById('toggle'),
        toggleSmallElt = document.getElementById('toggleSmall'),

        toggle = function()
        {
            if('none' == debugBar.style.display)
            {
                debugBar.style.display = 'block';
                debugBarSmall.style.display = 'none';
            }else{
                debugBar.style.display = 'none';
                debugBarSmall.style.display = 'block';
            }
        };

    window.debug = {
        postLog: function(data)
        {
            $('body').append('<div class="divError">' + data + '</div>');
        }
    };

    toggleSmallElt.onmouseup = toggleElt.onmouseup = function() { toggle(); };
}
