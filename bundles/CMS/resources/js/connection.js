"use strict";

$(function()
{
    $('#connectionForm').submit(this, function()
    {
        var formThis = this;
        $.post(
            this.dataset.href,
            {
                email: document.getElementById('email').value,
                pwd: document.getElementById('pwd').value
            },
            function(response)
            {
                response = $.parseJSON(response);
                if('fail' === response[0])
                    $('#connection_info').html(response[1]);
                else
                    document.location.href = formThis.dataset.href_redirect;
            }
        );

        return false;
    });
});
