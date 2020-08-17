<?php

require_once 'common/breakDescription.php';
require_once 'common/dailySummary.php';
require_once 'common/database.php';
require_once 'common/displayInfo.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/shiftInfo.php';
require_once 'common/stationInfo.php';
require_once 'common/time.php';

Time::init();

session_start();

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
   else if (isset($_GET["macAddress"]))
   {
      $macAddress = $_GET["macAddress"];
      
      $displayId = DisplayInfo::getDisplayIdFromMac($macAddress);

      $displayInfo = DisplayInfo::load($displayId);
      
      if ($displayInfo)
      {
         $stationId = $displayInfo->stationId;
      }
   }
   
   return ($stationId);
}

function getStationLabel($stationId)
{
    $label = "";
   
   $stationInfo = StationInfo::load($stationId);
   
   if ($stationInfo)
   {
       $label = $stationInfo->getLabel();
   }
   
   return ($label);
}

function getCycleTime($stationId)
{
   $cycleTime = 0;
   
   $stationInfo = StationInfo::load($stationId);
   
   if ($stationInfo)
   {
      $cycleTime = $stationInfo->cycleTime;
   }
   
   return ($cycleTime);
}

function getCountButtons()
{
   echo
<<<HEREDOC
   <div class="btn btn-blob" onclick="incrementCount();">+</div>
   <div class="btn btn-small btn-blob" onclick="decrementCount();" style="position: relative; left:15px; top: 80px;">-</div>
HEREDOC;
}
   
function getBreakButton()
{
   echo
<<<HEREDOC
   <div id="break-button" class="btn btn-small btn-blob" onclick="if (!window.isOnBreak) {showModal('break-description-modal');} else {toggleBreakButton();}" style="position: relative; left:50px; top: 0px;">
      <i id="play-icon" class="material-icons" style="margin-right:5px; color: rgba(155,155,155,1); font-size: 35px;">play_arrow</i>
      <i id="pause-icon" class="material-icons" style="margin-right:5px; color: rgba(155,155,155,1); font-size: 35px;">pause</i>
   </div>
HEREDOC;
}
   
function getShiftHours()
{
   $shiftHours = "";
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getShifts();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $dateTime = new DateTime($row["startTime"]);
         $startHour = intval($dateTime->format("H"));
         
         $dateTime = new DateTime($row["endTime"]);
         $endHour = intval($dateTime->format("H"));

         $shiftHours .= 
<<<HEREDOC
         {$row["shiftId"]}: {startHour: $startHour, endHour: $endHour}, 
HEREDOC;
      }
   }
   
   return ($shiftHours);
}

$stationId = getStationId();

$stationLabel = getStationLabel($stationId);

$cycleTime = getCycleTime($stationId);

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
   <link rel="stylesheet" type="text/css" href="css/modal.css"/>
   
</head>

<body onload="update()">

   <form>
      <input id="station-id-input" type="hidden" name="stationId" value="<?php echo $stationId; ?>">
      <input id="cycle-time-input" type="hidden" name="cycleTime" value="<?php echo $cycleTime; ?>">
   </form>

   <div class="flex-vertical" style="align-items: flex-start;">
   
      <?php Header::render(true);?>
      
      <?php if (!isKioskMode()) {include 'common/menu.php';}?>
      
      <div class="main workstation" style="align-items: center; flex-wrap: wrap;">
      
         <div class="flex-vertical left-panel">
         
            <div class="flex-horizontal" style="justify-content: flex-start;">
               <div class="stat-label">Station</div>
               <div id="hardware-button-led" class="flex-horizontal"></div>
            </div>
            <div class="flex-horizontal">
               <div class="large-stat"><?php echo $stationLabel; ?></div>
               <?php if (!isKioskMode()) {getBreakButton();}?>
            </div>
            
            <br>
            
            <div class="stat-label">Average time between screens</div>
            <div id="average-count-time-div" class="large-stat"></div>
            
            <br>
            
            <div id="elapsed-time-label" class="stat-label">Time since last screen</div>
            <div id="break-time-label" class="stat-label">Paused</div>
            <div id="elapsed-time-div" class="large-stat"></div>
            <!-- div id="break-description"></div-->
            
         </div>
      
         <div class="flex-vertical right-panel">
         
            <div class="flex-horizontal">
            
               <?php if (!isKioskMode()) {getCountButtons();}?>
               
               <div class="flex-vertical" style="margin-left: 50px;">
                  <div class="stat-label">Today's screen count</div>
                  <div id="count-div" class="urgent-stat large-stat"></div>
               </div>
               
            </div>
            
            <div id="hourly-count-chart-div" style="margin-top: 50px;"></div>
            
            <div id="first-entry-div"></div>
            
         </div>
         
      </div>
      
   </div>
   
   <!--  Modal dialogs -->

   <div id="break-description-modal" class="modal">
      <div class="flex-vertical modal-content" style="width:300px;">
         <div id="close" class="close">&times;</div>
         <label>Reason for break?</label>
         <select id="break-description-id-input" form="config-form" name="breakDescriptionId">
            <?php echo BreakDescription::getBreakDescriptionOptions("");?>
         </select>
         <div class="flex-horizontal">
            <button class="config-button" type="submit" form="config-form" onclick="toggleBreakButton(); hideModal('break-description-modal');">Select</button>
         </div>
      </div>
   </div>
   
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script src="chart/chart.js"></script>
   <script src="script/flexscreen.js"></script>
   <?php if (isKioskMode()) {echo "<script src=\"script/kiosk.js\"></script>";}?>
   <script src="script/modal.js"></script>
   <script>
      // Start a timer to update the count/hourly count div.
      setInterval(function(){update();}, 3000);
   
      // Start a one-second timer to update the elapsed-time-div.
      setInterval(function(){updateElapsedTime();}, 50);

      // Store shift hours for updating the x-axis of the hourly chart.
      var shiftHours = {
         <?php echo getShiftHours(); ?>
      };
   </script>

</body>

</html>