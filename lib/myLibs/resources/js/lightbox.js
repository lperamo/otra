var LIB_LIGHTBOX = (function (d, u) {
    'use strict';
    var container, callbackYes, callbackNo;
    var beginning = '<div class="lightbox-container"><div class="lightbox old-lightbox t-center"><div class="final-div', lightboxBeginning = beginning + '">', confirmBeginning = beginning + ' confirm">', ending = '</div></div></div>', confirmEnding = '<br><br><a id="lightbox-cancel" class="soft-btn confirm-button">Cancel</a><a id="lightbox-ok" class="soft-btn confirm-button">OK</a>' + ending;
    /**
     * We use either html or the url.
     *
     * @param {Object} params
     */
    var lightbox = function lightbox(params) {
        var params = {
            callback: params.callback,
            container: params.container || d.body,
            html: params.html,
            type: params.type,
            url: params.url
        };
        container = params.container;
        if (u === params.url) {
            params.container.insertAdjacentHTML('beforeend', lightboxBeginning + '>' + params.html + ending);
            u !== params.callback && params.callback();
        }
        else {
            var xhr_1 = new XMLHttpRequest();
            xhr_1.onreadystatechange = function () {
                if (4 === xhr_1.readyState && 200 === xhr_1.status) {
                    params.container.insertAdjacentHTML('beforeend', lightboxBeginning + xhr_1.responseText + ending);
                    u !== params.callback && params.callback();
                }
            };
            xhr_1.open('GET', params.url, true);
            xhr_1.send();
        }
    };
    /* Confirm functions ******/
    function lightboxCancel() {
        confirmExit.call(this, callbackNo || u);
    }
    function lightboxOK() {
        confirmExit.call(this, callbackYes);
    }
    /**
     * Exiting a confirm dialog
     *
     * @param  {Function} callback - Callback to launch on yes or no (depending on what function has loaded this function)
     */
    function confirmExit(callback) {
        removeEventListener('mouseup', lightboxCancel, false);
        removeEventListener('mouseup', lightboxOK, false);
        exit.apply(this);
        // Calls the callback related to the pressed button
        u !== callback && callback.call(this);
    }
    function exit() {
        var lightboxContainer = this.parentNode.parentNode.parentNode;
        lightboxContainer.classList.add('lightbox-ending'); // animates the end
        setTimeout(function removeLightBoxContainer() {
            container.removeChild(lightboxContainer);
        }, 100);
    }
    /**
     * Creates the dialog html and assigns events.
     *
     * @param {string} code Code to put into the dialog.
     */
    function createConfirmDialog(code) {
        container.insertAdjacentHTML('beforeend', code);
        d.getElementById('lightbox-cancel').addEventListener('mouseup', lightboxCancel);
        d.getElementById('lightbox-ok').addEventListener('mouseup', lightboxOK);
    }
    /**
     * We use either html or the url.
     *
     * @param {string} html
     * @param {Function} _callbackYes
     * @param {Function} _callbackNo
     * @param {Object} _container
     */
    function confirm(html, _callbackYes, _callbackNo, _container) {
        container = _container || d.body;
        callbackYes = _callbackYes;
        callbackNo = _callbackNo;
        createConfirmDialog(confirmBeginning + html + confirmEnding);
    }
    /**
     * We use either html or the url.
     *
     * @param {Object} params
     */
    function advConfirm(params) {
        var params = {
            callbackYes: params.callbackYes,
            container: params.container || d.body,
            callbackNo: params.callbackNo,
            html: params.html,
            url: params.url
        };
        callbackYes = params.callbackYes;
        callbackNo = params.callbackNo;
        container = params.container;
        if (u === params.url) // HTML content
            createConfirmDialog(confirmBeginning + params.html + confirmEnding);
        else // URL content
         {
            var xhr_2 = new XMLHttpRequest();
            xhr_2.onreadystatechange = function () {
                4 === xhr_2.readyState && 200 === xhr_2.status
                    && createConfirmDialog(confirmBeginning + xhr_2.responseText + confirmEnding);
            };
            xhr_2.open('GET', params.url, true);
            xhr_2.send();
        }
    }
    /* END Confirm functions ******/
    var dummy = {
        advConfirm: advConfirm,
        basic: lightbox,
        confirm: confirm,
        exit: exit
    };
    return dummy;
})(document, undefined);
//# sourceMappingURL=lightbox.js.map