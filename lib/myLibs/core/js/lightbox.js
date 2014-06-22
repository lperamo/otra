(function(d, u){
  "use strict";
  window.lightBox = function(container, url, callback){
    if(null === d.getElementById('lbCSS'))
      d.body.insertAdjacentHTML('beforeend', '\n\
        <style id="lbCSS">\n\
        .alignLeft{text-align:left}\n\
        .lightboxContainer{align-items:center;color:#EEE;display:flex;min-height:100%;justify-content:center;position:absolute;text-shadow:0 0 3px #CCC;top:0;width:100%;z-index:10000}\n\
        .lightbox{background:rgba(51,51,51,0.8)}\n\
        </style>');

    var xhr = new XMLHttpRequest;

    xhr.onreadystatechange = function() {
      if (4 === xhr.readyState && 200 === xhr.status){
        container.insertAdjacentHTML('beforeend', '<div class="lightboxContainer"><div class="lightbox oldLightbox tcenter"><div class="alignLeft">' + xhr.responseText + '</div></div></div>');
        if (u !== callback) callback()
      }
    }

    xhr.open('GET', url, true), xhr.send()
  }
})(document)
