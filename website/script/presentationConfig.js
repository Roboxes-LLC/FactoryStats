function getPresentation(presentationId, onLoaded)
{
   var requestURL = "api/presentation/?presentationId=" + presentationId; 
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);

            if (json.success && (onLoaded != null))
            {
               onLoaded(json.presentation);
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
