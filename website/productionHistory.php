<?php

require_once 'common/breakInfo.php';
require_once 'common/dailySummary.php';
require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/shiftInfo.php';
require_once 'common/stationInfo.php';

Time::init();

session_start();

class Table
{
   const UNKNOWN       = 0;
   const HOURLY_COUNTS = 1;
   const DAILY_COUNTS  = 2;
   const BREAKS        = 3;
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
         <th>Screen Count</th>
         <th>First Screen</th>
         <th>Last Screen</th>
         <th>Average Time Between Screens</th>
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
            
      $dateTime = new DateTime($dailySummary->date, new DateTimeZone('America/New_York'));
      $dateString = $dateTime->format("m-d-Y");
      
      $hours = floor(($dailySummary->averageCountTime / 3600));
      $minutes = floor((($dailySummary->averageCountTime % 3600) / 60));
      $seconds = ($dailySummary->averageCountTime % 60);
      
      $countString = ($dailySummary->count > 0) ? $dailySummary->count : "---";
      
      $firstEntryString = "---";
      if ($dailySummary->firstEntry)
      {
         $dateTime = new DateTime($dailySummary->firstEntry, new DateTimeZone('America/New_York'));
         $firstEntryString = $dateTime->format("h:i A");
      }
      
      $lastEntryString = "---";
      if ($dailySummary->lastEntry)
      {
         $dateTime = new DateTime($dailySummary->lastEntry, new DateTimeZone('America/New_York'));
         $lastEntryString = $dateTime->format("h:i A");
      }
      
      $timeString = "---";
      if ($dailySummary->averageCountTime > 0)
      {
         $timeString = "";
         
         if ($hours > 0)
         {
            $timeString .= $hours . (($hours > 1) ? " hours " : " hour ");
         }
         
         if (($hours > 0) || ($minutes > 0))
         {
            $timeString .= $minutes . (($minutes > 1) ? " minutes " : " minute ");
         }
         
         if ($hours == 0)
         {
            $timeString .= $seconds . " seconds";
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
            <td>$timeString</td>
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
         <th>Screen Count</th>
      </tr>
HEREDOC;
    
   $stationId = getFilterStationId();
   $shiftId = getFilterShiftId();
   $startDate = getFilterStartDate();
   $endDate = getFilterEndDate();
       
   $shiftInfo = ShiftInfo::load($shiftId);
   $shiftName = $shiftInfo ? $shiftInfo->shiftName : "All";
    
   $database = FlexscreenDatabase::getInstance();
    
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
          
          while ($result && ($row = $result->fetch_assoc()))
          {
              $shifts[] = $row["shiftId"];
          }
      }
      
      foreach ($shifts as $shiftId)
      {
         $shiftInfo = ShiftInfo::load($shiftId);
         if ($shiftInfo)
         {
            $startTime = null;
            $endTime = null;
            
            // If the specified shift is configured to span two days (ex. 11pm to 1am) then gather
            // data from the middle of the first day to the middle of the last day.
            if ($shiftInfo->shiftSpansDays())
            {
                $startTime = Time::midDay($startDate);
                $endTime = Time::midDay(Time::incrementDay($endDate));  // TODO: This will pick up 12pm entries.
            }
            else 
            {
               $startTime = Time::startOfDay($startDate);
               $endTime = Time::endOfDay($endDate);
            }
            
            $result = $database->getHourlyCounts($stationId, $shiftId, $startTime, $endTime);
            
            $totalCount = 0;
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               $stationInfo = StationInfo::load($row["stationId"]);
                
               $dateTime = new DateTime(Time::fromMySqlDate($row["dateTime"], 
                                        "Y-m-d H:i:s"),
                                        new DateTimeZone('America/New_York'));
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
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getBreaks($stationId, $shiftId, $startTime, $endTime);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $breakInfo = BreakInfo::load($row["breakId"]);
         
         $stationInfo = StationInfo::load($row["stationId"]);
         
         $shiftInfo = ShiftInfo::load($row["shiftId"]);
         $shiftName = $shiftInfo ? $shiftInfo->shiftName : "---";
         
         $startDateTime = new DateTime(Time::fromMySqlDate($row["startTime"], "Y-m-d H:i:s"),
            new DateTimeZone('America/New_York'));
         $dateString = $startDateTime->format("m-d-Y");
         $startTimeString = $startDateTime->format("h:i A");
         
         $endTimeString = "---";
         $durationString = "---";
         if ($row["endTime"] != null)
         {
            $endDateTime = new DateTime(Time::fromMySqlDate($row["endTime"], "Y-m-d H:i:s"),
               new DateTimeZone('America/New_York'));
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
            <td>$shiftName</td>
            <td>$dateString</td>
            <td>$startTimeString</td>
            <td>$endTimeString</td>
            <td>$durationString</td>
            <td>$reason</td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function renderStationOptions()
{
   $selectedStationId = getFilterStationId();
   
   echo "<option value=\"ALL\">All stations</option>";

   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && ($row = $result->fetch_assoc()))
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
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Historical Data</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">

      <div class="flex-horizonal historical-data-filter-div">
         <form action="#">
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

<script src="script/flexscreen.js"></script>
<script src="script/historicalData.js"></script>
<script>
   setMenuSelection(MenuItem.PRODUCTION_HISTORY);
</script>

</body>

</html>