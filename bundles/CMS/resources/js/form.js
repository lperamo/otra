(function()
{
	"use strict";

	var selectChoice = function selectChoice()
	{
		$(this).parents('.select').find('.actualSelectValue>a').attr('data-value', this.dataset.value).text(this.innerHTML);
	},

	hideChoices = function hideChoices()
	{
		$(this).find('.selectChoices:first').removeClass('selectVisible');
		$(':not(.select, .selectChoices)').off('click', hideChoices);
		$('.selectChoice').off('click');
	},

	selectClick = function selectClick(e)
	{
		e.stopImmediatePropagation();
		$(this).find('.selectChoices:first').toggleClass('selectVisible');

		// Retrieves the selected value in the select
		$('.selectChoice').click(selectChoice);
		$(':not(.select, .selectChoices)').click(hideChoices);
	};

	$('body').on('click', '.select', selectClick);
})();
