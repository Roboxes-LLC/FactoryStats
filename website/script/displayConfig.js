function updateDisplayStatus()
{
   var requestURL = "api/displayStatus/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         //try
         {
            var json = JSON.parse(this.responseText);
            
            var table = document.getElementById("display-table");
            var rowCount = (table.rows.length - 1);
            var displayCount = json.displayStatuses.length;
            
            // Check for a change in the number of registered displays.
            if (rowCount != displayCount)
            {
               location.reload();
            }
            else
            {
               // Update all displays.
               for (displayStatus of json.displayStatuses)
               {
                  var id = "display-" + displayStatus.displayId;
                  
                  // Get table row.
                  var row = document.getElementById(id);
                  
                  if (row != null)
                  {
                     // IP address.
                     row.cells[2].innerHTML = displayStatus.ipAddress;
                     
                     // Version.
                     row.cells[3].innerHTML = displayStatus.version;
                     
                     // Last contact.
                     row.cells[4].innerHTML = displayStatus.lastContact;
                     
                     // Display status.
                     row.cells[5].className = "";
                     row.cells[5].innerHTML = displayStatus.displayStatusLabel;
                     row.cells[5].classList.add(displayStatus.displayStatusClass);
   
                     // LED indicator.         
                     var ledDiv = row.cells[6].querySelector('.display-led');     
                     ledDiv.classList.remove("led-green");     
                     ledDiv.classList.remove("led-red");
                     if (displayStatus.isOnline)
                     {
                        ledDiv.classList.add("led-green");
                     }
                     else
                     {
                        ledDiv.classList.add("led-red");                     
                     }
                  }
               }
            }     
         }
         /*
         catch (exception)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
         */
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}
