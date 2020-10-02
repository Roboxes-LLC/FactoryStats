/*
var Page = {
   FIRST : 0,
   SPLASH : 0,
   WORKSTATION_SUMMARY : 0,
   PRODUCTION_HISTORY : 1,
   HARDWARE_BUTTON : 2,
   LAST : 3
};

var lastCountTime = null;

function jumpTo(page)
{
   var urls = [
      "index.php",
      "workstationSummary.php",
      "productionHistory.php",
      "hardwareButton.php",
   ];
   
   if ((page >= Page.FIRST) && (page < Page.LAST))
   {
      
   }

   location.href = urls[page];
}
*/

var MenuItem = {
   FIRST : 0,
   WORKSTATION_SUMMARY : 0,
   PRODUCTION_HISTORY : 1,
   CONFIGURATION : 2,
   LAST : 3
};

// Store the shift info.
var shiftHours = null;

// Keep track of the current shift, as it is updated by the server.
var currentShiftId = 0;

var ValidatingAction = {
  INCREMENT_COUNT: 0,
  DECREMENT_COUNT: 1,
};

var validatingAction = 0;

var chart = null;

function initializeChart()
{
   chart = new HourlyStatsChart(document.getElementById("hourly-count-chart-div"));
}

function setMenuSelection(menuItem)
{
   var menuItemElements = [
      "menu-item-workstation-summary",
      "menu-item-production-history",
      "menu-item-configuration"
   ];
   
   for (var tempMenuItem = MenuItem.FIRST; tempMenuItem < MenuItem.LAST; tempMenuItem++)
   {
      var element = document.getElementById(menuItemElements[tempMenuItem]);
      
      if (element)  // Menu is not present in kiosk mode.
      {
         if (menuItem == tempMenuItem)
         {
            // Set.
            element.classList.add("selected");
         }
         else
         {
            // Clear.
            element.classList.remove("selected");
         }
      }
   }
}

function storeInSession(key, value)
{
   var requestURL = "api/session/?action=set&key=" + key + "&value=" + value;
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         if (json.success)
         {
            console.log("Stored [" + key + ", " + value + "] in session.");
         }
         else
         {
            console.log("Failed to store [" + key + ", " + value + "] in session.");
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function update()
{
   var requestURL = "api/status/?stationId=" + getStationId() + "&shiftId=" + getShiftId() + "&action=status";

   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         workstation = json.workstations[0];

         updateCount(workstation.count);
         
         updateHourlyCount(workstation.hourlyCount);
         
         if (workstation.isOnBreak == true)
         {
            updateCountTime(workstation.breakInfo.startTime);
         }
         else
         {
            updateCountTime(workstation.updateTime);
         }
         
         updateElapsedTime();
         
         updateCycleTimeStatus(workstation.cycleTimeStatus, workstation.cycleTimeStatusLabel, workstation.isOnBreak);
         
         updateAverageCountTime(workstation.averageCountTime);
         
         //updateHardwareButtonIndicator(json.hardwareButtonStatus);
         
         updateBreak(workstation.isOnBreak, workstation.breakInfo);
         
         updateFirstEntry(workstation.firstEntry);
         
         currentShiftId = parseInt(workstation.currentShiftId);
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function updateCount(count)
{
   var element = document.getElementById("count-div");
   element.innerHTML = count;
}

function updateCountTime(countTime)
{
   // Store count time in global variable.
   window.lastCountTime = countTime;
}

function updateElapsedTime()
{
   var timeString = "----";
   
   if (window.lastCountTime)
   {
      var now = new Date(Date.now());
      var lastCountTime = new Date(Date.parse(window.lastCountTime));
      
      // Verify lastCountTime is for this work day.
      if (lastCountTime &&
          ((lastCountTime.getYear() == now.getYear()) &&
           (lastCountTime.getMonth() == now.getMonth()) &&
           (lastCountTime.getDay() == now.getDay())))
      {
         var diff = new Date(now - lastCountTime);
         
         var millisInHour = (1000 * 60 * 60);
         var millisInMinute = (1000 * 60);
         var millisInSecond = 1000;
         
         var hours = Math.floor(diff / millisInHour);
         var minutes = Math.floor((diff % millisInHour) / millisInMinute);
         var seconds = Math.round((diff % millisInMinute) / millisInSecond);
         var tenths = Math.round((diff % millisInSecond) / 10);
         
         if (hours > 0)
         {
            timeString = padNumber(hours) + ":" + padNumber(minutes) + ":" + padNumber(seconds);
         }
         else
         {
            timeString = padNumber(minutes) + ":" + padNumber(seconds);
         }
      }
   }
   
   var element = document.getElementById("elapsed-time-div");
   
   element.innerHTML = timeString;
}

function updateCycleTimeStatus(cycleTimeStatus, cycleTimeStatusLabel, isOnBreak)
{
   var element = document.getElementById("elapsed-time-div");
   
   element.classList.remove("under-cycle-time");
   element.classList.remove("near-cycle-time");
   element.classList.remove("over-cycle-time");
   element.classList.remove("paused");
   
   if (cycleTimeStatusLabel != "")
   {
      element.classList.add(cycleTimeStatusLabel);
   }
   
   if (isOnBreak)
   {
      element.classList.add("paused");
   }
}

function updateAverageCountTime(averageCountTime)
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
   
   var element = document.getElementById("average-count-time-div");
   element.innerHTML = timeString;
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

function updateHardwareButtonIndicator(hardwareButtonStatus)
{
   var element = document.getElementById("hardware-button-led");
   
   var ledClass = isHardwareButtonOnline(hardwareButtonStatus) ? "led-green" : "led-red";
   
   element.className = "";
   element.classList.add(ledClass);
}

function updateHourlyCount(hourlyCount)
{
   var shiftId = getShiftId();
   
   chart.setChartHours(shiftHours[shiftId].startHour, shiftHours[shiftId].endHour);
   
   chart.update(hourlyCount);
}

function incrementCount()
{
   //if (validateShift() == true)
   {
      var requestURL = "api/update/?stationId=" + getStationId() + "&shiftId=" + getShiftId() + "&count=1";
      
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function()
      {
         if (this.readyState == 4 && this.status == 200)
         {
            var json = JSON.parse(this.responseText);
   
            update();
         }
      };
      xhttp.open("GET", requestURL, true);
      xhttp.send(); 
   }
}

function decrementCount()
{
   var requestURL = "api/update/?stationId=" + getStationId() + "&shiftId=" + getShiftId() + "&count=-1";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         update();
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send(); 
}

function getStationId()
{
   return (document.getElementById("station-id-input").value);
}

function getShiftId()
{
   return (parseInt(document.getElementById("shift-id-input").value));
}

function shouldValidateShift()
{
   var silenceShiftValidation = document.getElementById("silence-shift-validation-input").checked;
   
   return ((silenceShiftValidation == false) && 
           (getShiftId() != currentShiftId));
}
      
function updateShiftValidationText()
{
   var textElement = document.getElementById("shift-validation-text");
   var templateElement = document.getElementById("shift-validation-template");
   var text = templateElement.innerHTML;         
   
   var shiftId = getShiftId();
   
   text = text.replace("%shiftName", shiftHours[shiftId].shiftName);
   text = text.replace("%shiftStart", shiftHours[shiftId].startTime);
   text = text.replace("%shiftEnd", shiftHours[shiftId].endTime);
   
   textElement.innerHTML = text;
}

function onShiftSelectionUpdate()
{
   document.getElementById("silence-shift-validation-input").checked = false;
}

function onShiftValidated()
{
   if (validatingAction == ValidatingAction.INCREMENT_COUNT)
   {
      incrementCount();
   }
   else if (validatingAction == ValidatingAction.DECREMENT_COUNT)
   {
      decrementCount();
   }
}

function getCycleTime()
{
   return (document.getElementById("cycle-time-input").value);
}

function padNumber(number)
{
   return ((number < 10 ? '0' : '') + number);
}

function toggleBreakButton()
{
   var element = document.getElementById("break-button");
   
   var isOnBreak = element.classList.contains("paused");
   
   var breakDescriptionId = document.getElementById("break-description-id-input").value;
   
   if (!isOnBreak)
   {
      startBreak(breakDescriptionId);
   }
   else
   {
      endBreak();
   }
}

function startBreak(breakDescriptionId)
{
   console.log("startBreak (breakDescriptionId = " + breakDescriptionId + ")");
   
   var requestURL = "api/break/?stationId=" + getStationId() + "&shiftId=" + getShiftId() + "&status=start&breakDescriptionId=" + breakDescriptionId;
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         if (json.success)
         {
            update();
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();   
}

function endBreak(stationId)
{
   console.log("endBreak");
   
   var requestURL = "api/break/?stationId=" + getStationId() + "&shiftId=" + getShiftId() + "&status=end";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);

         if (json.success)
         {
            update();
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();     
}

function updateBreak(isOnBreak, breakInfo)
{
   window.isOnBreak = isOnBreak;

   var element = document.getElementById("break-button");
   
   if (element != null)  // Not present in kiosk mode.
   {
      if (isOnBreak)
      {
         element.classList.add("paused");
         document.getElementById("break-time-label").style.display = "block";
         //document.getElementById("break-description").style.display = "block";
         //document.getElementById("break-description").innerHTML = breakInfo.breakDescriptionId;
         document.getElementById("elapsed-time-label").style.display = "none"; 
         document.getElementsByClassName("main")[0].classList.add("paused");
      }
      else
      {
         element.classList.remove("paused");
         document.getElementById("break-time-label").style.display = "none";
         //document.getElementById("break-description").style.display = "none";
         //document.getElementById("break-description").innerHTML = "";
         document.getElementById("elapsed-time-label").style.display = "block"; 
      }
   }
}

function updateFirstEntry(firstEntry)
{
   if (firstEntry)
   {
      var date = new Date(Date.parse(firstEntry));
      var timeString = date.toLocaleTimeString("en-US", {hour: 'numeric', minute: 'numeric'});
      
      var element = document.getElementById("first-entry-div");
      element.innerHTML = "Time of first screen:&nbsp&nbsp" + timeString;
   }
}