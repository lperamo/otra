(function (doc) {
    'use strict';
    function unfold() {
        var eltTemp = this;
        if (eltTemp.nextElementSibling.className === 'deep-content') {
            while (null !== eltTemp.nextElementSibling && 'deep-content' === eltTemp.nextElementSibling.className) {
                eltTemp.nextElementSibling.className = '';
                eltTemp = eltTemp.nextElementSibling;
            }
        }
        else {
            while (null !== eltTemp.nextElementSibling && '' === eltTemp.nextElementSibling.className) {
                eltTemp.nextElementSibling.className = 'deep-content';
                eltTemp = eltTemp.nextElementSibling;
            }
        }
    }
    function showArgs() {
        var eltTemp = this.parentNode;
        eltTemp.nextElementSibling.className = (eltTemp.nextElementSibling.className === 'deep-content')
            ? ''
            : 'deep-content';
    }
    function addUnfoldListener(el) { el.addEventListener('click', unfold, false); }
    function addShowArgsListener(el) { el.addEventListener('click', showArgs, false); }
    [].forEach.call(doc.querySelectorAll('.foldable'), addUnfoldListener);
    [].forEach.call(doc.querySelectorAll('.show-args'), addShowArgsListener);
})(document);
//# sourceMappingURL=tools.js.map