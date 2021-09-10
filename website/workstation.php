<?php

require_once 'common/breakDescription.php';
require_once 'common/dailySummary.php';
require_once 'common/database.php';
require_once 'common/demo.php';
require_once 'common/displayInfo.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/shiftInfo.php';
require_once 'common/stationInfo.php';
require_once 'common/time.php';
require_once 'common/version.php';

Time::init();

session_start();

Authentication::authenticate();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::WORKSTATION)))
{
   header('Location: index.php?action=logout');
   exit;
}

function getStationId()
{
   static $stationId = null;
   
   if (!$stationId)
   {
      $stationId = StationInfo::UNKNOWN_STATION_ID;
   
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
   }
   
   return ($stationId);
}

function getStationInfo()
{
   static $stationInfo = null;
   
   if (!$stationInfo)
   {
      $stationId = getStationId();
      
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationInfo = StationInfo::load($stationId);
      }
   }
   
   return ($stationInfo);
}

function getStationLabel()
{
   $label = "";
   
   $stationInfo = getStationInfo();
   
   if ($stationInfo)
   {
       $label = $stationInfo->getLabel();
   }
   
   return ($label);
}

function getCycleTime()
{
   $cycleTime = 0;
   
   $stationInfo = getStationInfo();
   
   if ($stationInfo)
   {
      $cycleTime = $stationInfo->cycleTime;
   }
   
   return ($cycleTime);
}

function getObjectName()
{
   $objectName = "widget";
   
   $stationInfo = getStationInfo();
   
   if ($stationInfo)
   {
      $objectName = $stationInfo->objectName;
   }
   
   return ($objectName);
}

function getObjectNamePlural()
{
   $objectNamePlural = "widgets";
   
   $stationInfo = getStationInfo();
   
   if ($stationInfo)
   {
      $objectNamePlural = $stationInfo->getObjectNamePlural();
   }
   
   return ($objectNamePlural);
}

function getCountButtons()
{
   echo
<<<HEREDOC
   <div class="btn btn-blob" onclick="if (shouldValidateShift()) {validatingAction = ValidatingAction.INCREMENT_COUNT; updateShiftValidationText(); showModal('shift-validation-modal');} else {incrementCount();}">+</div>
   <div class="btn btn-small btn-blob" onclick="if (shouldValidateShift()) {validatingAction = ValidatingAction.DECREMENT_COUNT; updateShiftValidationText(); showModal('shift-validation-modal');} else {decrementCount();}" style="position: relative; left:15px; top: 80px;">-</div>
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
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getShifts();
      
      foreach ($result as $row)
      {
         $shiftName = $row["shiftName"];
         
         $dateTime = new DateTime($row["startTime"]);
         $startHour = intval($dateTime->format("H"));
         $startTime = $dateTime->format("g:i A");
         
         $dateTime = new DateTime($row["endTime"]);
         $endHour = intval($dateTime->format("H"));
         $endTime = $dateTime->format("g:i A");
         
         $shiftHours .=
<<<HEREDOC
         {$row["shiftId"]}: {shiftName: "$shiftName", startTime: "$startTime", startHour: $startHour, endTime: "$endTime", endHour: $endHour},
HEREDOC;
      }
   }
   
   return ($shiftHours);
}

$stationId = getStationId();

$stationLabel = getStationLabel();

$cycleTime = getCycleTime();

$objectName = getObjectName();
$objectNamePlural = getObjectNamePlural();

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Flexscreen Counter</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/button.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body onload="initializeChart(); update()">

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
               <!--div id="hardware-button-led" class="flex-horizontal"></div-->
            </div>
            <div class="flex-horizontal">
               <div class="large-stat"><?php echo $stationLabel; ?></div>
               <?php if (!isKioskMode()) {getBreakButton();}?>
            </div>
            
            <br>
            
            <div class="stat-label">Average time between <?php echo $objectNamePlural; ?></div>
            <div id="average-count-time-div" class="large-stat"></div>
            
            <br>
            
            <div id="elapsed-time-label" class="stat-label">Time since last <?php echo $objectName; ?></div>
            <div id="break-time-label" class="stat-label">Paused</div>
            <div id="elapsed-time-div" class="large-stat"></div>
            <!-- div id="break-description"></div-->
            
         </div>
      
         <div class="flex-vertical right-panel">
         
            <div class="flex-horizontal">
            
               <?php if (!isKioskMode()) {getCountButtons();}?>
               
               <div class="flex-vertical" style="margin-left: 50px;">
                  <div class="stat-label">Today's <?php echo $objectName; ?> count</div>
                  <div id="count-div" class="urgent-stat large-stat"></div>
               </div>
               
            </div>
            
            <div id="hourly-count-chart-div" style="margin-top: 50px;"></div>
            
            <div class="flex-horizontal first-entry">
               <div>Time of first <?php echo $objectName; ?>:&nbsp&nbsp</div>
               <div id="first-entry-div"></div>
            </div>
            
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
   
   <div id="shift-validation-modal" class="modal">
      <div class="flex-vertical modal-content" style="width:300px;">
         <div id="close" class="close">&times;</div>
         <div class="flex-vertical">
            <div style="color:orange"><b>Shift Warning</b></div>
            <br>
            <div id="shift-validation-template" style="display:none">It looks like you are updating values for <b>%shiftName</b> which runs from %shiftStart to %shiftEnd. Is this correct?</div>            
            <div id="shift-validation-text"></div>
            <br>
            <div class="flex-horizontal">
               <input id="silence-shift-validation-input" type="checkbox" id="accept">&nbsp;&nbsp;Don't ask me again.
            </div>
            <br>
            <div class="flex-horizontal"><button class="config-button" onclick="onShiftValidated(); hideModal('shift-validation-modal');">Yes</button>&nbsp;&nbsp;<button class="config-button" onclick="hideModal('shift-validation-modal');">Cancel</button></div>
         </div>
      </div>
   </div>
   
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script src="chart/chart.js<?php echo versionQuery();?>"></script>
   <script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
   <?php if (isKioskMode()) {echo "<script src=\"script/kiosk.js\"" . versionQuery() . "></script>";}?>
   <script src="script/modal.js<?php echo versionQuery();?>"></script>
   <script>
      // Start a timer to update the count/hourly count div.
      setInterval(function(){update();}, 10000);
   
      // Start a one-second timer to update the elapsed-time-div.
      setInterval(function(){updateElapsedTime();}, 500);
      
      // Store shift hours for updating the x-axis of the hourly chart.
      shiftHours = {
         <?php echo getShiftHours(); ?>
      };
   </script>
   
<?php
   if (Demo::isDemoSite() && !Demo::showedInstructions(Permission::WORKSTATION))
   {
      Demo::setShowedInstructions(Permission::WORKSTATION, true);
      
      $versionQuery = versionQuery();
      
      echo
<<<HEREDOC
   <div id="demo-modal" class="modal">
      <div class="flex-vertical modal-content demo-modal-content">
         <div id="close" class="close">&times;</div>
         <p class="demo-modal-title">Workstation page</p>         
         <p>This is the main interface for counting completed products at each physical workstation. Besides being able to update product counts, a line worker can use a number of other statistics to monitor their pace throughout he day.</p>
         <p>Press the large '+' button in increment the count at this station.  Station counts can also be decremented or paused from here.</p>
      </div>
   </div>

   <script src="script/modal.js$versionQuery"></script>
   <script>showModal("demo-modal");</script>
HEREDOC;
   }
?>   

</body>

</html>