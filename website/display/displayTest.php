<?php
?>

<html>
   <head>
      <script>
         var displayInfo = null;
         
         function getUID()
         {
            let requestURL = "http://localhost/displayInfo.json"; 
         
            let xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function()
            {
            
               if ((this.readyState == 4) && (this.status == 200))
               {
                  try
                  {
                     var json = JSON.parse(this.responseText);
         
                     console.log("Setting display info for display " + json.uid);
                     displayInfo = json;
                     update();
                  }
                  catch (exception)
                  {
                     console.log(exception);
                     console.log("JSON syntax error");
                     console.log(this.responseText);
                  }
               }
            };
            xhttp.open("GET", requestURL, true);
            xhttp.send();
         }
         
         function update()
         {
            if (displayInfo != null)
               document.getElementById("uid").innerHTML = displayInfo.uid;
            else
               document.getElementById("uid").innerHTML = "Could not load UID";
         }
         
         window.onload = function() {
            getUID();
         }
      </script>
   </head>
   <body>
      <div id="uid"></div>
   </body>
</html>