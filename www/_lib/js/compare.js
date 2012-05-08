<script language="javascript"> 

function toggleCommon(id)
{
	var arr = document.querySelectorAll("tr.hide");
    var curr_state = document.getElementById("displayText");

	if (curr_state.innerHTML == "hide common data") {

		for (i=0; i<arr.length; i++) {
			arr[i].style.display = 'none';
		}

		curr_state.innerHTML = "show common data";
	}
	else {

		for (i=0; i<arr.length; i++) {
			arr[i].style.display = 'table-row';
		}

		curr_state.innerHTML = "hide common data";
	}

}


</script>

