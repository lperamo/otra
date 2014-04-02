(function(){
	"use strict";
	var body, tbody, thead, cpt,
		txtMail = '<input class="input field" type="email" required="required" title="" data-tooltip="Please complete this field."',
		txtText = '<input class="input field" required="required" title="" data-tooltip="Please complete this field." ',
		options = '';
		// roles = window.roles;
		for(var role in roles) { options += '<li class="selectChoice" data-value="' + roles[role].id_role + '">' + roles[role].nom + '</li>';	}
		var roleText = '<div class="select">\n\
					      <span class="fl input actualSelectValue">\n\
					        <a data-value="' + roles[0].nom + '">' + roles[0].nom + '</a>\n\
					        <span class="fr selectArrow"></span>\n\
					      </span>\n\
					      <div class="clearBoth"></div>\n\
					      <ul class="fl selectChoices">' + options + '</ul>\n\
		    			</div>',
		U = {
		selectAll : function(){
			tbody.find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
		},
		addUser : function(){
			tbody.find('#trOptions').before('<tr id="_'+ cpt + '" class="editable">\n\
						<td><input type="checkbox"/></td>\n\
						<td class="mail">' + txtMail + ' /></td>\n\
						<td>' + txtText + '/></td>\n\
						<td>' + txtText + '/></td>\n\
						<td>' + roleText + '</td>\n\
						<td>\n\
							<input type="submit" value="Validate" data-tooltip="Validates the new user" class="softBtn validateButton rightPad edit">\n\
							<span class="softBtn delete" data-tooltip="Delete the user"></span>\n\
						</td>\n\
					</tr>');
		},
		del : function(){},
		deleteAll : function(){},
		edit : function(){
			var $this = $(this),
					tr = $this.parents('tr'),
					tdBackup = tr.html(),
					mail = tr.find('.mail'),
					pwd = mail.next(),
					pseudo = pwd.next(),
					role = pseudo.next(),
					roleTxt = role.text(),
					endTd = role.next();

			$this.text('Cancel');
			mail.html(txtMail + 'value="' + mail.text() + '" />');
			pwd.html(txtText + '/>');
			pseudo.html(txtText + 'value="' + pseudo.text() + '" />');
			role.html(roleText).find('a').attr('data-value', roleTxt).text(roleTxt);
			$('<span class="softBtn validateButton"><input type="submit" class="rightPad edit" value="Validate" data-tooltip="Validates the user"></span>').prependTo(endTd);
			$('form').submit({
				id_user: tr.attr('id'),
				mail: mail.find('input').val(),
				pwd: pwd.find('input').val(),
				pseudo: pseudo.find('input').val(),
				role: role.find('input').attr('data-value')
			}, U.validOne);
		},
		events: function(){
			thead.on('click','#all', U.selectAll);
			tbody.on('click', '#addBtn', U.addUser)
				.on('click', 'tr.editable>td', U.focusSelect)
				.find('#delAllBtn').click(U.deleteAll);
			tbody.find('.edit').click(U.edit);
			tbody.find('.delete').click(U.del);
		},
		focusSelect : function(){	$(this).find('input, select').focus(); },
		validOne : function(e){ console.log(e.data, 'valider'); $.post('validate', {}, U.validOneFn); },
		validOneFn : function(){}
	};

	window.initUsers = function(){
		tbody = $('#usersBody'), thead = $('#usersHeader'), cpt = 0;
		U.events();
	};

	window.initUsers();
})();
