"use strict";

$(function()
{
    $('#ML_form').submit(this, function()
    {
        // Adds the email in the mailing list
        $.post(
            this.dataset.href,
            {email: $('#ML_email').val()},
            function(response)
            {
                console.log('plop');
                $('#ML_info').html(response);
            }
        );
            
        return false;
    });
});