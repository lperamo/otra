(function (document) {
    'use strict';
    var selectChoiceEventHandler = [], selectChoicesEventHandler;
    function delegateMouseUp(evt) {
        var that = evt.target, actualSelectValue = '.actual-select-value';
        if (false === toolsBase.matches(that, actualSelectValue)) {
            if (false === toolsBase.matches(that.parentElement, actualSelectValue))
                return;
            that = that.parentElement;
        }
        selectClick.call(that, evt);
    }
    /**
     * Shows the select choices and add the related listeners
     *
     * @param {Event} evt
     */
    function selectClick(evt) {
        evt.stopImmediatePropagation();
        var $selectChoices = this.parentElement.querySelector('.select-choices'), $selectChoice = $selectChoices.querySelectorAll('.select-choice');
        $selectChoices.classList.toggle('select-visible');
        // Retrieves the selected value in the select
        [].map.call($selectChoice, function callEachSelectChoice(that, i) {
            selectChoiceEventHandler[i] = selectChoice.bind(that, $selectChoices);
            that.addEventListener('mouseup', selectChoiceEventHandler[i], false);
        });
        // console.log($selectChoices);
        selectChoicesEventHandler = hideChoices.bind($selectChoices, $selectChoice);
        $selectChoices.addEventListener('mouseup', selectChoicesEventHandler);
    }
    /**
     * Set the SELECT's value related to the chosen option and removes options listeners
     *
     * @param {NodeListOf<Element>} $selectChoices
     */
    function selectChoice($selectChoices) {
        // Set the SELECT's value related to the chosen option
        var $actualSelectValue = this.parentElement.parentElement.querySelector('.actual-select-value__link');
        $actualSelectValue.setAttribute('data-value', this.dataset.value);
        $actualSelectValue.textContent = this.innerHTML;
        // Removes listeners to avoid memory leaks
        // console.log($selectChoices);
        hideChoices.call($selectChoices, this);
    }
    /**
     * Hides the select choices and remove the listeners associated to the choices
     *
     * @param {NodeListOf<Element>} $selectChoice
     */
    function hideChoices($selectChoice) {
        var myThis = this;
        this.classList.remove('select-visible');
        [].map.call($selectChoice, function removeListener(that, i) {
            console.log(that, myThis, selectChoiceEventHandler);
            that.removeListener('mouseup', selectChoiceEventHandler[i]);
        });
        console.log(selectChoicesEventHandler);
        this.removeListener('mouseup', selectChoicesEventHandler);
    }
    // Events management
    document.body.addEventListener('click', delegateMouseUp, false);
})(document);
//# sourceMappingURL=form.js.map