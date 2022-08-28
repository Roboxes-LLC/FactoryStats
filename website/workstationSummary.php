<?php

require_once 'common/database.php';
require_once 'common/demo.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/shiftInfo.php';
require_once 'common/stationInfo.php';
require_once 'common/workstationStatus.php';
require_once 'common/version.php';

Time::init();

session_start();

Authentication::authenticate();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::WORKSTATION_SUMMARY)))
{
   header('Location: index.php?action=logout');
   exit;
}

function getGroupId()
{
   $groupId = StationGroup::UNKNOWN_GROUP_ID;
   
   $params = Params::parse();
   
   if ($params->keyExists("groupId"))
   {
      $groupId = $params->getInt("groupId");
   }
   
   return ($groupId);
}

function renderStationSummaries($shiftId, $groupId = StationGroup::UNKNOWN_GROUP_ID)
{
   echo "<div class=\"flex-horizontal main summary\">";
   
   $result = null;
   if ($groupId != StationGroup::UNKNOWN_GROUP_ID)
   {
      $result = FactoryStatsDatabase::getInstance()->getStationsForGroup($groupId);
   }
   else 
   {
      $result = FactoryStatsDatabase::getInstance()->getStations();
   }
   
   foreach ($result as $row)
   {
      $stationId = $row["stationId"];
      
      $hideOnSummary = filter_var($row["hideOnSummary"], FILTER_VALIDATE_BOOLEAN);

      if (!$hideOnSummary)
      {
         renderStationSummary($stationId, $shiftId);
      }
   }
   
   echo "</div>";
}

function renderStationSummary($stationId, $shiftId)
{
   $url= "workstation.php?stationId=" . $stationId;

   echo "<a href=\"$url\"><div id=\"workstation-summary-$stationId\" class=\"flex-vertical station-summary-div\">";
   
   $stationInfo = StationInfo::load($stationId);
   
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);
   
   $objectName = $stationInfo->objectName;
   $objectNamePlural = $stationInfo->getObjectNamePlural();
   
   if ($stationInfo && $workstationStatus)
   {
      echo 
<<<HEREDOC
      <div class="flex-horizontal" style="justify-content: flex-start;">
         <div class="station-label">{$stationInfo->getLabel()}</div>
         <!--div class="flex-horizontal hardware-button-led"></div-->
      </div>

      <div class="flex-vertical">
         <div class="stat-label">Today's $objectName count</div>
         <div class="large-stat urgent-stat count-div"></div>
      </div>
      
      <div class="stat-label">Average time between $objectNamePlural</div>
      <div class="medium-stat average-count-time-div"></div>
      
      <div class="stat-label">Time of last $objectName</div>
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
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/workstationSummary.css<?php echo versionQuery();?>"/>
   
</head>

<body onload="update()">

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(true, !isKioskMode(), !isKioskMode());?>
   
   <?php if (!isKioskMode()) {include 'common/menu.php';}?>
   
   <?php renderStationSummaries(ShiftInfo::getShiftId(), getGroupId());?>
     
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<?php if (isKioskMode()) {echo "<script src=\"script/kiosk.js\"" . versionQuery() . "></script>";}?>
<script src="script/common.js<?php echo versionQuery();?>"></script>
<script src="script/workstationSummary.js<?php echo versionQuery();?>"></script>
<script>
   // Set menu selection.
   setMenuSelection(MenuItem.WORKSTATION_SUMMARY);

   // Start a timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);
</script>

<?php
   if (Demo::isDemoSite())
   {
      $versionQuery = versionQuery();
      
      echo 
<<<HEREDOC
   <script src="script/demo.js$versionQuery"></script>
   <script>
      var demo = new Demo();
      demo.startSimulation();
   </script>
HEREDOC;

      if (!Demo::showedInstructions(Permission::WORKSTATION_SUMMARY))
      {
         Demo::setShowedInstructions(Permission::WORKSTATION_SUMMARY, true);
         
         echo
<<<HEREDOC
   <div id="demo-modal" class="modal">
      <div class="flex-vertical modal-content demo-modal-content">
         <div id="close" class="close">&times;</div>
         <p class="demo-modal-title">Workstation Summary page</p>         
         <p>Counts from every workstation are summarized in real-time here. No matter where you are in the world, as long as you have an Internet connected screen, you can see exactly how your floor is operating.</p>
         <p>Click on an individual station to get an even more detailed look at the data.</p>
         <p>Note: Real-time data is simulated here for demonstration purposes.</p>
      </div>
   </div>

   <script src="script/modal.js$versionQuery"></script>
   <script>showModal("demo-modal");</script>
HEREDOC;
      }
   }
?>


</body>

</html>