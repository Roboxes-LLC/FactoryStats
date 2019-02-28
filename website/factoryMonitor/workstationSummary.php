<?php
require_once '../common/database.php';
require_once '../common/workstationStatus.php';

function renderStationSummaries()
{
   echo "<div class=\"station-summaries-div\">";
   
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
   echo "<div id=\"summary-$stationId\" class=\"flex-vertical station-summary-div\">";
   
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId);
   
   if ($workstationStatus)
   {
      echo 
<<<HEREDOC
      <div>Station ID: $workstationStatus->stationId</div>
      <div>Count: $workstationStatus->count</div> 
      <div>Last update: $workstationStatus->updateTime</div>
      <div>Average time: $workstationStatus->averageCountTime</div>
HEREDOC;
   }
      
   // <div>Button status: {$workstationStatus->hardwareButtonStatus->lastContact}</div>
      
   echo "</div>";
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Workstation Summary</title>
   
   <link rel="stylesheet" type="text/css" href="../common/flex.css"/>
   <link rel="stylesheet" type="text/css" href="../flexscreen.css"/>
   
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
   
   <?php renderStationSummaries();?>
     
</div>

<script src="workstationSummary.js"></script>
<script>
   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);
</script>

</body>

</html>