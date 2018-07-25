(function (d, w, undef) {
    'use strict';
    var $content, localTable0, localModulesManagement, localElementsManagement, localArticlesManagement, ajaxInit = {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json, text/plain, */*',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        method: 'post'
    }, s = {
        replaceModulesContent: function (data) {
            localTable0.innerHTML = data;
        },
        replaceElementsContent: function (data) {
            localTable0.innerHTML = data;
        },
        replaceArticlesContent: function (data) {
            localTable0.innerHTML = data;
        }
    };
    /**
     *
     * @param {MouseEvent} e
     */
    function moduleSearch(e) {
        if (13 === e.which)
            w.fetch(this.dataset.href, { search: this.value }).then(toolsBase.checkStatus).then(s[this.dataset.fn]);
    }
    function getElements() {
        w.fetch('/backend/ajax/modules/get/elements', { id: this.dataset.id }).then(toolsBase.checkStatus)
            .then(function () { });
    }
    /**
     * Menu management. (all menus)
     *
     * @param {MouseEvent|HTMLElement} evt
     */
    function changeModuleTab(evt) {
        var that;
        if (evt.target === undef)
            that = evt;
        else
            that = (evt.target.nodeName === 'LI') ? evt.target : evt.target.parentElement;
        that.parentElement.querySelector('li.active-tab').classList.remove('active-tab');
        that.classList.add('active-tab');
        // if (undef !== evt)
        w.fetch(new Request(that.dataset.href, ajaxInit))
            .then(toolsBase.checkStatus)
            .then(s[that.dataset.fn]);
    }
    /**
     *
     * @param {KeyboardEvent} evt
     */
    function delegateKeyUpEvents(evt) {
        if (false === toolsBase.matches(evt.target, 'input._generic-search'))
            return;
        moduleSearch.call(this, evt);
    }
    /**
     *
     * @param {MouseEvent} evt
     */
    function delegateMouseUpEvents(evt) {
        if (false === toolsBase.matches(evt.target, 'a.see-details, li.tab, th:nth-child(1), td:nth-child(1), label[for=modules-all]'))
            return;
        if (true === toolsBase.matches(evt.target, 'a.see-details'))
            getElements.call(this, evt);
        if (true === toolsBase.matches(evt.target, 'li.tab'))
            changeModuleTab.call(this, evt);
        if (true === toolsBase.matches(evt.target, 'td:nth-child(1)'))
            backend.triggerCheckbox.call(this, evt);
        if (true === toolsBase.matches(evt.target, 'th:nth-child(1), label[for=modules-all]'))
            backend.selectAll.call(this, evt);
    }
    /**
     *
     * @param {MouseEvent} evt
     */
    function delegateMouseUpEventsForMenu(evt) {
        if (false === toolsBase.matches(evt.target, 'a, li.tab'))
            return;
        if (true === toolsBase.matches(evt.target, 'a, li.tab'))
            changeModuleTab.call(this, evt);
    }
    function pageReady() {
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
//# sourceMappingURL=modules.js.map