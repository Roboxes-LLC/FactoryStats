<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/kiosk.php';
require_once ROOT.'/common/version.php';

session_start();

Authentication::authenticate();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::WORKSTATION)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function getStationId()
{
   static $stationId = null;
   
   if ($stationId == null)
   {
      $params = Params::parse();
      
      $stationId = $params->getInt("stationId");
   }
   
   return ($stationId);
   
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

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Factory Stats</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
   <script src="/script/common/common.js<?php echo versionQuery();?>"></script>
   <script src="/script/common/chart.js<?php echo versionQuery();?>"></script>
   <script src="/script/page/cycleTime.js<?php echo versionQuery();?>"></script>
   <?php if (isKioskMode()) {echo "<script src=\"script/kiosk.js\"" . versionQuery() . "></script>";}?>
   
   <style>
      #cycle-time-table-container {
         width: 1000px; 
         height: 400px;
      }
   
      table {
         cell-padding: 0;
         cell-spacing: 0;
      }
      
      .cycle-time-cell {
         width: 25px;
         height:45px;
      }
      
      .cycle-time-cell.first {
         border-color: lime;
         border-width: 4px;
      }
      
      .cycle-time-cell.tolerance-good {
         background: green;
      }
      
      .cycle-time-cell.tolerance-fair {
         background: orange;
      }
      
      .cycle-time-cell.tolerance-poor {
         background: red;
      }
      
      .cycle-time-cell.tolerance-bad {
         background: #8A0000;
      }
      
      .cycle-time-cell.unknown {
         background: gray;
      }
      
      .data-filter-container {
         margin-bottom: 50px;
      }
      
      .data-filter-container select {
         margin-right: 25px;
      }
      
      .data-filter-container label {
         margin-right: 10px;
      }
      
      .download-link {
         margin-left: 20px;
         text-decoration: underline;
         cursor: pointer;
      }
   </style>
   
   
</head>

<body onload="initializeChart(); update()">

   <form>
      <input id="station-id-input" type="hidden" name="stationId" value="<?php echo $stationId; ?>">
   </form>

   <div class="flex-vertical" style="align-items: flex-start;">
   
      <?php Header::render(false);?>
      
      <?php if (!isKioskMode()) {include 'common/menu.php';}?>
      
      <div class="main">
      
         <div class="flex-vertical">
         
            <div class="flex-horizontal data-filter-container">
               <label>Station: </label><select id="station-input"><option value="61">Wire Bending (a)</option></select>
               <label>Shift: </label><select id="shift-input"><?php echo ShiftInfo::getShiftOptions(ShiftInfo::getShift(Time::now()), false) ?></select>
               <label>Manufacture date: </label><input id="mfg-date-input" type="date" value="<?php echo Time::toJavascriptDate(Time::now()) ?>">
               <label id="download-link" class="download-link" style="margin-left:20px">Download Data</label>
            </div>
            
            <div class="flex-horizontal">
            
               <!--  div id="cycle-time-chart-container" style="width: 800px; height: 400px; margin-right: 50px;"></div-->
         
               <div id="cycle-time-table-container"></div>
         
            </div>
      </div>
      
   </div>

   <script>
      var PAGE = new CycleTime();
      PAGE.setTolerance(14.9, 15.9, 30);
   </script>

</body>

</html>