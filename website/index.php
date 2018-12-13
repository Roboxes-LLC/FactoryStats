<?php
require_once 'common/time.php';

Time::init();

function getStationId()
{
   $stationId = "";
   
   if (isset($_SESSION['stationId']))
   {
      $stationId = $_SESSION["stationId"];
   }
   else if (isset($_GET["stationId"]))
   {
      $stationId = $_GET["stationId"];
   }
   else if (isset($_POST["stationId"]))
   {
      $stationId = $_POST["stationId"];
   }
   
   return ($stationId);
}
?>

<html>

<head>
<title>Flexscreen Counter</title>
<link rel="stylesheet" type="text/css" href="common/flex.css"/>
<link rel="stylesheet" type="text/css" href="common/button.css"/>
<style>
table {
   border-collapse: collapse;
}

table, th, td {
   border: 1px solid black;
   text-align: center;
}

body {
   background: black;
   font-family: "Avant Garde", Avantgarde, "Century Gothic", CenturyGothic, AppleGothic, sans-serif;
}

.header {
   width: 100%;
   align-items: center;
   border-bottom-style: solid;
   border-color: white;
   border-width: 2px;
   padding-top: 5px;
   padding-bottom: 5px;   
}

.left-panel {
   height: 100%;
   background-image: url("images/Joe-Hands-Fade-In-Graphic-half.png");
   background-repeat: no-repeat;
   align-items: stretch;
   justify-content: flex-start;
   flex-grow: 1;
   padding-top: 50px;
   padding-left: 400px;
}

.right-panel {
   height: 100%;
   align-items: center;
   flex-grow: 1;
   width: 500px; /*FIX */
}
   
.stat-label {
   color: white;
   font-size: 24px;
}

.large-stat {
   color: white;
   font-size: 100px;
}

.urgent-stat {
   color: yellow;
}


</style>
</head>

<body>

   <form>
      <input id="station-id-input" type="hidden" name="stationId" value="<?php echo getStationId(); ?>">
   </form>

<div class="flex-vertical" style="align-items: flex-start;">

   <div class="flex-horizontal header">
      <div><img src="images/flexscreen-logo-hompage-2.png" width="350px"></div>
      <!-- select id="station-input">
         <option value="ALL">All Stations</option>
         <option value="STA1">Station 1</option>
         <option value="STA2">Station 2</option>
         <option value="STA3" selected>Station 3</option>
      </select-->
   </div>
   
   <div class="flex-horizontal">
   
      <div class="flex-vertical left-panel">
      
         <div class="stat-label">Today's screen count</div>
         <div id="count-div" class="large-stat"></div>
         
          <div class="stat-label">Average time between screens</div>
         <div id="average-count-time-div" class="large-stat"></div>
         
         <div class="stat-label">Time since last screen</div>
         <div id="elapsed-time-div" class="large-stat urgent-stat"></div>
         
      </div>
   
      <div class="flex-vertical right-panel">
         <div class="btn btn-blob" onclick="incrementCount(); update();">+</div>
      </div>
      
   </div>
   
</div>

<!--
Hourly count:<br/>
<div id="hourly-count-div">
</div>
-->

<script src="flexscreen.js"></script>
<script>
   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);

   // Start a one-second timer to update the elapsed-time-div.
   setInterval(function(){updateElapsedTime();}, 500);
   update();
</script>

</body>

</html>