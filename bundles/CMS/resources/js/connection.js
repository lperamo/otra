(function(b,d){function e(a){d===a.status?b.getElementById("connection-info").innerHTML=a[0]:b.location.href=a.url}function f(a){if(!0!==a.ok)console.log("Looks like there was a problem. Status Code: "+a.status);else return a.json()}function g(a){a.preventDefault();window.fetch(this.dataset.href,{body:"email="+b.getElementById("email").value+"&pwd="+b.getElementById("pwd").value,credentials:"same-origin",headers:{Accept:"application/json, text/plain, */*","Content-Type":"application/x-www-form-urlencoded"},
method:"post"}).then(f).then(e);return!1}function c(){b.getElementById("connection-form").addEventListener("submit",g,!1)}"loading"!==b.readyState?c():b.addEventListener("DOMContentLoaded",c)})(document,void 0);
