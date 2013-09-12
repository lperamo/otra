[].forEach.call( document.querySelectorAll('.foldable'), function(el) {
  el.addEventListener('click', function() {
   	var eltTemp = el;
   	if(eltTemp.nextSibling.className === "deepContent")
   	{
	   	while(eltTemp.nextSibling !== null && eltTemp.nextSibling.className === "deepContent")
	   	{
				eltTemp.nextSibling.className = "";
				eltTemp = eltTemp.nextSibling;
	   	}
	  }else{
	  	while(eltTemp.nextSibling !== null && eltTemp.nextSibling.className === "")
	   	{
				eltTemp.nextSibling.className = "deepContent";
				eltTemp = eltTemp.nextSibling;
	   	}
	  }
  }, false);
});

var eltTemp;
[].forEach.call( document.querySelectorAll('.showArgs'), function(el) {
  el.addEventListener('click', function() {
		eltTemp = el.parentNode.parentNode;
		console.log(eltTemp);
   	if(eltTemp.nextSibling.className === "deepContent")
   	{
			eltTemp.nextSibling.className = "";
	  }else{
			eltTemp.nextSibling.className = "deepContent";
	  }
  }, false);
});
