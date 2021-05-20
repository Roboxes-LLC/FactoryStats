function update()
{
   var requestURL = "api/workstationSummary/?shiftId=" + getShiftId();
      
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);

            for (var i = 0; i < json.workstationSummary.length; i++)
            {
               updateWorkstation(json.workstationSummary[i]);
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

function getWorkstationDiv(stationId)
{
   elementId = "workstation-summary-" + stationId;
   
   return (document.getElementById(elementId));
}

function getShiftId()
{
   return (document.getElementById("shift-id-input").value);
}

function getStationFilter()
{
   var stationFilter = 1;
   
   var element = document.getElementById("station-filter-input");
   
   if (element)
   {
      stationFilter = document.getElementById("station-filter-input").value;
   }
   
   return (stationFilter);
}

function updateWorkstation(workstationStatus)
{
   divElement = getWorkstationDiv(workstationStatus.stationId);
   
   if (divElement)
   {
      divElement.getElementsByClassName("station-label")[0].innerHTML = workstationStatus.label;
      
      divElement.getElementsByClassName("count-div")[0].innerHTML = workstationStatus.count;
      
      updateUpdateTime(workstationStatus.stationId, workstationStatus.updateTime);
      
      updateAverageCountTime(workstationStatus.stationId, workstationStatus.averageCountTime);
      
      updateCycleTimeStatus(workstationStatus.stationId, workstationStatus.cycleTimeStatus, workstationStatus.cycleTimeStatusLabel, workstationStatus.isOnBreak);
      
      updateVisibility(workstationStatus.stationId, workstationStatus.count);
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
      var dateString = "----";
      
      var now = new Date();
      
      var dateTime = new Date(updateTime);
      
      if ((now.getYear() == dateTime.getYear()) &&
          (now.getMonth() == dateTime.getMonth()) &&
          (now.getDay() == dateTime.getDay()))
      {
         var hours = dateTime.getHours();
         var amPm = (hours >= 12) ? "pm" : "am";
         hours = (hours == 0) ? 12 : (hours > 12) ? (hours - 12) : hours;
         
         var minutes = dateTime.getMinutes();
         if (minutes < 10)
         {
            minutes = "0" + minutes;
         }
         
         var dateString = hours + ":" + minutes + " " + amPm;
      }
      
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
            timeString += hours + "<span class=\"stat-units\"> hr </span>";
         }
         
         if ((hours > 0) || (minutes > 0))
         {
            timeString += minutes + "<span class=\"stat-units\"> min </span>";
         }
         
         if (hours == 0)
         {
            timeString += seconds + "<span class=\"stat-units\"> sec</span>";
         }
      }
   
      var element = divElement.getElementsByClassName("average-count-time-div")[0]
      element.innerHTML = timeString;
   }
}

function updateCycleTimeStatus(stationId, cycleTimeStatus, cycleTimeStatusLabel, isOnBreak)
{
   divElement = getWorkstationDiv(stationId);
   
   if (divElement)
   {
      divElement.classList.remove("under-cycle-time");
      divElement.classList.remove("near-cycle-time");
      divElement.classList.remove("over-cycle-time");
      divElement.classList.remove("paused");
      
      if (cycleTimeStatusLabel != "")
      {
         divElement.classList.add(cycleTimeStatusLabel);
      }
      
      if (isOnBreak)
      {
         divElement.classList.add("paused");
      }
   }
}

function updateVisibility(stationId, count)
{
   divElement = getWorkstationDiv(stationId);
   
   var stationFilter = getStationFilter(); 
   
   if (divElement)
   {
      var isVisible = ((stationFilter == 1) ||
                       ((stationFilter == 2) && (count > 0)) ||
                       ((stationFilter == 3) && (count == 0)));
                       
      if (isVisible)
      {
         show(divElement.id, "flex");
      }
      else
      {
         hide(divElement.id);
      }
   }
}
