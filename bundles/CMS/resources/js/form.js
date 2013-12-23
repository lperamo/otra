$('body').on('click', '.select', function(e){
	e.stopImmediatePropagation();
	$(this).find('.selectChoices:first').toggleClass('selectVisible');

	// Retrieves the selected value in the select
	$('.selectChoice').click(function(){
		$(this).parents('.select').find('.actualSelectValue>a').attr('data-value', this.dataset.value).text(this.innerHTML);
	});

	var hideChoices = function(){
		$(this).find('.selectChoices:first').removeClass('selectVisible');
		$(':not(.select, .selectChoices)').off('click', hideChoices);
		$('.selectChoice').off('click');
	};
	$(':not(.select, .selectChoices)').click(hideChoices);
});
