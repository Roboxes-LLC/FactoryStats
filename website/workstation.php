<?php
require_once 'common/displayInfo.php';
require_once 'common/time.php';
require_once 'common/displayInfo.php';
require_once 'common/stationInfo.php';

Time::init();

function getStationId()
{
   $stationId = "";
   
   if (isset($_GET["stationId"]))
   {
      $stationId = $_GET["stationId"];
   }
   else if (isset($_GET["displayId"]))
   {
      $displayId = $_GET["displayId"];
      
      $displayInfo = DisplayInfo::load($displayId);
      if ($displayInfo)
      {
         $stationId = $displayInfo->stationId;
      }
   }
   
   return ($stationId);
}

function getStationName($stationId)
{
   $name = "";
   
   $stationInfo = StationInfo::load($stationId);
   
   if ($stationInfo)
   {
      $name = $stationInfo->name;
   }
   
   return ($name);
}

function isReadOnly()
{
   return (isset($_GET["displayId"]));
}

function getButtons()
{
   echo
<<<HEREDOC
   <div class="btn btn-blob" onclick="incrementCount();">+</div>
   <div class="btn btn-small btn-blob" onclick="decrementCount();" style="position: relative; left:15px; top: 80px;">-</div>
HEREDOC;
}

$stationId = getStationId();

$stationName = getStationName($stationId);

$isReadOnly = isReadOnly();
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Flexscreen Counter</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/button.css"/>
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   
</head>

<body onload="update()">

   <form>
      <input id="station-id-input" type="hidden" name="stationId" value="<?php echo $stationId; ?>">
   </form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php include 'common/header.php';?>
   
   <?php if (!$isReadOnly) {include 'common/menu.php';}?>
   
   <div class="flex-horizontal" style="flex-wrap: wrap;">
   
      <div class="flex-vertical left-panel">
      
         <div class="flex-horizontal" style="justify-content: flex-start;">
            <div class="stat-label">Station</div>
            <div id="hardware-button-led" class="flex-horizontal"></div>
         </div>
         <div class="large-stat"><?php echo $stationName; ?></div>
         
         <div class="stat-label">Average time between screens</div>
         <div id="average-count-time-div" class="large-stat"></div>
         
         <div class="stat-label">Time since last screen</div>
         <div id="elapsed-time-div" class="large-stat urgent-stat"></div>
         
      </div>
   
      <div class="flex-vertical right-panel">
      
         <div class="flex-horizontal">
         
            <?php if (!$isReadOnly) {getButtons();}?>
            
            <div class="flex-vertical" style="margin-left: 50px;">
               <div class="stat-label">Today's screen count</div>
               <div id="count-div" class="large-stat"></div>
            </div>
            
         </div>
         
         <div id="hourly-count-chart-div" style="margin-top: 50px;"></div>
         
      </div>
      
   </div>
   
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script src="chart/chart.js"></script>
<script src="script/flexscreen.js"></script>
<script>
   // Start a timer to update the count/hourly count div.
   setInterval(function(){update();}, 3000);

   // Start a one-second timer to update the elapsed-time-div.
   setInterval(function(){updateElapsedTime();}, 500);
</script>

</body>

</html>