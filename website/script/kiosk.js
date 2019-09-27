function setShift(shiftId)
{
   $element = document.getElementById("shift-id-input");
   
   if ($element)
   {
      $element.value = shiftId;
   }
}

function updateShift()
{
   var requestURL = "api/shift/";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         setShift(parseInt(json.shiftId));
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

// Start a 1 minute timer to update the shift input.
// TODO: Ocassionally collides with other timers making API calls.
setInterval(function(){updateShift();}, 60000);
