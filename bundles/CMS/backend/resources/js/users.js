/**
 * closure compiler annotation
 * @suppress {checkVars, missingProperties}
 */
(function(doc, window)
{
	"use strict";
	var $$tbody, cpt,
		txtMail = '<input class="input field" type="email" required="required" title="" autocomplete="on" data-tooltip="Please complete this field."',
		txtPwd = '<input type="password" class="input field" required="required" title="" data-tooltip="Please complete this field. (at least 8 characters)"',
		txtPseudo = '<input class="input field" required="required" title="" data-tooltip="Please complete this field."',
		editBtn = '<a class="softBtn circleBorder edit _edit TTTL" data-tooltip="Makes the line editable"></a>',
		validBtn = '<a class="softBtn circleBorder validate _validate TTTL" data-tooltip="Validates the new user"></a>',
		deleteBtn = '<a class="softBtn circleBorder delete _delete TTTL" data-tooltip="Delete the user"></a>',
		options = '',
		$prev,
		$next,
		$currentPage,
		$lastPage,
		$pageInd,
		$limit,
		$limitValue;

		for(var role in roles) {
			options += '<li class="selectChoice" data-value="' + roles[role].id + '">' + roles[role].nom + '</li>';
		}

		var roleText = '<div class="select">' +
					      '<span class="fl input actualSelectValue">' +
					        '<a data-value="' + roles[0].id + '">' + roles[0].nom + '</a>' +
					        '<span class="fr selectArrow"></span>' +
					      '</span>'+
					      '<div class="clearBoth"></div>'+
					      '<ul class="fl selectChoices">' + options + '</ul>'+
		    			'</div>',
		U = {
		page: 1,

		addUser()
		{
			$$tbody.find('#trOptions').before('<tr id="_'+ cpt + '" class="editable">'+
						'<td><input id="chk__' + cpt + '" type="checkbox" role="checkbox" class="noLabel" /><label for="chk__' + cpt + '"></td>'+
						'<td class="mail">' + txtMail + ' /></td>'+
						'<td>' + txtPwd + '/></td>'+
						'<td>' + txtPseudo + '/></td>'+
						'<td>' + roleText + '</td>'+
						'<td> ' + validBtn + deleteBtn + '</td>'+
					'</tr>');

			document.querySelector('tbody>tr:nth-last-child(2)>td:nth-of-type(2)>input').focus()
		},

		del()
		{
			var that = this;
			lightbox.confirm('Do you really want to delete this user ?', function()
				{
					U.delFn($('#content').find('table')[0], that.parentNode.parentNode);
				}
			);

			return false
		},

		/**
		 * @suppress {checkTypes}
		 */
		delFn(content, tr, undef)
		{
			if('_' === tr.id.substr(0,1))
			{
				tr.parentNode.removeChild(tr);
				return false
			}

			$.post('/backend/ajax/users/delete', { id_user: tr.id }, function(data)
			{
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

	    	tr.parentNode.removeChild(tr);

	    	// We update the paging informations
	    	$lastPage.nextSibling.textContent = ' (' + data.count + ' users)';
	    	$pageInd.dataset.realcount = data.count;

	    	if($pageInd.dataset.actualcount > data.count)
	    		$pageInd.dataset.actualcount = data.count

	    	// if there are no more users on this page, we remove one page and we pass to the previous page of users
	    	if(2 === $$tbody[0].querySelectorAll('tr').length && 1 < +$currentPage.textContent)
	    	{
	    		$lastPage.textContent = +$lastPage.textContent - 1;
	    		U.prev()
	    	}
			})
		},

		deleteAll()
		{
			var content = doc.getElementById('content').querySelector('table'),
				checkboxesChecked = $(content).find('input[type=checkbox]:checked');

			if(0 === checkboxesChecked.length) {
				notif.run(content, 'Nothing was selected !', 'WARNING', notif.WARNING, 10000);
				return false
			}

			if(confirm('Do you really want to delete those users ?'))
			{
				checkboxesChecked.closest('tr').each(function(){
					U.delFn(content, this)
				})
			}

			return false
		},

		/* Allow editing the user line */
		edit()
		{
			var $$tr = $(this).closest('tr'),
					$mail = $$tr[0].querySelector('.mail'),
					$pwd = $mail.nextElementSibling,
					$pseudo = $pwd.nextElementSibling,
					$role = $pseudo.nextElementSibling,
					roleTxt = $role.textContent,
					undef,
					roleId,
					trId = $$tr[0].id;

			for(var key in roles)
			{
				if(roleTxt === roles[key].nom) {
					roleId = roles[key].id;
					break
				}
			}

			if(undef === window.usersSaveData)
				window.usersSaveData = [];

			window.usersSaveData[trId] = [$mail.textContent, $pwd.textContent, $pseudo.textContent, $role.textContent];
			this.outerHTML = '<a class="_editEnd softBtn circleBorder validate TTTL" data-tooltip="Validates the new user"></a><a class="softBtn circleBorder cancel _cancel TTTL" data-tooltip="Cancels changes"></a>';
			U.oldMail = window.usersSaveData[trId][0];
			U.oldPseudo = window.usersSaveData[trId][2];
			$mail.innerHTML = txtMail + 'value="' + U.oldMail + '" />';
			$pwd.innerHTML = txtPwd + '/>';
			$pseudo.innerHTML = txtPseudo + 'value="' + U.oldPseudo + '" />';
			$role.innerHTML = roleText;

			var $roleA = $role.querySelector('a');
			$roleA.dataset.value = roleId;
			$roleA.textContent = roleTxt
		},

		/** Validate the edited user line */
		editEnd()
		{
			var $content = $('#content').find('table')[0],
				$$tr = $(this).closest('tr'),
				$$tds = $$tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin($$tr, $$tds, roles, content);

			if(true !== data)
				$.post('/backend/ajax/users/edit', data, function(data)
				{
					U.afterUpdate(data, $content, $$tds)
				});

			return false
		},

		/** Validate the new user line */
		validOne()
		{
			var $content = $('#content').find('table')[0],
				$$tr = $(this).closest('tr'),
				$$tds = $$tr.find('td:not(:first-child,:last-child)'),
				data = U.checkin($$tr, $$tds, roles, $content, true);

			if(true !== data)
				$.post('/backend/ajax/users/add', data, function(data)
				{
					U.afterUpdate(data, $content, $$tds, true, $$tr);
				});

			return false
		},

		/** Validate all the selected user lines being edited */
		validAll($$content)
		{
			var $$tr = $(this),
				$$tds = $$tr.find('td:not(:first-child,:last-child)'),
				undef;

			if(undef === $$tds[0].children[0])
				return false;

			var cond = 0 === $$tr.find('.cancel').length,
				  data = U.checkin($$tr, $$tds, roles, $$content[0], cond);

			if(true !== data)
			{
				if(cond) // We was adding something
					$.post('/backend/ajax/users/add', data, function(data)
					{
						U.afterUpdate(data, $$content[0], $$tds, true, $$tr)
					});
				else
					$.post('/backend/ajax/users/edit', data, function(data)
					{
						U.afterUpdate(data, $$content, $$tds)
					})
			}
		},

		launchValidAll()
		{
			backend.beforeAction('Do you really want to validate all the changes ?', U.validAll);
			return false
		},

		cancel()
		{
			var $$this = $(this),
				$$tr = $$this.closest('tr'),
				trId = $$tr[0].id,
				$$tds = $$tr.find('td');

			for(var i = 0; i < 4; ++i) { $$tds.eq(i + 1).text(window.usersSaveData[trId][i]) }
			$$this[0].insertAdjacentHTML('afterend', editBtn);
			$$this.prev().remove().end().remove();
			$$tr.find('.editEnd').remove()
		},

		/** Check form informations before send them to PHP */
		checkin($$tr, $$tds, roles, $content, add)
		{
			var erreurs = '',
				$mail = $$tds[0].children[0],
				pwd = $$tds[1].children[0].value,
				pseudo = $$tds[2].children[0].value,
				add = add || false;

			if('' === $mail.value)
				erreurs += 'Mail not specified !<br>';
			else if(true === $mail.validity.tooLong)
				erreurs += 'Mail too long !<br>';
			else if(true === $mail.validity.typeMismatch)
				erreurs += 'Mail : Incorrect format !<br>';

			if('' === pwd)
				erreurs += 'Password not specified !<br>';

			if('' === pseudo)
				erreurs += 'Pseudo not specified !<br>';

			var temp = false,
				roleVal = $$tds[3].querySelector('a[data-value]').dataset.value;

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
					mail: $mail.value,
					pwd: pwd,
					pseudo: pseudo,
					role: roleVal
				};

				if(false === add)
				{
					data['oldMail'] = U.oldMail;
					data['oldPseudo'] = U.oldPseudo;
					data['id_user'] = $$tr[0].id
				}

				return data
			} else {
				notif.run($content, erreurs, 'ERROR', notif.ERROR, 10000);
				return true
			}
		},

		/** Triggered after we comes back from the PHP (from afterUpdate callback) */
		formSuccess(data, $content)
		{
			try
			{
        var data = JSON.parse(data),
        		undef;

        if(true === data.success)
        {
        	notif.run($content, data.msg, 'INFO', notif.INFO, 10000);
        	return data.id;
        } else {
        	notif.run($content, data.msg, 'ERROR', notif.ERROR, 10000);
        	return false
        }
    	} catch (e) {
    		var undef;
    		undef !== window.debug && window.debug.postLog(e, data);

        return false
    	}
		},

		/**
		 * Triggered after we comes back from the PHP
		 *
		 * @param  {Object} data     [description]
		 * @param  {Object} $content [description]
		 * @param  {Object} $$tds    [description]
		 * @param  {bool} 	add      [description]
		 * @param  {Object} $$tr     [description]
		 * @return {Object}          [description]
		 */
		afterUpdate(data, $content, $$tds, add, $$tr)
		{
			var data = U.formSuccess(data, $content);
			if(false === data) return false;

			var $$select = $$tds.find('.select'),
				undef,
				add = add || false;

			if(false !== add)
				$$tr[0].id = data;

			$$tds.find('input').each(function(i) {
				this.outerHTML = 1 === i ? '********' : this.value
			});

			$$select.replaceWith($$select[0].querySelector('a').textContent);
			$$tds.last()[0].nextElementSibling.innerHTML = editBtn + deleteBtn;

			// if there are too much users on this page, we add a page and we pass to the next page of users
			if(true === add)
			{
				// We update the paging informations
				++$pageInd.dataset.realcount;
				$lastPage.nextSibling.textContent = ' (' + $pageInd.dataset.realcount + ' users)';

				// If adds a new user adds an page then we pass to this new page.
				if(2 + +$limitValue.textContent < $$tbody[0].querySelectorAll('tr').length)
				{
					U.next();

					// The last page cannot be less than the actual !!
					if(+$currentPage.textContent > +$lastPage.textContent)
						$lastPage.textContent = $currentPage.textContent
				} else // else ... we change the data-last attribute
					$limit.dataset.last = data
			}
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
			var $$firstTr = $$tbody.find('tr:first'),
				$$tds = $$firstTr.find('td'),
				mail = $$tds.eq(1)[0].querySelector('input').value.trim(),
				pseudo = $$tds.eq(3)[0].querySelector('input').value.trim(),
				role = $$tds.eq(4)[0].querySelector('input').value.trim(),
				limitValue = $limitValue.textContent,
				pageIndDataset = $pageInd.dataset;

			if('search' === type && pageIndDataset.realcount === pageIndDataset.actualcount && '' === mail + pseudo + role)
				return false;

			var options = $$tbody.find('#trOptions');

			$.post(
				'/backend/ajax/users/search',
				{
					mail: mail,
					pseudo: pseudo,
					role: role,
					type: type,
					limit: limitValue,
					prev: $limit.dataset.first,
					last: $limit.dataset.last
				},
				function(data)
				{
					try
					{
						var data = JSON.parse(data);

						if(true === data.success)
						{
							if('search' === type)
							{
								pageIndDataset.actualcount = data.count;

								// Updates ... Page 1 / 2 (1 user) ... section
								$pageInd.querySelector('#lastPage').innerHTML = Math.ceil(data.count / limitValue);
								var $childNodes = $pageInd.childNodes;
								$childNodes.item($childNodes.length - 1).textContent = ' (' + data.count + (2 > data.count ? ' user)' : ' users)');
							}

							var $$trFirst = $$tbody.find('tr:first'),
									$$thingsToReplace = $$trFirst.nextUntil('#trOptions');

							// Fixes the bug when the previous search has put nothing...then there is nothing to replace
							if(0 === $$thingsToReplace.length)
								$$trFirst.after(data.msg);
							else
								$$thingsToReplace.eq(0).after(data.msg).end().remove();

							$limit.dataset.first = data.first;
							$limit.dataset.last = data.last
						} else if(false === data.success)
							notif.run($$firstTr, data.msg, 'ERROR', notif.ERROR, 10000)
						else
							doc.getElementByTagName('html')[0].innerHTML = data.msg
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
				$prev.classList.add('disabled');

			if(U.page < $lastPage.textContent)
				$next.classList.remove('disabled');

			$currentPage.textContent = U.page
		},

		next()
		{
			U.refresh('next');
			if(1 < ++U.page)
				$prev.classList.remove('disabled');

			if(U.page == $lastPage.textContent)
				$next.classList.add('disabled');

			$currentPage.textContent = U.page
		},

		events()
		{
			$$tbody.on('mouseup', '#usersAll', backend.selectAll)
				.on('mouseup', 'td:nth-child(1):not(.options)', backend.triggerCheckbox)
				.on('mouseup', '#add', U.addUser)
				.on('mouseup', 'tr.editable>td', U.focusSelect)
				.on('mouseup', '._edit', U.edit)
				.on('mouseup', '._editEnd', U.editEnd)
				.on('mouseup', '._delete', U.del)
				.on('mouseup', '#usersDelAll', U.deleteAll)
				.on('mouseup', '._validate', U.validOne)
				.on('mouseup', '#usersValAll', U.launchValidAll)
				.on('mouseup', '._cancel', U.cancel)
				.on('mouseup',  '#prev:not(.disabled)', U.prev)
				.on('mouseup',  '#next:not(.disabled)', U.next)
				.on('keyup',  '.search', U.search)
		},

		focusSelect(){	$(this).find('input, select').focus() }
	};

	window.initUsers = function() {
		$$tbody = $('#usersBody'),
		cpt = 0,
		$prev = doc.getElementById('prev'),
		$next = doc.getElementById('next'),
		$currentPage = doc.getElementById('currentPage'),
		$lastPage = doc.getElementById('lastPage'),
		$pageInd = doc.getElementById('pageInd'),
		$limit = doc.getElementById('limit'),
		$limitValue = doc.getElementById('limitValue');
		U.events()
	};

	$(function() { window.initUsers()	})
})(document, window);
