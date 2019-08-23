<?php
require_once 'common/database.php';
require_once 'common/params.php';
require_once 'common/shiftInfo.php';
require_once 'common/stationInfo.php';
require_once 'common/workstationStatus.php';

function getShiftId()
{
   $shiftId = ShiftInfo::DEFAULT_SHIFT_ID;
   
   $params = Params::parse();
   
   $currentShiftId = ShiftInfo::getShift(Time::now("H:i:s"));
   
   if ($params->keyExists("shiftId"))
   {
      $shiftId = $params->getInt("shiftId");
   }
   else if ($currentShiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
   {
      $shiftId = $currentShiftId;
   }

   return ($shiftId);
}

function renderStationSummaries($shiftId)
{
   echo "<div class=\"flex-horizontal main summary\">";
   
   $result = FlexscreenDatabase::getInstance()->getStations();
   
   while ($result && ($row = $result->fetch_assoc()))
   {
      $stationId = $row["stationId"];
      
      renderStationSummary($stationId, $shiftId);
   }
   
   echo "</div>";
}

function renderStationSummary($stationId, $shiftId)
{
   $url= "workstation.php?stationId=" . $stationId . "&shiftId=" . $shiftId;

   echo "<a href=\"$url\"><div id=\"workstation-summary-$stationId\" class=\"flex-vertical station-summary-div\">";
   
   $stationInfo = StationInfo::load($stationId);
   
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);
   
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

function renderShiftOptions()
{
   $selectedShiftId = getShiftId();

   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getShifts();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $shiftId = $row["shiftId"];
         $shiftName = $row["shiftName"];
         $selected = ($shiftId == $selectedShiftId) ? "selected" : "";
         
         echo "<option value=\"$shiftId\" $selected>$shiftName</option>";
      }
   }
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
   
   <div class="flex-horizontal historical-data-filter-div" style="width:100%; align-items: center;">
      <label>Shift: </label><select id="shift-id-input" name="shiftId" onchange="update()"><?php renderShiftOptions();?></select>
   </div>
   
   <?php renderStationSummaries(getShiftId());?>
     
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