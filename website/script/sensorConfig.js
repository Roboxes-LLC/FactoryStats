function updateSensorStatus()
{
   var requestURL = "api/sensorStatus/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);
            
            var table = document.getElementById("sensor-table");
            var rowCount = (table.rows.length - 1);
            var sensorCount = json.sensorStatuses.length;
            
            // Check for a change in the number of registered sensors.
            if (rowCount != sensorCount)
            {
               location.reload();
            }
            else
            {
               // Update all sensors.
               for (sensorStatus of json.sensorStatuses)
               {
                  var id = "sensor-" + sensorStatus.sensorId;
                  
                  // Get table row.
                  var row = document.getElementById(id);
                  
                  if (row != null)
                  {
                     // Last contact.
                     row.cells[3].innerHTML = sensorStatus.lastContact;
                     
                     // Sensor status.
                     row.cells[6].className = "";
                     row.cells[6].innerHTML = sensorStatus.sensorStatusLabel;
                     row.cells[6].classList.add(sensorStatus.sensorStatusClass);
                     
                     // LED indicator.         
                     var ledDiv = row.cells[7].querySelector('.display-led');     
                     ledDiv.classList.remove("led-green");     
                     ledDiv.classList.remove("led-red");
                     if (sensorStatus.isOnline)
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
