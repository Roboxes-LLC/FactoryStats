function update()
{
   updateCount();
   updateHourlyCount();
}

function updateCount()
{
   var requestURL = "screenCount.php?stationId=" + getStationId() + "&action=hourlyCount";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
          
         var element = document.getElementById("count-div");
         element.innerHTML = json.totalCount;
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
}

function updateHourlyCount()
{
   var requestURL = "screenCount.php?stationId=" + getStationId() + "&action=hourlyCount";
   
   var xhttp = new XMLHttpRequest();
   xhttp.onreadystatechange = function()
   {
      if (this.readyState == 4 && this.status == 200)
      {
         var json = JSON.parse(this.responseText);
         
         var element = document.getElementById("hourly-count-div");
         
         var html = "<table><tr><td><b>Hour</b></td><td><b>Count</b></td></tr>";
            
         for (key in json.hourlyCount)
         {
            html += "<tr><td>" + key + "</td><td>" + json.hourlyCount[key] + "</td></tr>";
         }
         
         html += "</table>";
         
         element.innerHTML = html;
      }
   };
   xhttp.open("GET", requestURL, true);
   xhttp.send();
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

function getStationId()
{
   var element = document.getElementById("station-input");

   return (element.options[element.selectedIndex].value);
}