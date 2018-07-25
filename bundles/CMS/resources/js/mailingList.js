$(function(){$("#ML_form").submit(this,function(){$.post(this.dataset.href,{email:$("#ML_email").val()},function(a){console.log("plop");$("#ML_info").html(a)});return!1})});
