function updateDisplayStatus()
{
   var requestURL = "api/displayStatus/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);

            for (displayStatus of json.displayStatuses)
            {
               var id = "display-" + displayStatus.displayId;
               
               var element = document.getElementById(id);
               
               if (element != null)
               {
                  element.childNodes[0].innerHTML = displayStatus.label;
                  
                  element.childNodes[1].classList.remove("led-green");
                  element.childNodes[1].classList.remove("led-red");
                  element.childNodes[1].classList.add(displayStatus.ledClass);
               }
            }     
         }
         catch (expection)
         {
            console.log("JSON syntax error");
            console.log(this.responseText);
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}
