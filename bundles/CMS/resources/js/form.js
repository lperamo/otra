(function()
{
	"use strict";

	var selectChoice = function selectChoice()
	{
		$(this).parents('.select').find('.actual-select-value__link').attr('data-value', this.dataset.value).text(this.innerHTML);
	},

	hideChoices = function hideChoices()
	{
		$(this).find('.select-choices:first').removeClass('select-visible');
		$(':not(.select, .select-choices)').off('click', hideChoices);
		$('.select-choice').off('click');
	},

	selectClick = function selectClick(e)
	{
		e.stopImmediatePropagation();
		$(this).find('.select-choices:first').toggleClass('select-visible');

		// Retrieves the selected value in the select
		$('.select-choice').click(selectChoice);
		$(':not(.select, .select-choices)').click(hideChoices);
	};

	$('body').on('click', '.select', selectClick);
})();
