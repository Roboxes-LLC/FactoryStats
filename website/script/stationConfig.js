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
      if (stationInfo.updateTime != null)
      {
         element.innerHTML = stationInfo.updateTime;   
      }
   }
}

function setStationId(stationId)
{
   var input = document.getElementById('station-id-input');
   input.setAttribute('value', stationId);
}

function setStationInfo(name, label, objectName, cycleTime, hideOnSummary)
{
   document.getElementById('station-name-input').value = name;
   document.getElementById('station-label-input').value = label;
   document.getElementById('object-name-input').value = objectName;
   document.getElementById('station-cycle-time-input').value = cycleTime;
   document.getElementById('hide-on-summary-input').checked = hideOnSummary;
}

function setAction(action)
{
   var input = document.getElementById('action-input');
   input.setAttribute('value', action);
}