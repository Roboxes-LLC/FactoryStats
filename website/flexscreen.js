var lastCountTime = null;

function update()
{
   var requestURL = "screenCount.php?stationId=" + getStationId() + "&action=status";
   
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
      
      if (lastCountTime)
      {
         var diff = new Date(now - lastCountTime);
         
         var millisInHour = (1000 * 60 * 60);
         var millisInMinute = (1000 * 60);
         var millisInSecond = 1000;
         
         var hours = Math.floor(diff / millisInHour);
         var minutes = Math.floor((diff % millisInHour) / millisInMinute);
         var seconds = Math.round((diff % millisInMinute) / 1000);
         
         timeString = padNumber(hours) + ":" + padNumber(minutes) + ":" + padNumber(seconds);
      }
   }
   
   var element = document.getElementById("elapsed-time-div");
   element.innerHTML = timeString;
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

function updateHourlyCount(hourlyCount)
{
   drawChart(hourlyCount);
}

function incrementCount()
{
   var requestURL = "screenCount.php?stationId=" + getStationId() + "&action=update&count=1";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         // Silently ignore this.responseText for now.
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send(); 
}

function decrementCount()
{
   var requestURL = "screenCount.php?stationId=" + getStationId() + "&action=update&count=-1";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         // Silently ignore this.responseText for now.
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

function padNumber(number)
{
   return ((number < 10 ? '0' : '') + number);
}