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

function getGridClass($stationCount)
{
   $gridClasses = array("single", "double", "triple", "quad");
   
   $gridClass = $gridClasses[0]; // single
   
   if (($stationCount> 0) && ($stationCount <= 4))
   {
      $gridClass = $gridClasses[$stationCount - 1];
   }
   
   return ($gridClass);
}

function getChartSize($stationCount)
{
   $chartSizes = array(3, 2, 1, 1);  // large, medium, small, small
   
   $chartSize = $chartSizes[0];  // small
   
   if (($stationCount> 0) && ($stationCount <= 4))
   {
      $chartSize = $chartSizes[$stationCount - 1];
   }
   
   return ($chartSize);
}

function getStationGrid($stationIds)
{
   $html = "";
   
   $stationCount = count($stationIds);
   
   $gridClass = getGridClass($stationCount);
   
   $html =
<<<HEREDOC
   <div class="station-grid $gridClass">
HEREDOC;
   
   foreach ($stationIds as $stationId)
   {
      $html .= getStationPanel($stationId, getChartSize($stationCount));
   }
   
   $html .=
<<<HEREDOC
   </div>
HEREDOC;
   
   return ($html);
}

function getStationPanel($stationId, $chartSize)
{
   $stationLabel = "<unknown>";
   $objectName = "widget";

   $stationInfo = StationInfo::load($stationId);
   
   if ($stationInfo)
   {
      $stationLabel = $stationInfo->getLabel();
      $objectName = $stationInfo->objectName;
   }
   
   $html =
<<<HEREDOC
   <div id="station-$stationId" class="station-panel flex-vertical" style="align-items: flex-start">

      <div class="station-label">$stationLabel</div>   

      <div class="flex-horizontal" style="flex-wrap: wrap">

         <div class="stats-panel">   
         
            <div class="grid-item count flex-vertical">
               <div class="stat-label">Today's $objectName count</div>
               <div id="count-div-$stationId" class="urgent-stat large-stat"></div>
            </div>
               
            <div class="grid-item flex-vertical">
               <div class="stat-label">First $objectName</div>
               <div id="first-entry-time-div-$stationId" class="small-stat"></div>
            </div>
      
            <div class="grid-item flex-vertical">
               <div class="stat-label">Last $objectName</div>
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
            <div id="hourly-count-chart-div-$stationId" data-chart-size="$chartSize" class="hourly-count-chart"></div>
         </div>

      </div>

   </div>
HEREDOC;

   return ($html);
}

?>

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
      // Store station ids
      stationIds = [
         <?php echo getStationIdsVar(); ?>
      ];
      
      //window.addEventListener("resize", resizeCharts());
      
      // Start a timer to update the count/hourly count div.
      setInterval(function(){update(stationIds);}, 3000);
   
      // Start a one-second timer to update the elapsed-time-div.
      setInterval(function(){updateElapsedTimes(stationIds);}, 500);
   </script>

</body>

</html>