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

function setStationId(stationId)
{
   var input = document.getElementById('station-id-input');
   input.setAttribute('value', stationId);
}

function setStationInfo(name, label, description, cycleTime)
{
   var input = document.getElementById('station-name-input');
   input.setAttribute('value', name);
   
   input = document.getElementById('station-label-input');
   input.setAttribute('value', label);

   input = document.getElementById('station-description-input');
   input.setAttribute('value', description);

   input = document.getElementById('station-cycle-time-input');
   input.setAttribute('value', cycleTime);
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}