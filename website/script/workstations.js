var MenuItem = {
   FIRST : 0,
   WORKSTATION_SUMMARY : 0,
   PRODUCTION_HISTORY : 1,
   CONFIGURATION : 2,
   LAST : 3
};

// Keep track of the current shift, as it is updated by the server.
var currentShiftId = 0;

// The stations being displayed.
var stationIds = new Array();

var lastCountTime = new Array();

var charts = new Array();

function initializeCharts()
{
   for (const stationId of stationIds)
   {
      var container = getElement("hourly-count-chart-div", stationId);
      
      if (container != null)
      {
         charts[stationId] = new HourlyStatsChart(container);
      }
   }
   
   resizeCharts();
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
   var requestURL = "api/status/?";
   
   var addAmpersand = false;
   for (const stationId of stationIds)
   {
      if (addAmpersand)
      {
         requestURL += "&";
      }
      
      requestURL += "stationIds[]=" + stationId;
      
      addAmpersand = true;
   }
   
   requestURL += "&shiftId=" + getShiftId()
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         try
         {
            var json = JSON.parse(this.responseText);
            
            if (json.success)
            {
               for (const workstation of json.workstations)
               {
                  updateWorkstation(workstation);
               }
   
               currentShiftId = parseInt(json.currentShiftId);
            }
            else
            {
               console.log("Status update failed: " + json.error);
            }
         }
         catch (exception)
         {
            if (exception.name == "SyntaxError")
            {
               console.log("JSON syntax error");
               console.log(this.responseText);
            }
            else
            {
               throw(exception);
            }            
         }
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function updateWorkstation(workstation)
{
   updateCount(workstation.stationId, workstation.count);

   updateHourlyCount(workstation.stationId, workstation.hourlyCount, new Date(workstation.shiftStartTime), new Date(workstation.shiftEndTime));

   if (workstation.isOnBreak == true)
   {
      updateCountTime(workstation.stationId, workstation.breakInfo.startTime);
   }
   else
   {
      updateCountTime(workstation.stationId, workstation.updateTime);
   }

   updateElapsedTime(workstation.stationId);

   updateAverageCountTime(workstation.stationId, workstation.averageCountTime);
   
   updateBreak(workstation.stationId, workstation.isOnBreak, workstation.breakInfo);

   updateFirstEntry(workstation.stationId, workstation.firstEntry);
   
   updateLastEntry(workstation.stationId, workstation.updateTime);
}

function getElement(id, stationId)
{
   return (document.getElementById(id + "-" + stationId));
}

function updateCount(stationId, count)
{
   var element = getElement("count-div", stationId);
   element.innerHTML = count;
}

function updateCountTime(stationId, countTime)
{
   // Store count time in global variable.
   window.lastCountTime[stationId] = countTime;
}

function updateElapsedTimes(stationIds)
{
   for (const stationId of stationIds)
   {
      updateElapsedTime(stationId);
   }
}

function updateElapsedTime(stationId)
{
   var timeString = "----";
   
   if (window.lastCountTime[stationId])
   {
      var now = new Date(Date.now());
      var lastCountTime = new Date(Date.parse(window.lastCountTime[stationId]));
      
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
   
   var element = getElement("elapsed-time-div", stationId);

   element.innerHTML = timeString;
}

function updateAverageCountTime(stationId, averageCountTime)
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
   
   var element = getElement("average-count-time-div", stationId);
   element.innerHTML = timeString;
}

function updateHourlyCount(stationId, hourlyCount, shiftStartTime, shiftEndTime)
{
   charts[stationId].setChartHours(shiftStartTime, shiftEndTime);
   
   charts[stationId].update(hourlyCount);
}

function getConfiguredDisplaySize()
{
   var displaySize = DisplaySize.AUTO;
   
   if (document.documentElement.classList.contains("display-small"))
   {
      displaySize = DisplaySize.SMALL;
   }
   else if (document.documentElement.classList.contains("display-medium"))
   {
      displaySize = DisplaySize.MEDIUM;
   }
   else if (document.documentElement.classList.contains("display-large"))
   {
      displaySize = DisplaySize.LARGE;
   }
   
   return (displaySize);
}

function getDisplaySize(screenWidth)
{
   var displaySize = DisplaySize.SMALL;
   
   // Small
   // 768px - 1023px
   if (screenWidth < 1024)
   {
      displaySize = DisplaySize.SMALL;
   }
   // Medium
   // 1024px - 1365px
   else if (screenWidth < 1366)
   {
      displaySize = DisplaySize.SMALL;
   }
   // Large
   // 1366px - 1919px
   else if (screenWidth < 1920)
   {
      displaySize = DisplaySize.MEDIUM;      
   }
   // X-Large
   // 1920px - 2559px
   else if (screenWidth < 2560)
   {
      displaySize = DisplaySize.MEDIUM;      
   }
   // XX-Large
   // >= 2560px
   else 
   {
      displaySize = DisplaySize.LARGE; 
   }
   
   return (displaySize);
}

function getChartDimensions(displaySize, chartSize)
{
   var dimensions = 
   {
      titleFontSize: 0, 
      hAxisFontSize: 0, 
      annotationFontSize: 0
   };
   
   var chartDimensions = 
   [  /*                       small        medium         large */
      /* small screen  */ [[10, 10, 10], [15, 15, 15], [25, 25, 25]],
      /* medium screen */ [[15, 15, 15], [25, 25, 25], [40, 40, 40]],
      /* large screen  */ [[50, 50, 50], [60, 60, 60], [80, 80, 80]]
   ];
   
   if ((chartSize >= ChartSize.SMALL) && (chartSize <= ChartSize.LARGE) &&
       (displaySize >= DisplaySize.SMALL) && (displaySize <= DisplaySize.LARGE))
   {
      var displayIndex = displaySize - DisplaySize.SMALL;
      var chartIndex = chartSize - ChartSize.SMALL;
      
      dimensions.titleFontSize = chartDimensions[displayIndex][chartIndex][0]; 
      dimensions.hAxisFontSize = chartDimensions[displayIndex][chartIndex][1]; 
      dimensions.annotationFontSize = chartDimensions[displayIndex][chartIndex][2];
   }
   
   return (dimensions);
}

function resizeCharts()
{
   document.getElementById("screen-res-div").innerHTML = screen.width  + "x" + screen.height;

   var displaySize = getConfiguredDisplaySize()
   if (displaySize == DisplaySize.AUTO)
   {
      displaySize = getDisplaySize(screen.width);
   }

   for (const stationId of stationIds)
   {
      var chartSize = charts[stationId].container.getAttribute("data-chart-size");
         
      var chartDimensions = getChartDimensions(displaySize, chartSize);

      charts[stationId].setChartFontSize(chartDimensions.titleFontSize, chartDimensions.hAxisFontSize, chartDimensions.annotationFontSize);
   }
}

function getShiftId()
{
   return (parseInt(document.getElementById("shift-id-input").value));
}

function padNumber(number)
{
   return ((number < 10 ? '0' : '') + number);
}

function updateBreak(stationId, isOnBreak, breakInfo)
{
   window.isOnBreak = isOnBreak;

   if (isOnBreak)
   {
      getElement("break-time-label", stationId).style.display = "block";
      getElement("elapsed-time-label", stationId).style.display = "none"; 
   }
   else
   {
      getElement("break-time-label", stationId).style.display = "none";
      getElement("elapsed-time-label", stationId).style.display = "block"; 
   }
}

function updateFirstEntry(stationId, firstEntry)
{
   var timeString = "----";
   
   if (firstEntry)
   {
      var date = new Date(Date.parse(firstEntry));
      timeString = date.toLocaleTimeString("en-US", {hour: 'numeric', minute: 'numeric'});
   }
         
   var element = getElement("first-entry-time-div", stationId);
   element.innerHTML = timeString;
}

function updateLastEntry(stationId, lastEntry)
{
   var timeString = "----";
      
   if (lastEntry)
   {
      var date = new Date(Date.parse(lastEntry));
      timeString = date.toLocaleTimeString("en-US", {hour: 'numeric', minute: 'numeric'});
   }
         
   var element = getElement("last-entry-time-div", stationId);
   element.innerHTML = timeString;
}