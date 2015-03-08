(function()
{
	"use strict";
	var tbody, thead, cpt,
		txtMail = '<input class="input field" type="email" required="required" title="" autocomplete="on" data-tooltip="Please complete this field."',
		txtPwd = '<input type="password" class="input field" required="required" title="" data-tooltip="Please complete this field. (at least 8 characters)"',
		txtPseudo = '<input class="input field" required="required" title="" data-tooltip="Please complete this field."',
		editBtn = '<a class="softBtn circleBorder edit _edit TTTL" data-tooltip="Makes the line editable"></a>',
		validBtn = '<a class="softBtn circleBorder validate _validate TTTL" data-tooltip="Validates the new user"></a>',
		deleteBtn = '<a class="softBtn circleBorder delete _delete TTTL" data-tooltip="Delete the user"></a>',
		options = '',
		prevSel = $('#prev'),
		nextSel = $('#next'),
		currentPageSel = $('#currentPage'),
		lastPageSel = $('#lastPage');

		for(var role in roles) { options += '<li class="selectChoice" data-value="' + roles[role].id_role + '">' + roles[role].nom + '</li>'; }
		var roleText = '<div class="select">\n\
					      <span class="fl input actualSelectValue">\n\
					        <a data-value="' + roles[0].id_role + '">' + roles[0].nom + '</a>\n\
					        <span class="fr selectArrow"></span>\n\
					      </span>\n\
					      <div class="clearBoth"></div>\n\
					      <ul class="fl selectChoices">' + options + '</ul>\n\
		    			</div>',
		U = {
		page: 1,

		addUser()
		{
			tbody.find('#trOptions').before('<tr id="_'+ cpt + '" class="editable">\n\
						<td><input type="checkbox"/></td>\n\
						<td class="mail">' + txtMail + ' /></td>\n\
						<td>' + txtPwd + '/></td>\n\
						<td>' + txtPseudo + '/></td>\n\
						<td>' + roleText + '</td>\n\
						<td> ' + validBtn + deleteBtn + '</td>\n\
					</tr>')
		},

		del()
		{
			if(confirm('Do you really want to delete this user ?'))
			{
				var content = $('#content').find('table')[0];
				U.delFn(content, this.parentNode.parentNode)
			}
			return false
		},

		delFn(content, tr, undef)
		{
			if('_' === tr.id.substr(0,1))
			{
				tr.parentNode.removeChild(tr);
				return false
			}

			$.post('/backend/ajax/users/delete', { id_user: tr.id }, function(data){
				try {
	        data = JSON.parse(data);
	        if(true === data.success)
	        	notif.run(content, data.msg, 'INFO', notif.INFO, 10000);
	        else {
	        	notif.run(content, data.msg, 'ERROR', notif.ERROR, 10000);
	        	return false
	        }
	    	} catch (e) {
	    		if(undef !== window.debug)
	        	window.debug.postLog(data);

	        return false
	    	}

				tr.parentNode.removeChild(tr)
			})
		},

		deleteAll()
		{
			var content = $('#content').find('table'),
				checkboxesChecked = content.find('input[type=checkbox]:checked');

			if(0 === checkboxesChecked.length) {
				notif.run(content[0], 'Nothing was selected !', 'WARNING', notif.WARNING, 10000);
				return false
			}

			if(confirm('Do you really want to delete those users ?'))
			{
				checkboxesChecked.closest('tr').each(function(){
					U.delFn(content[0], $(this)[0])
				})
			}

			return false
		},

		edit()
		{
			var $this = $(this),
					tr = $this.closest('tr'),
					tdBackup = tr.html(),
					mail = tr.find('.mail'),
					pwd = mail.next(),
					pseudo = pwd.next(),
					role = pseudo.next(),
					roleTxt = role.text(),
					endTd = role.next(),
					undef,
					roleId,
					trId = tr[0].id;

			for(var key in roles) {
				if(roleTxt === roles[key].nom) {
					roleId = roles[key].id;
					break
				}
			}

			if(undef === window.usersSaveData)
				window.usersSaveData = [];

			window.usersSaveData[trId] = [mail.text(), pwd.text(), pseudo.text(), roleTxt];
			$this.replaceWith('<a class="_editEnd softBtn circleBorder validate TTTL" data-tooltip="Validates the new user"></a><a class="softBtn circleBorder cancel _cancel TTTL" data-tooltip="Cancels changes"></a>');
			U.oldMail = window.usersSaveData[trId][0];
			mail.html(txtMail + 'value="' + U.oldMail + '" />');
			pwd.html(txtPwd + '/>');
			pseudo.html(txtPseudo + 'value="' + window.usersSaveData[trId][2] + '" />');
			role.html(roleText).find('a').attr('data-value', roleId).text(roleTxt)
		},

		editEnd()
		{
			var content = $('#content').find('table')[0],
				tr = $(this).parents('tr'),
				tds = tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin(tr, tds, roles, content);

			if(true !== data)
				$.post('/backend/ajax/users/edit', data, function(data){ U.afterUpdate(data, content, tds); });

			return false
		},

		validOne()
		{
			var content = $('#content').find('table')[0],
				tr = $(this).closest('tr'),
				tds = tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin(tr, tds, roles, content, true);

			if(true !== data)
				$.post('/backend/ajax/users/add', data, function(data){ U.afterUpdate(data, content, tds, true, tr); });

			return false
		},

		validAll(content)
		{
			console.log(content);
			var tr = $(this),
				tds = tr.find('td:not(:first-child,:last-child)'),
				undef;

			if(undef === tds[0].children[0])
				return false;

			var cond = 0 === tr.find('.cancel').length,
				  data = U.checkin(tr, tds, roles, content[0], cond);

				  console.log(data);

			if(true !== data)
			{
				if(cond) // We was adding something
					$.post('/backend/ajax/users/add', data, function(data){ U.afterUpdate(data, content[0], tds, true, tr); });
				else
					$.post('/backend/ajax/users/edit', data, function(data){ U.afterUpdate(data, content, tds); })
			}
		},

		launchValidAll()
		{
			backend.beforeAction('Do you really want to validate all the changes ?', U.validAll);
			return false
		},

		cancel()
		{
			var $this = $(this),
				tr = $this.closest('tr'),
				trId = tr[0].id,
				tds = tr.find('td');

			for(var i=0; i < 4; ++i) { tds.eq(i + 1).text(window.usersSaveData[trId][i]) }
			$this.after(editBtn);
			$this.prev().remove().end().remove();
			tr.find('.editEnd').remove()
		},

		checkin(tr, tds, roles, content, add)
		{
			var erreurs = '',
				mail = tds[0].children[0],
				pwd = tds[1].children[0].value,
				pseudo = tds[2].children[0].value,
				add = add || false;

			if('' === mail.value)
				erreurs += 'Mail not specified !<br>';
			else if(true === mail.validity.tooLong)
				erreurs += 'Mail too long !<br>';
			else if(true === mail.validity.typeMismatch)
				erreurs += 'Mail : Incorrect format !<br>';

			if('' === pwd)
				erreurs += 'Password not specified !<br>';

			if('' === pseudo)
				erreurs += 'Pseudo not specified !<br>';

			var temp = false,
				roleVal = tds[3].querySelectorAll('a[data-value]')[0].dataset.value;

			for(var key in roles)
			{
				if(roleVal === roles[key].id_role) {
					temp = true;
					break
				}
			}

			if(false === temp)
				erreurs += 'Invalid role !';

			if('' === erreurs)
			{
				var data =
				{
					mail: mail.value,
					pwd: pwd,
					pseudo: pseudo,
					role: roleVal
				};

				if(false === add) {
					data['oldMail'] = U.oldMail;
					data['id_user'] = tr[0].id;
				}

				return data
			} else {
				notif.run(content, erreurs, 'ERROR', notif.ERROR, 10000);
				return true
			}
		},

		formSuccess(data, content)
		{
			try
			{
        var data = JSON.parse(data),
        	undef;

        if(true === data.success)
        {
        	notif.run(content, data.msg, 'INFO', notif.INFO, 10000);
        	return (undef == data.id) ? data.pwd : [data.id, data.pwd]
        } else {
        	notif.run(content, data.msg, 'ERROR', notif.ERROR, 10000);
        	return false
        }
    	} catch (e) {
    		var undef;
    		if(undef !== window.debug)
        	window.debug.postLog(e, data);

        return false
    	}
		},

		afterUpdate(data, content, tds, add, tr)
		{
			var data = U.formSuccess(data, content),
				select = tds.find('.select'),
				undef,
				add = add || false;

			if(false === add)
				var pwd = data;
			else {
				tr[0].id = data[0];
				var pwd = data[1]
			}

			if(undef === pwd)
				return false;

			tds.find('input').each(function(i) {
				this.outerHTML = 1 === i ? pwd : this.value
			});

			select.replaceWith(select[0].querySelectorAll('a').textContent);
			tds.last()[0].nextElementSibling.innerHTML = editBtn + deleteBtn
		},

		search(e)
		{
			if(13 !== e.keyCode)
				return false;

			U.refresh('search')
		},

		refresh(type, undef)
		{
			var options = tbody.find('#trOptions'),
				firstTr = tbody.find('tr:first'),
				tds = firstTr.find('td'),
				limit = $('#limit');

			$.post(
				'/backend/ajax/users/search',
				{
					mail: tds.eq(1).find('input').val().trim(),
					pseudo: tds.eq(3).find('input').val().trim(),
					role: tds.eq(4).find('input').val().trim(),
					type: type,
					limit: limit.find('.actualSelectValue>a')[0].dataset.value,
					prev: limit[0].dataset.first,
					last: limit[0].dataset.last
				},
				function(data)
				{
					try
					{
						var data = JSON.parse(data);

						if(true === data.success) {
							tbody.find('tr:first').nextUntil('#trOptions').eq(0).after(data.msg).end().remove();
							limit.attr({'data-first': data.first, 'data-last': data.last})
						} else if(false === data.success)
							notif.run(content, data.msg, 'ERROR', notif.ERROR, 10000)
						else
							$('html').html(data.msg)
					} catch(e)
					{
						if(undef !== window.debug)
	        		window.debug.postLog(e, data);

	        	return false
					}
				}
			)
		},

		prev()
		{
			U.refresh('prev');
			if(1 === --U.page)
				prevSel.addClass('disabled');

			if(U.page < lastPageSel.text())
				nextSel.removeClass('disabled');

			currentPageSel.text(U.page)
		},

		next()
		{
			U.refresh('next');
			if(1 < ++U.page)
				prevSel.removeClass('disabled');

			if(U.page == lastPageSel.text())
				nextSel.addClass('disabled');

			currentPageSel.text(U.page)
		},

		events()
		{
			tbody.on('mouseup', '#usersAll', backend.selectAll)
				.on('mouseup', 'td:nth-child(1):not(.options,.final)', backend.triggerCheckbox)
				.on('click', '#add', U.addUser)
				.on('click', 'tr.editable>td', U.focusSelect)
				.on('click', '._edit', U.edit)
				.on('click', '._editEnd', U.editEnd)
				.on('click', '._delete', U.del)
				.on('click', '#usersDelAll', U.deleteAll)
				.on('click', '._validate', U.validOne)
				.on('click', '#usersValAll', U.launchValidAll)
				.on('click', '._cancel', U.cancel)
				.on('click',  '#prev:not(.disabled)', U.prev)
				.on('click',  '#next:not(.disabled)', U.next)
				.on('keyup',  '.search', U.search)
		},

		focusSelect(){	$(this).find('input, select').focus() }
	};

	window.initUsers = function() {
		tbody = $('#usersBody'), thead = $('#usersHeader'), cpt = 0;
		U.events()
	};

	window.initUsers()
})();
