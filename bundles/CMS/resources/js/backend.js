var backend = (function(d)
{
  "use strict";

  var tabs = 0;
  function updateURL( event )
  {
    if (event.originalEvent.state)
    {
      $.get(
        event.originalEvent.state.link,
        function(response) {
          d.getElementById('content').innerHTML = response;
          d.getElementsByTagName('title')[0].textContent = event.originalEvent.state.title;
        }
      );
    }
  }

  /** Updates the tab CSS and updates the actual content */
  function changeTab($this, index)
  {
   $('#tab' + index).show();
   $('#tab' + $('.activeTab', '#menus').removeClass('activeTab').index()).hide();
   $this.addClass('activeTab')
  }

  function activateTab()
  {
    var $this = $(this),
    index = $this.index();

    if(! $this.hasClass('activeTab'))
    {
      var ajaxLink = this.dataset.href.replace('backend/', 'backend/ajax/'),
          title = this.textContent + ' - Backend';

      if(!((tabs & Math.pow(2, index)) >> index)) // if this tab isn't already loaded
      {
        $.get(
          ajaxLink,
          function(response)
          {
            try {
                response = JSON.parse(response);
                document.getElementsByTagName("html")[0].innerHTML = response.msg;
            } catch (e) {
                $('#content').append(response);
            }
            tabs += Math.pow(2, index);
            changeTab($this, index);
          }
        );
      } else
        changeTab($this, index);

      d.getElementsByTagName('title')[0].textContent = title;
      history.pushState({link: ajaxLink, title: title}, title, this.dataset.href);
    }
  }

  /** Allow to click on TDs instead of directly on checkboxes */
  function triggerCheckbox(evt)
  {
    if('TD' !== evt.target.tagName)
      return false;

    var event = document.createEvent('HTMLEvents');
    event.initEvent(evt.originalEvent.type, true, false);
    this.children[0].dispatchEvent(event);
    this.children[0].checked = !this.children[0].checked;
  }

  /** Select/Deselect all the related checkboxes */
  function selectAll() {
    $(this).closest('table').find('input[type=checkbox]:not("._selectAll")').prop('checked', !this.checked)
  }

  /** Make a check before launching a CRUD function via AJAX */
  function beforeAction(text, callback)
  {
    var content = $('#content').find('table'),
      checkboxesChecked = content.find('input[type=checkbox]:checked');

    if(0 === checkboxesChecked.length) {
      notif.run(content[0], 'Nothing was selected !', 'WARNING', notif.WARNING, 10000);
      return false
    }

    if(confirm('Do you really want to validate all the changes ?'))
    {
      checkboxesChecked.closest('tr').each(function() {
        callback.call(this, content)
      });
    }
  }

  // function

  $(window).on('popstate', updateURL);

  $(function()
  {
    tabs = Math.pow(2, $('.activeTab').index());
    $('#menus').on('click', '.tab', activateTab);
  });

  var dummy = {
    beforeAction: beforeAction,
    triggerCheckbox: triggerCheckbox,
    selectAll: selectAll
  };

  return dummy
})(document);
