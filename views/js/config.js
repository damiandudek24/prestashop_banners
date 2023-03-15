if ( window.history.replaceState ) {
		window.history.replaceState( null, null, window.location.href );
	}

$(document).ready(function() {
	$(window).keydown(function(event){
		if(event.keyCode == 13) {
			event.preventDefault();
			return false;
		}
	});
});
  
//Zamiana url zdjęcia w tabeli na miniaturkę dodanego obrazka
$("td.changetoimage").each(function(e,t){
		var x = $(t).text(); 
		var newDiv = document.createElement("img");
		$(newDiv).attr("src", x);
		$(newDiv).attr("width", "40");
		$(t).text(""); $(t).append(newDiv);
	});

//Akcja po html przycisku
$('.delete-row').on("click",function(){
		var id = $(this).find('input[name=row-delete]').val();
		$("input[name='to-remove']").val(id);
		$("#Modal2").modal();
	})
