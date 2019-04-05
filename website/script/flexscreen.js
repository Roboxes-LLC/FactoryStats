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

function update()
{
   var requestURL = "api/status/?stationId=" + getStationId() + "&action=status";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);

         updateCount(json.count);
         updateHourlyCount(json.hourlyCount);
         updateCountTime(json.updateTime);
         updateElapsedTime();
         updateAverageCountTime(json.averageCountTime);
         updateHardwareButtonIndicator(json.hardwareButtonStatus);
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
   var timeClass = "time-early";  // time-early, time-warning, time-late
   
   if (window.lastCountTime)
   {
      var now = new Date(Date.now());
      var lastCountTime = new Date(Date.parse(window.lastCountTime));
      
      if (lastCountTime)
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
         
         // Style the elapsed time display based on its relation to the cycle time.
         var cycleTime = getCycleTime();  // seconds
         if (cycleTime > 0)
         {            
            var totalSeconds = Math.round(diff / millisInSecond);
            var warningThreshold = Math.round(cycleTime * 0.8);  // Warning at 80% of cycle time
            timeClass = (totalSeconds > cycleTime) ? "time-late" : (totalSeconds > warningThreshold) ? "time-warning" : "time-early";
         }
      }
   }
   
   var element = document.getElementById("elapsed-time-div");
   
   element.innerHTML = timeString;
   
   element.classList.remove("time-early");
   element.classList.remove("time-warning");
   element.classList.remove("time-late");
   element.classList.add(timeClass);
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
   drawChart(hourlyCount);
}

function incrementCount()
{
   var requestURL = "api/update/?stationId=" + getStationId() + "&count=1";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);

         updateCount(json.count);
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send(); 
}

function decrementCount()
{
   var requestURL = "api/update/?stationId=" + getStationId() + "&count=-1";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         updateCount(json.count);
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send(); 
}

function getStationId()
{
   var element = document.getElementById("station-id-input");

   return (element.value);
}

function getCycleTime()
{
   var element = document.getElementById("cycle-time-input");

   return (element.value);
}

function padNumber(number)
{
   return ((number < 10 ? '0' : '') + number);
}