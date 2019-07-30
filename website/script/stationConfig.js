function updateStationInfo()
{
   console.log("Update");
   var requestURL = "api/stationInfoSummary/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);

         for (var i = 0; i < json.stationInfoSummary.length; i++)
         {
            updateWorkstation(json.stationInfoSummary[i]);
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function getUpdateTimeCell(stationId)
{
   elementId = "station-" + stationId + "-update-time";
   
   return (document.getElementById(elementId));
}

function updateWorkstation(stationInfo)
{
   element = getUpdateTimeCell(stationInfo.stationId);
   
   if (element)
   {
      element.innerHTML = stationInfo.updateTime;
   }
}