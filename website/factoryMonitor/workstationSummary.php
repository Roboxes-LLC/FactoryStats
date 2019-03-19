<?php
require_once '../common/database.php';
require_once '../common/workstationStatus.php';

function renderStationSummaries()
{
   echo "<div class=\"station-summaries-div flex-horizontal\">";
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $stationId = $row["stationId"];
         
         renderStationSummary($stationId);
      }
   }
   
   echo "</div>";
}

function renderStationSummary($stationId)
{
   $url= "/flexscreen/index.php?stationId=" . $stationId;

   echo "<a href=\"$url\"><div id=\"workstation-summary-$stationId\" class=\"flex-vertical station-summary-div\">";
   
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId);
   
   if ($workstationStatus)
   {
      echo 
<<<HEREDOC
      <div class="flex-horizontal" style="justify-content: flex-start;">
         <div class="medium-stat station-id-div">$stationId</div>
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
      
   // <div>Button status: {$workstationStatus->hardwareButtonStatus->lastContact}</div>
      
   echo "</div></a>";
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Workstation Summary</title>
   
   <link rel="stylesheet" type="text/css" href="../common/flex.css"/>
   <link rel="stylesheet" type="text/css" href="../flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="workstationSummary.css"/>
   
   <style>
   .station-summary-div {
      color: white;
      border: 1px solid white;
   }
   </style>
   
</head>

<body onload="update()">

<div class="flex-vertical" style="align-items: flex-start;">

   <div class="flex-horizontal header">
      <div><img src="../images/flexscreen-logo-hompage-2.png" width="350px"></div>
   </div>
   
   <?php include '../common/menu.php';?>
   
   <?php renderStationSummaries();?>
     
</div>

<script src="../flexscreen.js"></script>
<script src="workstationSummary.js"></script>
<script>
   // Set menu selection.
   setMenuSelection(MenuItem.WORKSTATION_SUMMARY);

   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);
</script>

</body>

</html>