/**
 * closure compiler annotation
 * @suppress {checkVars, missingProperties}
 */
// let roles : any = '';
(function (doc, window, undef) {
    'use strict';
    var $tbody, cpt, 
    /** HTML FOR TABLE EDITION MODE */
    beginField = '<input class="input field" required="required" title="" ', txtMail = beginField + 'type="email" autocomplete="on" data-tooltip="Please complete this field."', txtPwd = beginField + 'type="password" data-tooltip="Please complete this field. (at least 8 characters)"', txtPseudo = beginField + 'data-tooltip="Please complete this field."', 
    /** HTML FOR BUTTONS */
    beginBtn = '<a class="soft-btn circle-border tttl ', editBtn = beginBtn + 'edit _edit " data-tooltip="Makes the line editable"></a>', validBtn = beginBtn + 'validate _validate" data-tooltip="Validates the new user"></a>', deleteBtn = beginBtn + 'delete _delete" data-tooltip="Delete the user"></a>', 
    /** PAGINATION ELEMENTS */
    $prev, $next, $currentPage, $lastPage, $pageInd, // => Page n / max (x users)
    $limit, $limitValue, 
    /** TEMPORARY DATA */
    usersSaveData, oldMail, oldPseudo, page = 1, 
    /** TEMPLATES */
    $templateUser, $templateRoleTxt, $templateUserValidation;
    /**
     * Replace the actual page of users by the previous one.
     */
    function prev() {
        refresh('prev');
        if (1 === --page)
            $prev.classList.add('disabled');
        if (page < parseInt($lastPage.textContent))
            $next.classList.remove('disabled');
        $currentPage.textContent = page.toString();
    }
    /**
     *
     * @param {string}           data
     * @param {HTMLTableElement} content
     * @param {HTMLRowElement}   tr
     */
    function delFnSuccess(data, content, tr) {
        var jsonData;
        try {
            jsonData = JSON.parse(data);
            if (true === jsonData.success)
                LIB_NOTIF.run(content, jsonData.msg, 'INFO', LIB_NOTIF.INFO, 10000);
            else {
                LIB_NOTIF.run(content, jsonData.msg, 'ERROR', LIB_NOTIF.ERROR, 10000);
                return;
            }
        }
        catch (e) {
            if (undef !== FWK_DEBUG)
                FWK_DEBUG.postLog(data);
            return;
        }
        tr.parentNode.removeChild(tr);
        // We update the paging informations
        $lastPage.nextSibling.textContent = ' (' + jsonData.count + ' users)';
        $pageInd.dataset.realcount = jsonData.count;
        if ($pageInd.dataset.actualcount > jsonData.count)
            $pageInd.dataset.actualcount = jsonData.count;
        // if there are no more users on this page, we remove one page and we pass to the previous page of users
        if (2 === $tbody[0].querySelectorAll('tr').length && 1 < +$currentPage.textContent) {
            $lastPage.textContent = (+$lastPage.textContent - 1).toString();
            prev();
        }
    }
    function delFnError() {
        // TODO implement it
    }
    /**
     * Adds an additional line in the table in order to let the user fill the new user fields.
     */
    function addUser() {
        // Creates the user row from the template inside the HTML
        var fragment = new DocumentFragment();
        fragment.appendChild(document.importNode($templateUser.content, true));
        fragment.getElementById('template-user-tr').id = '_' + cpt;
        fragment.getElementById('chk').id = 'chk__' + cpt;
        // Adds the user row
        $tbody.querySelector('#tr-options').before(fragment);
        document.querySelector('tbody>tr:nth-last-child(2)>td:nth-of-type(2)>input').focus();
    }
    /**
     * Asks to the user if he really wants to delete the line he chose and then delete it or not.
     */
    function del() {
        var that = this;
        LIB_LIGHTBOX.confirm('Do you really want to delete this user ?', function () {
            delFn(doc.getElementById('content').querySelectorAll('table')[0], that.parentNode.parentNode);
        });
        return false;
    }
    /**
     * Asks to the user if he really wants to delete all the selected lines he chose and then delete them or not.
     */
    function deleteAll() {
        var $content = doc.getElementById('content').querySelector('table'), $checkboxesChecked = $content.querySelectorAll('input[type=checkbox]:checked:not(#users-all)');
        if (0 === $checkboxesChecked.length) {
            LIB_NOTIF.run($content, 'Nothing was selected !', 'WARNING', LIB_NOTIF.WARNING, 10000);
            return false;
        }
        if (confirm('Do you really want to delete those users ?')) {
            [].map.call($checkboxesChecked, function eachOnCheckboxes() {
                [].map.call(this.closest('tr'), function delByCheckbox() {
                    delFn($content, this);
                });
            });
        }
        return false;
    }
    /**
     * Delete a given user.
     *
     * @param content
     * @param tr
     *
     * @suppress {checkTypes}
     *
     * @returns {boolean}
     */
    function delFn(content, tr) {
        if ('_' === tr.id.substr(0, 1)) {
            tr.parentNode.removeChild(tr);
            return false;
        }
        toolsBase.fetch({
            callback: delFnSuccess.bind(null, content, tr),
            errorCallback: delFnError,
            href: '/backend/ajax/users/delete',
            method: 'POST',
            parameters: { id_user: tr.id }
        });
    }
    /**
     * Allow editing the selected user line
     */
    function edit() {
        var $tr = this.closest('tr'), $mail = $tr[0].querySelector('.mail'), $pwd = $mail.nextElementSibling, $pseudo = $pwd.nextElementSibling, $role = $pseudo.nextElementSibling, roleTxt = $role.textContent, roleId, trId = $tr[0].id;
        // TODO uses $roleTxt
        if (undef === usersSaveData)
            usersSaveData = [];
        usersSaveData[trId] = [$mail.textContent, $pwd.textContent, $pseudo.textContent, $role.textContent];
        this.outerHTML = $templateUserValidation.content;
        oldMail = usersSaveData[trId][0];
        oldPseudo = usersSaveData[trId][2];
        $mail.innerHTML = txtMail + 'value="' + oldMail + '" />';
        $pwd.innerHTML = txtPwd + '/>';
        $pseudo.innerHTML = txtPseudo + 'value="' + oldPseudo + '" />';
        $role.innerHTML = '';
        $role.appendChild($templateRoleTxt);
        var $roleA = $role.querySelector('a');
        $roleA.dataset.value = roleId;
        $roleA.textContent = roleTxt;
    }
    /**
     * Validates the edited user line
     */
    function editEnd() {
        var $content = document.getElementById('content').querySelectorAll('table')[0], $tr = this.closest('tr'), $tds = $tr.querySelectorAll('td:not(:first-child,:last-child)'), data = checkin($tr, $tds, roles, $content);
        if (true !== data)
            toolsBase.fetch({
                callback: function (thatData) {
                    afterUpdate(thatData, $content, $tds);
                },
                // errorCallback: delFnError,
                href: '/backend/ajax/users/edit',
                method: 'POST',
                parameters: data
            });
        return false;
    }
    /**
     * Validates the new user line
     */
    function validOne() {
        var $content = document.getElementById('content').querySelectorAll('table')[0], $tr = this.closest('tr'), $tds = $tr.find('td:not(:first-child,:last-child)'), data = checkin($tr, $tds, roles, $content, true);
        if (true !== data)
            toolsBase.fetch({
                callback: function (thatData) {
                    afterUpdate(thatData, $content, $tds, true, $tr);
                },
                // errorCallback: delFnError,
                href: '/backend/ajax/users/add',
                method: 'POST',
                parameters: data
            });
        return false;
    }
    /**
     * Validate all the selected user lines being edited
     */
    function validAll($content) {
        var $tr = this, $tds = $tr.querySelectorAll('td:not(:first-child,:last-child)');
        if (undef === $tds[0].children[0])
            return;
        var cond = 0 === $tr.querySelectorAll('.cancel').length, data = checkin($tr, $tds, roles, $content[0], cond);
        if (true !== data) {
            if (cond)
                toolsBase.fetch({
                    callback: function (thatData) {
                        afterUpdate(thatData, $content[0], $tds, true, $tr);
                    },
                    // errorCallback: delFnError,
                    href: '/backend/ajax/users/add',
                    method: 'POST',
                    parameters: data
                });
            else
                toolsBase.fetch({
                    callback: function (thatData) {
                        afterUpdate(thatData, $content, $tds);
                    },
                    // errorCallback: delFnError,
                    href: '/backend/ajax/users/edit',
                    method: 'POST',
                    parameters: data
                });
        }
    }
    /**
     * Validates all the changes made in the user table in edition mode
     *
     * @returns {boolean}
     */
    function launchValidAll() {
        backend.beforeAction('Do you really want to validate all the changes ?', validAll);
        return false;
    }
    /**
     * Exiting edition mode.
     */
    function cancel() {
        var $tr = this.closest('tr'), trId = $tr[0].id, $tds = $tr.querySelectorAll('td');
        for (var i = 0; i < 4; ++i) {
            $tds[i + 1].innerText = usersSaveData[trId][i];
        }
        this.insertAdjacentHTML('afterend', editBtn);
        var $previousElt = this.previousElementSibling, $editEnd = $tr.querySelector('.edit-end');
        $previousElt.parentElement.removeChild($previousElt);
        this.parentElement.removeChild(this);
        $editEnd.parentElement.removeChild($editEnd);
    }
    /**
     * Check form informations before send them to PHP
     *
     * @param $tr
     * @param $tds
     * @param roles
     * @param $content
     * @param add
     *
     * @returns {any}
     */
    function checkin($tr, $tds, thoseRoles, $content, add) {
        if (add === void 0) { add = false; }
        var erreurs = '', $mail = $tds[0].children[0], pwd = $tds[1].children[0].value, pseudo = $tds[2].children[0].value;
        if ('' === $mail.value)
            erreurs += 'Mail not specified !<br>';
        else if (true === $mail.validity.tooLong)
            erreurs += 'Mail too long !<br>';
        else if (true === $mail.validity.typeMismatch)
            erreurs += 'Mail : Incorrect format !<br>';
        if ('' === pwd)
            erreurs += 'Password not specified !<br>';
        if ('' === pseudo)
            erreurs += 'Pseudo not specified !<br>';
        var temp = false, roleVal = $tds[3].querySelector('a[data-value]').dataset.value;
        for (var key in thoseRoles) {
            if (thoseRoles.hasOwnProperty(key) && roleVal === thoseRoles[key].id) {
                temp = true;
                break;
            }
        }
        if (false === temp)
            erreurs += 'Invalid role !';
        if ('' === erreurs) {
            var data = {
                mail: $mail.value,
                pseudo: pseudo,
                pwd: pwd,
                role: roleVal
            };
            if (false === add) {
                data.oldMail = oldMail;
                data.oldPseudo = oldPseudo;
                data.id_user = $tr[0].id;
            }
            return data;
        }
        else {
            LIB_NOTIF.run($content, erreurs, 'ERROR', LIB_NOTIF.ERROR, 10000);
            return true;
        }
    }
    /** Triggered after we comes back from the PHP (from afterUpdate callback) */
    function formSuccess(data, $content) {
        try {
            var jsonData = JSON.parse(data);
            if (true === jsonData.success) {
                LIB_NOTIF.run($content, jsonData.msg, 'INFO', LIB_NOTIF.INFO, 10000);
                return jsonData.id;
            }
            else {
                LIB_NOTIF.run($content, jsonData.msg, 'ERROR', LIB_NOTIF.ERROR, 10000);
                return false;
            }
        }
        catch (e) {
            if (undef !== FWK_DEBUG)
                FWK_DEBUG.postLog(e, data);
            return false;
        }
    }
    /**
     * Triggered after we comes back from the PHP
     *
     * @param  {string}  data     [description]
     * @param  {Object}  $content [description]
     * @param  {NodeListOf<Element>}  $tds     [description]
     * @param  {boolean} add      [description]
     * @param  {HTMLTableRowElement}  $tr      [description]
     *
     * @return {Object}           [description]
     */
    function afterUpdate(data, $content, $tds, add, $tr) {
        if (add === void 0) { add = false; }
        var dataId = formSuccess(data, $content);
        if (false === dataId)
            return false;
        var $select = $tds.querySelector('.select');
        if (false !== add)
            $tr[0].id = data;
        [].map.call($tds.querySelector('input'), function (i) {
            this.outerHTML = 1 === i ? '********' : this.value;
        });
        $select.replaceWith($select[0].querySelector('a').textContent);
        $tds.slice(-1)[0].nextElementSibling.innerHTML = editBtn + deleteBtn;
        // if there are too much users on this page, we add a page and we pass to the next page of users
        if (true === add) {
            // We update the paging informations
            ++$pageInd.dataset.realcount;
            $lastPage.nextSibling.textContent = ' (' + $pageInd.dataset.realcount + ' users)';
            // If adds a new user adds an page then we pass to this new page.
            if (2 + +$limitValue.textContent < $tbody[0].querySelectorAll('tr').length) {
                next();
                // The last page cannot be less than the actual !!
                if (+$currentPage.textContent > +$lastPage.textContent)
                    $lastPage.textContent = $currentPage.textContent;
            }
            else
                $limit.dataset.last = data;
        }
    }
    /**
     * If we use the 'enter' key then we initiate the search
     */
    function search(e) {
        if (13 !== e.keyCode)
            return;
        refresh('search');
    }
    /**
     * Replace the actual page of users by the next one.
     */
    function next() {
        refresh('next');
        if (1 < ++page)
            $prev.classList.remove('disabled');
        if (page.toString() === $lastPage.textContent)
            $next.classList.add('disabled');
        $currentPage.textContent = page.toString();
    }
    function focusSelect() {
        this.querySelectorAll('input, select')[0].focus();
    }
    /** Refreshes the table. We can come from the pagination or a search. */
    function refresh(type) {
        var $firstTr = $tbody.querySelector('tr:first'), $tds = $firstTr.querySelectorAll('td'), mail = $tds[1][0].querySelector('input').value.trim(), pseudo = $tds[3][0].querySelector('input').value.trim(), role = $tds[4][0].querySelector('input').value.trim(), limitValue = $limitValue.textContent, pageIndDataset = $pageInd.dataset;
        // If we did not type anything in the search fields then we do nothing.
        if ('search' === type && pageIndDataset.realcount === pageIndDataset.actualcount && '' === mail + pseudo + role)
            return;
        var options = $tbody.querySelector('#tr-options');
        $.post('/backend/ajax/users/search', {
            last: $limit.dataset.last,
            limit: limitValue,
            mail: mail,
            prev: $limit.first,
            pseudo: pseudo,
            type: type,
            role: role
        }, searchSuccess.bind(null, type, pageIndDataset, limitValue, $firstTr));
    }
    function searchSuccess(data, type, pageIndDataset, limitValue, $firstTr) {
        try {
            var thatData = JSON.parse(data);
            if (true === thatData.success) {
                if ('search' === type) {
                    pageIndDataset.actualcount = thatData.count;
                    // Updates ... Page 1 / 2 (1 user) ... section
                    $pageInd.querySelector('#last-page').innerHTML = Math.ceil(thatData.count / limitValue).toString();
                    var $childNodes = $pageInd.childNodes;
                    $childNodes.item($childNodes.length - 1).textContent =
                        ' (' + thatData.count + (2 > thatData.count ? ' user)' : ' users)');
                }
                var $trFirst = $tbody.querySelector('tr:first'), $thingsToReplace = [];
                while ($trFirst.nextElementSibling !== undef
                    && toolsBase.matches($trFirst.nextElementSibling, '#tr-options') === false)
                    $thingsToReplace.push($trFirst = $trFirst.nextElementSibling);
                // Fixes the bug when the previous search has put nothing...then there is nothing to replace
                if (0 === $thingsToReplace.length)
                    $trFirst.after(thatData.msg);
                else
                    $thingsToReplace.eq(0).after(thatData.msg).end().remove();
                $limit.dataset.first = thatData.first;
                $limit.dataset.last = thatData.last;
            }
            else if (false === thatData.success)
                LIB_NOTIF.run($firstTr, thatData.msg, 'ERROR', LIB_NOTIF.ERROR, 10000);
            else
                document.documentElement.innerHTML = thatData.msg; // change html tag content with thatData.msg
        }
        catch (e) {
            if (undef !== FWK_DEBUG)
                FWK_DEBUG.postLog(e, data);
            return false;
        }
    }
    /**
     *
     * @param evt
     */
    function delegateMouseUpEvents(evt) {
        console.log(evt.target);
        // Events init°
        var events = [
            ['input#users-all', backend.selectAll],
            ['td:nth-child(1):not(.options)', backend.triggerCheckbox],
            ['span#add', addUser],
            ['tr.editable>td', focusSelect],
            ['._edit', edit],
            ['._editEnd', editEnd],
            ['._editEnd', editEnd],
            ['._delete', del],
            ['#users-del-all', deleteAll],
            ['._validate', validOne],
            ['#users-val-all', validAll],
            ['._cancel', cancel],
            ['#prev:not(.disabled)', prev],
            ['#next:not(.disabled)', next],
            ['._search', search]
        ];
        // We look for each event
        // whether there is a selector that match the actual selector that is related to the current event
        // and if it is the case then launch the appropriate callback
        for (var event_1 in events) {
            if (events.hasOwnProperty(event_1)
                && (true === toolsBase.matches(evt.target, events[event_1][0]))) {
                events[event_1][1].call(this, evt);
                break;
            }
        }
    }
    var CMS_INIT_USERS = function () {
        /**
         * CACHING
         */
        $tbody = document.getElementById('users-body');
        /** PAGINATION */
        cpt = 0;
        $prev = doc.getElementById('prev');
        $next = doc.getElementById('next');
        $currentPage = doc.getElementById('current-page');
        $lastPage = doc.getElementById('last-page');
        $pageInd = doc.getElementById('page-ind');
        $limit = doc.getElementById('limit');
        $limitValue = doc.getElementById('limit-value');
        /** TEMPLATES */
        $templateUser = document.getElementById('template-user');
        $templateRoleTxt = document.getElementById('role-txt');
        $templateUserValidation = document.getElementById('user-validation');
        /**
         * EVENTS
         */
        $tbody.addEventListener('mouseup', delegateMouseUpEvents);
    };
    function pageReady() { CMS_INIT_USERS(); }
    'loading' !== document.readyState
        ? pageReady()
        : document.addEventListener('DOMContentLoaded', pageReady);
})(document, window, undefined);
//# sourceMappingURL=users.js.map