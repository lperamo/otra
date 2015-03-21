(function(doc, window)
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
		prevSel = doc.getElementById('prev'),
		nextSel = doc.getElementById('next'),
		currentPageSel = doc.getElementById('currentPage'),
		lastPageSel = doc.getElementById('lastPage');

		for(var role in roles) {
			options += '<li class="selectChoice" data-value="' + roles[role].id + '">' + roles[role].nom + '</li>';
		}

		var roleText = '<div class="select">\n\
					      <span class="fl input actualSelectValue">\n\
					        <a data-value="' + roles[0].id + '">' + roles[0].nom + '</a>\n\
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
						<td><input id="chk__' + cpt + '" type="checkbox" role="checkbox" class="noLabel" /><label for="chk__' + cpt + '"></td>\n\
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
					U.delFn(content[0], this)
				})
			}

			return false
		},

		/* Allow editing the user line */
		edit()
		{
			var tr = $(this).closest('tr'),
					mail = tr[0].querySelector('.mail'),
					pwd = mail.nextElementSibling,
					pseudo = pwd.nextElementSibling,
					role = pseudo.nextElementSibling,
					roleTxt = role.textContent,
					undef,
					roleId,
					trId = tr[0].id;

			for(var key in roles)
			{
				if(roleTxt === roles[key].nom) {
					roleId = roles[key].id;
					break
				}
			}

			if(undef === window.usersSaveData)
				window.usersSaveData = [];

			window.usersSaveData[trId] = [mail.textContent, pwd.textContent, pseudo.textContent, role.textContent];
			this.outerHTML = '<a class="_editEnd softBtn circleBorder validate TTTL" data-tooltip="Validates the new user"></a><a class="softBtn circleBorder cancel _cancel TTTL" data-tooltip="Cancels changes"></a>';
			U.oldMail = window.usersSaveData[trId][0];
			U.oldPseudo = window.usersSaveData[trId][2];
			mail.innerHTML = txtMail + 'value="' + U.oldMail + '" />';
			pwd.innerHTML = txtPwd + '/>';
			pseudo.innerHTML = txtPseudo + 'value="' + U.oldPseudo + '" />';
			role.innerHTML = roleText;

			var roleA = role.querySelector('a');
			roleA.dataset.value = roleId;
			roleA.textContent = roleTxt
		},

		/** Validate the edited user line */
		editEnd()
		{
			var content = $('#content').find('table')[0],
				tr = $(this).closest('tr'),
				tds = tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin(tr, tds, roles, content);

			if(true !== data)
				$.post('/backend/ajax/users/edit', data, function(data){
					U.afterUpdate(data, content, tds)
				});

			return false
		},

		/** Validate the new user line */
		validOne()
		{
			var content = $('#content').find('table')[0],
				tr = $(this).closest('tr'),
				tds = tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin(tr, tds, roles, content, true);

			if(true !== data)
				$.post('/backend/ajax/users/add', data, function(data){
					U.afterUpdate(data, content, tds, true, tr)
				});

			return false
		},

		/** Validate all the selected user lines being edited */
		validAll(content)
		{
			var tr = $(this),
				tds = tr.find('td:not(:first-child,:last-child)'),
				undef;

			if(undef === tds[0].children[0])
				return false;

			var cond = 0 === tr.find('.cancel').length,
				  data = U.checkin(tr, tds, roles, content[0], cond);

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

			for(var i = 0; i < 4; ++i) { tds.eq(i + 1).text(window.usersSaveData[trId][i]) }
			$this[0].insertAdjacentHTML('afterend', editBtn);
			$this.prev().remove().end().remove();
			tr.find('.editEnd').remove()
		},

		/** Check form informations before send them to PHP */
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
				roleVal = tds[3].querySelector('a[data-value]').dataset.value;

			for(var key in roles)
			{
				if(roleVal === roles[key].id) {
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

				if(false === add)
				{
					data['oldMail'] = U.oldMail;
					data['oldPseudo'] = U.oldPseudo;
					data['id_user'] = tr[0].id
				}

				return data
			} else {
				notif.run(content, erreurs, 'ERROR', notif.ERROR, 10000);
				return true
			}
		},

		/** Triggered after we comes back from the PHP (from afterUpdate callback) */
		formSuccess(data, content)
		{
			try
			{
        var data = JSON.parse(data),
        		undef;

        if(true === data.success)
        {
        	notif.run(content, data.msg, 'INFO', notif.INFO, 10000);
        	return undef == data.id ? data.pwd : [data.id, data.pwd]
        } else {
        	notif.run(content, data.msg, 'ERROR', notif.ERROR, 10000);
        	return false
        }
    	} catch (e) {
    		var undef;
    		undef !== window.debug && window.debug.postLog(e, data);

        return false
    	}
		},

		/** Triggered after we comes back from the PHP */
		afterUpdate(data, content, tds, add, tr)
		{
			var data = U.formSuccess(data, content);
			if(false === data) return false;

			var select = tds.find('.select'),
				undef,
				add = add || false;

			if(false === add)
				var pwd = data;
			else {
				tr[0].id = data[0];
				var pwd = data[1];
			}

			if(undef === pwd)
				return false;

			tds.find('input').each(function(i) {
				this.outerHTML = 1 === i ? pwd : this.value
			});

			select.replaceWith(select[0].querySelector('a').textContent);
			tds.last()[0].nextElementSibling.innerHTML = editBtn + deleteBtn
		},

		search(e)
		{
			if(13 !== e.keyCode)
				return false;

			U.refresh('search')
		},

		/** Refreshes the table. We can come from the pagination or a search. */
		refresh(type, undef)
		{
			var options = tbody.find('#trOptions'),
				firstTr = tbody.find('tr:first'),
				tds = firstTr.find('td'),
				limit = doc.getElementById('limit');

			$.post(
				'/backend/ajax/users/search',
				{
					mail: tds.eq(1)[0].querySelector('input').value.trim(),
					pseudo: tds.eq(3)[0].querySelector('input').value.trim(),
					role: tds.eq(4)[0].querySelector('input').value.trim(),
					type: type,
					limit: limit.querySelector('.actualSelectValue>a').textContent,
					prev: limit.dataset.first,
					last: limit.dataset.last
				},
				function(data)
				{
					try
					{
						var data = JSON.parse(data);

						if(true === data.success) {
							tbody.find('tr:first').nextUntil('#trOptions').eq(0).after(data.msg).end().remove();
							$(limit).attr({'data-first': data.first, 'data-last': data.last})
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
				prevSel.classList.add('disabled');

			if(U.page < lastPageSel.textContent)
				nextSel.classList.remove('disabled');

			currentPageSel.textContent = U.page
		},

		next()
		{
			U.refresh('next');
			if(1 < ++U.page)
				prevSel.classList.remove('disabled');

			if(U.page == lastPageSel.textContent)
				nextSel.classList.add('disabled');

			currentPageSel.textContent = U.page
		},

		events()
		{
			tbody.on('mouseup', '#usersAll', backend.selectAll)
				.on('mouseup', 'td:nth-child(1):not(.options)', backend.triggerCheckbox)
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
})(document, window);
