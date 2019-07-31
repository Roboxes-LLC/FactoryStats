<?php
require_once 'common/database.php';
require_once 'common/stationInfo.php';
require_once 'common/workstationStatus.php';

function renderStationSummaries()
{
   echo "<div class=\"flex-horizontal main summary\">";
   
   $result = FlexscreenDatabase::getInstance()->getStations();
   
   while ($result && ($row = $result->fetch_assoc()))
   {
      $stationId = $row["stationId"];
      
      renderStationSummary($stationId);
   }
   
   echo "</div>";
}

function renderStationSummary($stationId)
{
   $url= "workstation.php?stationId=" . $stationId;

   echo "<a href=\"$url\"><div id=\"workstation-summary-$stationId\" class=\"flex-vertical station-summary-div\">";
   
   $stationInfo = StationInfo::load($stationId);
   
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId);
   
   if ($stationInfo && $workstationStatus)
   {
      echo 
<<<HEREDOC
      <div class="flex-horizontal" style="justify-content: flex-start;">
         <div class="medium-stat station-id-div">{$stationInfo->getLabel()}</div>
         <div class="flex-horizontal hardware-button-led"></div>
      </div>

      <div class="flex-vertical">
         <div class="stat-label">Today's screen count</div>
         <div class="large-stat urgent-stat count-div"></div>
      </div>
      
      <div class="stat-label">Average time between screens</div>
      <div class="medium-stat average-count-time-div"></div>
      
      <div class="stat-label">Time of last screen</div>
      <div class="medium-stat update-time-div"></div>
HEREDOC;
   }
      
   echo "</div></a>";
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Workstation Summary</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="css/workstationSummary.css"/>
   
   <style>
   .station-summary-div {
      color: white;
      border: 1px solid white;
   }
   </style>
   
</head>

<body onload="update()">

<div class="flex-vertical" style="align-items: flex-start;">

   <?php include 'common/header.php';?>
   
   <?php include 'common/menu.php';?>
   
   <?php renderStationSummaries();?>
     
</div>

<script src="script/flexscreen.js"></script>
<script src="script/workstationSummary.js"></script>
<script>
   // Set menu selection.
   setMenuSelection(MenuItem.WORKSTATION_SUMMARY);

   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);
</script>

</body>

</html>