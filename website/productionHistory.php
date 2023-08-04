<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/breakInfo.php';
require_once ROOT.'/common/customerInfo.php';
require_once ROOT.'/common/dailySummary.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/demo.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/stationInfo.php';
require_once ROOT.'/common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::PRODUCTION_HISTORY)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

class Table
{
   const UNKNOWN       = 0;
   const HOURLY_COUNTS = 1;
   const DAILY_COUNTS  = 2;
   const BREAKS        = 3;
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

function getFilterStationId()
{
   $stationId =  "ALL";
   
   $params = Params::parse();
   
   if ($params->keyExists("filterStationId"))
   {
       $stationId = $params->get("filterStationId");
   }

   return ($stationId);
}

function getFilterShiftId()
{
   $shiftId = ShiftInfo::getShiftId();
   
   $params = Params::parse();
   
   if ($params->keyExists("filterShiftId"))
   {
      $shiftId = $params->get("filterShiftId");
   }
   
   return ($shiftId);
}

function getFilterStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   $params = Params::parse();
   
   if ($params->keyExists("filterStartDate"))
   {
      $startDate = $params->get("filterStartDate");
   }
   
   return ($startDate);
}

function getFilterEndDate()
{
   $endDate = Time::now("Y-m-d");
   
   $params = Params::parse();
   
   if ($params->keyExists("filterEndDate"))
   {
      $endDate = $params->get("filterEndDate");
   }
   
   return ($endDate);
}

function getTable()
{
   $table = Table::DAILY_COUNTS;
   
   $params = Params::parse();
   
   switch ($params->get("display"))
   {
      case "hourly":
      {
         $table = Table::HOURLY_COUNTS;
         break;
      }
      
      case "breaks":
      {
         $table = Table::BREAKS;
         break;
      }
      
      case "daily":
      default:
      {
         $table = Table::DAILY_COUNTS;
         break;
      }
   }
   
   return ($table);
}

// *****************************************************************************
//                                 Data gathering

function getDailyCountData()
{
    $data = array();
   
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
   
   $dailySummaries = DailySummary::getDailySummaries($stationId, $shiftId, $startDate, $endDate);
   
   foreach ($dailySummaries as $dailySummary)
   {
      $stationInfo = StationInfo::load($dailySummary->stationId);
       
      $shiftInfo = ShiftInfo::load($dailySummary->shiftId);
      $shiftName = $shiftInfo ? $shiftInfo->shiftName : "---";
       
      $dateTime = Time::getDateTime($dailySummary->date);
      $dateString = $dateTime->format("m-d-Y");
              
      $countString = ($dailySummary->count > 0) ? $dailySummary->count : "---";
             
      $firstEntryString = "---";
      if ($dailySummary->firstEntry)
      {
         $dateTime = Time::getDateTime($dailySummary->firstEntry);
         $firstEntryString = $dateTime->format("h:i A");
      }
       
      $lastEntryString = "---";
      if ($dailySummary->lastEntry)
      {
         $dateTime = Time::getDateTime($dailySummary->lastEntry);
         $lastEntryString = $dateTime->format("h:i A");
      }
      
      // Total time
            
      $totalTimeString = "---";
      if ($dailySummary->totalCountTime > 0)
      {
         $hours = ($dailySummary->totalCountTime / 3600);
         $totalTimeString = number_format($hours, 2, '.', '');
      }
      
      // Average time      
       
      $averageTimeString = "---";
      if ($dailySummary->averageCountTime > 0)
      {
         $hours = floor(($dailySummary->averageCountTime / 3600));
         $minutes = floor((($dailySummary->averageCountTime % 3600) / 60));
         $seconds = ($dailySummary->averageCountTime % 60);
         
         $averageTimeString = "";
           
         if ($hours > 0)
         {
            $averageTimeString .= $hours . (($hours > 1) ? " hours " : " hour ");
         }
           
         if (($hours > 0) || ($minutes > 0))
         {
            $averageTimeString .= $minutes . (($minutes > 1) ? " minutes " : " minute ");
         }
           
         if ($hours == 0)
         {
            $averageTimeString .= $seconds . " seconds";
         }
      }
      
      $row = array();
      $row["stationName"] = $stationInfo->name;
      $row["shiftName"] = $shiftName;
      $row["date"] = $dateString;
      $row["count"] = $countString;
      $row["firstEntry"] = $firstEntryString;
      $row["lastEntry"] = $lastEntryString;
      $row["totalTime"] = $totalTimeString;
      $row["averageTime"] = $averageTimeString;
      
      $data[] = $row;
   }
   
   return ($data);
}

function getHourlyCountData()
{
   $data = array();
    
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
    
   $startTime = Time::startOfDay($startDate);
   $endTime = Time::endOfDay($endDate);
    
   $shiftInfo = ShiftInfo::load($shiftId);
   $shiftName = $shiftInfo ? $shiftInfo->shiftName : "All";
    
   $database = FactoryStatsDatabase::getInstance();
    
   if ($database && $database->isConnected())
   {
       $result = $database->getHourlyCounts($stationId, $shiftId, $startTime, $endTime);
        
       foreach ($result as $row)
       {
          $stationInfo = StationInfo::load($row["stationId"]);
            
          $shiftInfo = ShiftInfo::load($row["shiftId"]);
          $shiftName = $shiftInfo ? $shiftInfo->shiftName : "---";
           
          $dateTime = Time::getDateTime(Time::fromMySqlDate($row["dateTime"], Time::STANDARD_FORMAT));
          $dateString = $dateTime->format("m-d-Y");
          $hourString = $dateTime->format("h A");
            
          $count = intval($row["count"]);
          
          $row = array();
          $row["stationName"] = $stationInfo->name;
          $row["shiftName"] = $shiftName;
          $row["date"] = $dateString;
          $row["hour"] = $hourString;
          $row["count"] = $count;
          
          $data[] = $row;
       }
   }
    
   return ($data);
}

function getBreakData()
{
   $data = array();
   
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
   
   $startTime = Time::startOfDay($startDate);
   $endTime = Time::endOfDay($endDate);
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getBreaks($stationId, $shiftId, $startTime, $endTime);
       
      foreach ($result as $row)
      {
         $breakInfo = BreakInfo::load($row["breakId"]);
           
         $stationInfo = StationInfo::load($row["stationId"]);
           
         $shiftInfo = ShiftInfo::load($row["shiftId"]);
         $shiftName = $shiftInfo ? $shiftInfo->shiftName : "---";
           
         $startDateTime = Time::getDateTime(Time::fromMySqlDate($row["startTime"], Time::STANDARD_FORMAT));
         $dateString = $startDateTime->format("m-d-Y");
         $startTimeString = $startDateTime->format("h:i A");
           
         $endTimeString = "---";
         $durationString = "---";
         if ($row["endTime"] != null)
         {
            $endDateTime = Time::getDateTime(Time::fromMySqlDate($row["endTime"], Time::STANDARD_FORMAT));
            $endTimeString = $endDateTime->format("h:i A");
               
            $interval = $startDateTime->diff($endDateTime);
               
            if ($interval->d >= 1)
            {
               $durationString = $interval->d . " days";
            }
            else if ($interval->h >= 1)
            {
               $durationString = $interval->h . " hr " . $interval->i . " min";
            }
            else if ($interval->i >= 1)
            {
               $durationString = $interval->i . " min";
            }
            else
            {
               $durationString = "< 1 min";
            }
         }
           
         $breakDescription = BreakDescription::load($breakInfo->breakDescriptionId);
         $reason = $breakDescription ? $breakDescription->description : "";
         
         $row = array();
         $row["stationName"] = $stationInfo->name;
         $row["shiftName"] = $shiftName;
         $row["date"] = $dateString;
         $row["startTime"] = $startTimeString;
         $row["endtime"] = $endTimeString;
         $row["duration"] = $durationString;
         $row["reason"] = $reason;
         
         $data[] = $row;
      }
   }
    
   return ($data);
}

// *****************************************************************************

function renderTable()
{
   switch (getTable())
   {
      case Table::HOURLY_COUNTS:
      {
         renderHourlyCountsTable();
         break;
      }
      
      case Table::BREAKS:
      {
         renderBreaksTable();
         break;
      }
      
      case Table::DAILY_COUNTS:
      default:
      {
         renderDailyCountsTable();
         break;
      } 
   }
}

function renderDailyCountsTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Shift</th>
         <th>Date</th>
         <th>Count</th>
         <th>First Update</th>
         <th>Last Update</th>
         <th>Total Time (hours)</th>
         <th>Average Time Between Updates</th>
      </tr>
HEREDOC;

   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
   
   $dailySummaries = DailySummary::getDailySummaries($stationId, $shiftId, $startDate, $endDate);
     
   $totalCount = 0;
   
   foreach ($dailySummaries as $dailySummary)
   {
      $stationInfo = StationInfo::load($dailySummary->stationId);
      
      $shiftInfo = ShiftInfo::load($dailySummary->shiftId);
      $shiftName = $shiftInfo ? $shiftInfo->shiftName : "---";
            
      $dateTime = Time::getDateTime($dailySummary->date);
      $dateString = $dateTime->format("m-d-Y");
            
      $countString = ($dailySummary->count > 0) ? $dailySummary->count : "---";
      
      $firstEntryString = "---";
      if ($dailySummary->firstEntry)
      {
         $dateTime = Time::getDateTime($dailySummary->firstEntry);
         $firstEntryString = $dateTime->format("h:i A");
      }
      
      $lastEntryString = "---";
      if ($dailySummary->lastEntry)
      {
         $dateTime = Time::getDateTime($dailySummary->lastEntry);
         $lastEntryString = $dateTime->format("h:i A");
      }
      
      // Total time
      
      $totalTimeString = "---";
      if ($dailySummary->totalCountTime > 0)
      {
         $hours = ($dailySummary->totalCountTime / 3600);
         $totalTimeString = number_format($hours, 2, '.', '');
      }
      
      // Average time
                  
      $averageTimeString = "---";
      if ($dailySummary->averageCountTime > 0)
      {
         $hours = floor(($dailySummary->averageCountTime / 3600));
         $minutes = floor((($dailySummary->averageCountTime % 3600) / 60));
         $seconds = ($dailySummary->averageCountTime % 60);
         
         $averageTimeString = "";
         
         if ($hours > 0)
         {
            $averageTimeString .= $hours . (($hours > 1) ? " hours " : " hour ");
         }
         
         if (($hours > 0) || ($minutes > 0))
         {
            $averageTimeString .= $minutes . (($minutes > 1) ? " minutes " : " minute ");
         }
         
         if ($hours == 0)
         {
            $averageTimeString .= $seconds . " seconds";
         }
      }
      
      echo
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$shiftName</td>
            <td>$dateString</td>
            <td>$countString</td>
            <td>$firstEntryString</td>
            <td>$lastEntryString</td>
            <td>$totalTimeString</td>
            <td>$averageTimeString</td>
         </tr>
HEREDOC;
      
      $totalCount += $dailySummary->count;
   }
   
   echo
<<<HEREDOC
         <tr class="total">
            <th>Total</th>
            <td></td>
            <td></td>
            <td>$totalCount</td>
            <td></td>
            <td></td>
            <td></td>
         </tr>
HEREDOC;
   
   echo "</table>";
}

function renderHourlyCountsTable()
{
   echo
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Shift</th>
         <th>Date</th>
         <th>Hour</th>
         <th>Count</th>
      </tr>
HEREDOC;
    
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
    
   $database = FactoryStatsDatabase::getInstance();
    
   if ($database && $database->isConnected())
   {
      // Compile all selected shifts.
      $shifts = array();
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
          $shifts[] = $shiftId;
      }
      else
      {
          $result = $database->getShifts();
          
          foreach ($result as $row)
          {
              $shifts[] = $row["shiftId"];
          }
      }
      
      foreach ($shifts as $shiftId)
      {
         $shiftInfo = ShiftInfo::load($shiftId);
         if ($shiftInfo)
         {
            // Get start and end times based on the shift.
            $evaluationTimes = $shiftInfo->getEvaluationTimes($startDate, $endDate);
             
            $result = $database->getHourlyCounts($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
            
            $totalCount = 0;
            
            foreach ($result as $row)
            {
               $stationInfo = StationInfo::load($row["stationId"]);
                
               $dateTime = Time::getDateTime(Time::fromMySqlDate($row["dateTime"], Time::STANDARD_FORMAT)); 
               $dateString = $dateTime->format("m-d-Y");
               $hourString = $dateTime->format("h A");
                
               $count = intval($row["count"]);
                
               echo
<<<HEREDOC
              <tr>
                 <td>$stationInfo->name</td>
                 <td>$shiftInfo->shiftName</td>
                 <td>$dateString</td>
                 <td>$hourString</td>
                 <td>$count</td>
              </tr>
HEREDOC;
                
              $totalCount += $count;
           }            
         }
      }
   }
   
   echo
<<<HEREDOC
   <tr class="total">
      <th>Total</th>
      <td></td>
      <td></td>
      <td></td>
      <td>$totalCount</td>
   </tr>
HEREDOC;
    
   echo "</table>";
}

function renderBreaksTable()
{
   echo
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Shift</th>
         <th>Date</th>
         <th>Start time</th>
         <th>End time</th>
         <th>Duration</th>
         <th>Reason</th>
      </tr>
HEREDOC;
   
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
   
   $startTime = Time::startOfDay($startDate);
   $endTime = Time::endOfDay($endDate);
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      // Compile all selected shifts.
      $shifts = array();
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
          $shifts[] = $shiftId;
      }
      else
      {
          $result = $database->getShifts();
           
          foreach ($result as $row)
          {
              $shifts[] = $row["shiftId"];
          }
      }
       
      foreach ($shifts as $shiftId)
      {
         $shiftInfo = ShiftInfo::load($shiftId);
         if ($shiftInfo)
         {
            // Get start and end times based on the shift.
            $evaluationTimes = $shiftInfo->getEvaluationTimes($startDate, $endDate);
       
            $result = $database->getBreaks($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
      
            foreach ($result as $row)
            {
               $breakInfo = BreakInfo::load($row["breakId"]);
                 
               $stationInfo = StationInfo::load($row["stationId"]);
                 
               $startDateTime = Time::getDateTime(Time::fromMySqlDate($row["startTime"], Time::STANDARD_FORMAT));
               $dateString = $startDateTime->format("m-d-Y");
               $startTimeString = $startDateTime->format("h:i A");
                 
               $endTimeString = "---";
               $durationString = "---";
               if ($row["endTime"] != null)
               {
                  $endDateTime = Time::getDateTime(Time::fromMySqlDate($row["endTime"], Time::STANDARD_FORMAT));
                  $endTimeString = $endDateTime->format("h:i A");
                    
                  $interval = $startDateTime->diff($endDateTime);
                    
                  if ($interval->d >= 1)
                  {
                     $durationString = $interval->d . " days";
                  }
                  else if ($interval->h >= 1)
                  {
                     $durationString = $interval->h . " hr " . $interval->i . " min";
                  }
                  else if ($interval->i >= 1)
                  {
                     $durationString = $interval->i . " min";
                  }
                  else
                  {
                     $durationString = "< 1 min";
                  }
               }
                 
               $breakDescription = BreakDescription::load($breakInfo->breakDescriptionId);
               $reason = $breakDescription ? $breakDescription->description : "";
         
               echo
<<<HEREDOC
               <tr>
                  <td>$stationInfo->name</td>
                  <td>$shiftInfo->shiftName</td>
                  <td>$dateString</td>
                  <td>$startTimeString</td>
                  <td>$endTimeString</td>
                  <td>$durationString</td>
                  <td>$reason</td>
               </tr>
HEREDOC;
            }
         }
      }
   }
   
   echo "</table>";
}

function renderStationOptions()
{
   $selectedStationId = getFilterStationId();
   
   echo "<option value=\"ALL\">All stations</option>";

   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations();
      
      foreach ($result as $row)
      {
         $stationId = $row["stationId"];
         $stationName = $row["name"];
         $selected = ($stationId == $selectedStationId) ? "selected" : "";
         
         echo "<option value=\"$stationId\" $selected>$stationName</option>";
      }
   }
}

function renderShiftOptions()
{
   echo ShiftInfo::getShiftOptions(getFilterShiftId(), true);
}

function downloadCsv($header, $data, $filename)
{
   $file = fopen('./temp.csv', 'w');
   if ($file)
   {
      header("Content-Type: text/csv");
      header("Content-Disposition: attachment; filename=\"$filename\"");
      header("Pragma: no-cache");
      header("Expires: 0");
      
      if ($header)
      {
         fputcsv($file, $header);
      }
        
      foreach ($data as $row)
      {
         fputcsv($file, $row);
      }
        
      ob_clean();
      flush();
        
      readfile("./temp.csv");
   }
}

// *****************************************************************************
//                                 Begin

$params = getParams();

if ($params->keyExists("action") &&
    ($params->get("action") == "download"))
{
   $data = null;
   $header = null;
   $filename = "";
   
   switch (getTable())
   {
      case Table::DAILY_COUNTS:
      {
         $header = array("Workstation", "Shift", "Date", "Count", "First Update", "Last Update", "Total Time (hours)", "Average Time Between Updates");
         $data = getDailyCountData();
         $filename = "dailyCounts.csv";
         break;
      }
      
      case Table::HOURLY_COUNTS:
      {
         $header = array("Workstation", "Shift", "Date", "Hour", "Count");
         $data = getHourlyCountData();
         $filename = "hourlyCounts.csv";
         break;
      }
      
      case Table::BREAKS:
      {
         $header = array("Workstation", "Shift", "Date", "Start time", "End time", "Duration", "Reason");
         $data = getBreakData();
         $filename = "breaks.csv";
         break;
      }
      
      default:
      {
          break;
      }
   }
   
   downloadCsv($header, $data, $filename);
   
   die;
}
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Historical Data</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   
   <script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
   <script src="script/productionHistory.js<?php echo versionQuery();?>"></script>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">

      <div class="flex-horizonal historical-data-filter-div">
         <form id="filter-form" action="#">
            <input id="action-input" type="hidden" name="action" value="">
            <label>Station ID: </label><select name="filterStationId"><?php renderStationOptions();?></select>
            <label>Shift: </label><select name="filterShiftId"><?php renderShiftOptions();?></select>
            <label>Start date: </label><input type="date" name="filterStartDate" value="<?php echo getFilterStartDate();?>">
            <label>End date: </label><input type="date" name="filterEndDate" value="<?php echo getFilterEndDate();?>">
            <label>Daily stats</label><input type="radio" name="display" value="daily" <?php echo (getTable() == Table::DAILY_COUNTS) ? "checked" : "";?>>
            <label>Hourly stats</label><input type="radio" name="display" value="hourly" <?php echo (getTable() == Table::HOURLY_COUNTS) ? "checked" : "";?>>
            <label>Breaks</label><input type="radio" name="display" value="breaks" <?php echo (getTable() == Table::BREAKS) ? "checked" : "";?>>
            <button type="submit">Filter</button>
         </form>
      </div>
   
      <?php renderTable();?>
      
      <br>
      <button onclick="exportCsv()">Export as CSV</button>
   
   </div>
     
</div>

<script>
   setMenuSelection(MenuItem.PRODUCTION_HISTORY);
</script>

<?php
   if (Demo::isDemoSite() && !Demo::showedInstructions(Permission::PRODUCTION_HISTORY))
   {
      Demo::setShowedInstructions(Permission::PRODUCTION_HISTORY, true);
      
      $versionQuery = versionQuery();
      
      echo
<<<HEREDOC
   <script src="script/demo.js$versionQuery"></script>
   <script>
      var demo = new Demo();
      demo.startSimulation();
   </script>
   
   <div id="demo-modal" class="modal">
      <div class="flex-vertical modal-content demo-modal-content">
         <div id="close" class="close">&times;</div>
         <p class="demo-modal-title">Production History page</p>         
         <p>The real power of Factory Stats comes with the aggregation of data over time. By drilling down into each day's product counts you can spots trends, track downtime, and maximize your facility's potential.</p>         <p>We're continually exploring new and exciting ways to report on your data, and you always have the option to download the numbers into Excel for more detailed analysis.</p>
      </div>
   </div>

   <script src="script/modal.js$versionQuery"></script>
   <script>showModal("demo-modal");</script>
HEREDOC;
   }
?>

</body>

</html>