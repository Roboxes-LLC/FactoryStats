function update()
{
   var requestURL = "../api/workstationSummary/"
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);

         for (var i = 0; i < json.workstationSummary.length; i++)
         {
            updateWorkstation(json.workstationSummary[i]);
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function getWorkstationDiv(stationId)
{
   elementId = "workstation-summary-" + stationId;
   
   return (document.getElementById(elementId));
}

function updateWorkstation(workstationStatus)
{
   divElement = getWorkstationDiv(workstationStatus.stationId);
   
   if (divElement)
   {
      divElement.getElementsByClassName("station-id-div")[0].innerHTML = workstationStatus.stationId;
      updateHardwareButtonIndicator(workstationStatus.stationId, workstationStatus.hardwareButtonStatus);
      divElement.getElementsByClassName("count-div")[0].innerHTML = workstationStatus.count;
      updateUpdateTime(workstationStatus.stationId, workstationStatus.updateTime);
      updateAverageCountTime(workstationStatus.stationId, workstationStatus.averageCountTime);
   }
}

function isHardwareButtonOnline(hardwareButtonStatus)
{
   var isOnline = false;
   
   var now = new Date(Date.now());
   var lastContactTime = new Date(Date.parse(hardwareButtonStatus.lastContact));
   
   if (lastContactTime)
   {
      var diff = new Date(now - lastContactTime);
      
      var millisInSecond = 1000;
      var seconds = Math.round(diff / millisInSecond);

      isOnline = (seconds < 15);
   }  
   
   return (isOnline);
}

function updateHardwareButtonIndicator(stationId, hardwareButtonStatus)
{
   divElement = getWorkstationDiv(stationId);
   
   if (divElement)
   {
      var element = divElement.getElementsByClassName("hardware-button-led")[0];
   
      var ledClass = isHardwareButtonOnline(hardwareButtonStatus) ? "led-green" : "led-red";
   
      element.className = "hardware-button-led";
      element.classList.add(ledClass);
   }
}

function updateUpdateTime(stationId, updateTime)
{
   divElement = getWorkstationDiv(stationId);
   
   if (divElement)
   {
      var dateTime = new Date(updateTime);
      
      var hours = dateTime.getHours();
      hours = (hours > 12) ? (hours - 12) : hours;
      var amPm = (hours > 12) ? "pm" : "am";
      
      var minutes = dateTime.getMinutes();
      if (minutes < 10)
      {
         minutes = "0" + minutes;
      }

      // TODO: Revisit, maybe using phrases like "yesterday", "last week", "3 months ago".
      /*
      var dateString = (dateTime.getMonth() + 1) + "/" + dateTime.getDay() + "/" + dateTime.getFullYear() + " " +
                       hours + ":" + dateTime.getMinutes() + " " + amPm;
      */
      
      var dateString = hours + ":" + minutes + " " + amPm;
      
      
      var element = divElement.getElementsByClassName("update-time-div")[0]
      element.innerHTML = dateString;
   }   
}

function updateAverageCountTime(stationId, averageCountTime)
{
   divElement = getWorkstationDiv(stationId);
   
   if (divElement)
   {
      var timeString = "----";
      
      if (averageCountTime > 0)
      {
         var hours = Math.floor(averageCountTime / 3600);
         var minutes = Math.floor((averageCountTime % 3600) / 60);
         var seconds = (averageCountTime % 60);
         
         timeString = "";
         
         if (hours > 0)
         {
            timeString += hours + "<span class=\"stat-label\"> hours </span>";
         }
         
         if ((hours > 0) || (minutes > 0))
         {
            timeString += minutes + "<span class=\"stat-label\"> minutes </span>";
         }
         
         if (hours == 0)
         {
            timeString += seconds + "<span class=\"stat-label\"> seconds</span>";
         }
      }
   
      var element = divElement.getElementsByClassName("average-count-time-div")[0]
      element.innerHTML = timeString;
   }
}