function updateButtonStatus()
{
   var requestURL = "api/buttonStatus/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);
            
            var table = document.getElementById("button-table");
            var rowCount = (table.rows.length - 1);
            var buttonCount = json.buttonStatuses.length;
            
            // Check for a change in the number of registered buttons.
            if (rowCount != buttonCount)
            {
               location.reload();
            }
            else
            {
               // Update all buttons.
               for (buttonStatus of json.buttonStatuses)
               {
                  var id = "button-" + buttonStatus.buttonId;
                  
                  // Get table row.
                  var row = document.getElementById(id);
                  
                  if (row != null)
                  {
                     // Last contact.
                     row.cells[2].innerHTML = buttonStatus.lastContact;
                     
                     // Button status.
                     row.cells[3].className = "";
                     row.cells[3].innerHTML = buttonStatus.buttonStatusLabel;
                     row.cells[3].classList.add(buttonStatus.buttonStatusClass);
   
                     // LED indicator.         
                     var ledDiv = row.cells[4].querySelector('.button-led');     
                     ledDiv.classList.remove("led-bright-green");
                     ledDiv.classList.remove("led-green");     
                     if (buttonStatus.recentlyPressed)
                     {
                        ledDiv.classList.add("led-bright-green");
                     }
                     else
                     {
                        ledDiv.classList.add("led-green");                     
                     }
                  }
               }
            }     
         }
         catch (exception)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}
