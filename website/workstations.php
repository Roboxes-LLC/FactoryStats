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

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getStationIds()
{
   $stationIds = array();
   
   $params = getParams();
   
   if ($params->keyExists("stationIds"))
   {
      $stationIds = $params->get("stationIds");
   }
   else if ($params->keyExists("stationId"))
   {
      $stationIds[] = $params->getInt("stationId");
   }
   
   return ($stationIds);
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
   
function getShiftHoursVar()
{
   $shiftHours = "";
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getShifts();
      
      while ($result && ($row = $result->fetch_assoc()))
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

function getStationIdsVar()
{
   $stationIdsVar = "";
   
   $stationIds = getStationIds();
   
   foreach ($stationIds as $stationId)
   {
      $stationIdsVar .= $stationId . ", ";
   }
   
   return ($stationIdsVar);
}

function getStationGrid($stationIds)
{
   $html = "";
   
   $stationCount = count($stationIds);
   
   $gridClasses = array("single", "double", "triple", "quad");
   
   $gridClass = "single";
   if (($stationCount> 0) && ($stationCount <= 4))
   {
      $gridClass = $gridClasses[$stationCount - 1];
   }
   
   $html =
<<<HEREDOC
   <div class="station-grid $gridClass">
HEREDOC;
   
   foreach ($stationIds as $stationId)
   {
      $html .= getStationPanel($stationId);
   }
   
   $html .=
<<<HEREDOC
   </div>
HEREDOC;
   
   return ($html);
}

function getStationPanel($stationId)
{
   $stationLabel = getStationLabel($stationId);
   
   $html =
<<<HEREDOC
   <div id="station-$stationId" class="station-panel flex-vertical">

      <div class="station-label">$stationLabel</div>   

      <div class="flex-horizontal">

         <div class="stats-panel">   
         
            <div class="grid-item count flex-vertical">
               <div class="stat-label">Today's screen count</div>
               <div id="count-div-$stationId" class="urgent-stat large-stat"></div>
            </div>
               
            <div class="grid-item flex-vertical">
               <div class="stat-label">First screen</div>
               <div id="first-entry-time-div-$stationId" class="small-stat"></div>
            </div>
      
            <div class="grid-item flex-vertical">
               <div class="stat-label">Last screen</div>
               <div id="last-entry-time-div-$stationId" class="small-stat"></div>
            </div>
               
            <div class="grid-item flex-vertical">
               <div class="stat-label">Average time</div>
               <div id="average-count-time-div-$stationId" class="small-stat"></div>
            </div>
      
            <div class="grid-item flex-vertical">
              <div id="elapsed-time-label-$stationId" class="stat-label">Elapsed time</div>
              <div id="break-time-label-$stationId" class="stat-label">Paused</div>
              <div id="elapsed-time-div-$stationId" class="small-stat"></div>
            </div>
         </div>
   
         <div class="grid-item flex-vertical">
            <div id="hourly-count-chart-div-$stationId" class="hourly-count-chart"></div>
         </div>

      </div>

   </div>
HEREDOC;

   return ($html);
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Factory Stats - Workstations</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/workstations.css<?php echo versionQuery();?>"/>
   
</head>

<body class="flex-vertical" onload="initializeCharts();  update();">
   
   <?php Header::render(true); ?>
   
   <?php echo getStationGrid(getStationIds()); ?>
   
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script src="chart/chart.js<?php echo versionQuery();?>"></script>
   <script src="script/workstations.js<?php echo versionQuery();?>"></script>
   <?php if (isKioskMode()) {echo "<script src=\"script/kiosk.js\"" . versionQuery() . "></script>";}?>
   
   <script>
      // Store shift hours for updating the x-axis of the hourly chart.
      shiftHours = {
         <?php echo getShiftHoursVar(); ?>
      };
      
      // Store station ids
      stationIds = [
         <?php echo getStationIdsVar(); ?>
      ];
      
      // Start a timer to update the count/hourly count div.
      setInterval(function(){update(stationIds);}, 3000);
   
      // Start a one-second timer to update the elapsed-time-div.
      setInterval(function(){updateElapsedTimes(stationIds);}, 50);
   </script>

</body>

</html>