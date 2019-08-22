<?php

require_once 'common/breakInfo.php';
require_once 'common/dailySummary.php';
require_once 'common/database.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';

Time::init();

class Table
{
   const UNKNOWN       = 0;
   const HOURLY_COUNTS = 1;
   const DAILY_COUNTS  = 2;
   const BREAKS        = 3;
}

function getStationId()
{
   $stationId =  "ALL";
   
   $params = Params::parse();
   
   if ($params->keyExists("stationId"))
   {
       $stationId = $params->get("stationId");
   }

   return ($stationId);
}


function getStartDate()
{
   $startDate = Time::now("Y-m-d");
   
   if (($_SERVER["REQUEST_METHOD"] === "GET") &&
      (isset($_GET["startDate"])))
   {
      $startDate = $_GET["startDate"];
   }
   else if (($_SERVER["REQUEST_METHOD"] === "POST") &&
            (isset($_PUT["startDate"])))
   {
      $startDate = $_PUT["startDate"];
   }
   
   return ($startDate);
}

function getEndDate()
{
   $endDate = Time::now("Y-m-d");
   
   if (($_SERVER["REQUEST_METHOD"] === "GET") &&
       (isset($_GET["endDate"])))
   {
      $endDate = $_GET["endDate"];
   }
   else if (($_SERVER["REQUEST_METHOD"] === "POST") &&
            (isset($_PUT["endDate"])))
   {
      $endDate = $_PUT["endDate"];
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
   $params = Params::parse();
   
   $shiftId = $params->keyExists("shiftId") ? $params->get("shiftId") : ShiftInfo::UNKNOWN_SHIFT_ID;
   
   switch (getTable())
   {
      case Table::HOURLY_COUNTS:
      {
         renderHourlyCountsTable($shiftId);
         break;
      }
      
      case Table::BREAKS:
      {
         renderBreaksTable($shiftId);
         break;
      }
      
      case Table::DAILY_COUNTS:
      default:
      {
         renderDailyCountsTable($shiftId);
         break;
      } 
   }
}

function renderDailyCountsTable($shiftId)
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Date</th>
         <th>Screen Count</th>
         <th>First Screen</th>
         <th>Last Screen</th>
         <th>Average Time Between Screens</th>
      </tr>
HEREDOC;

   $stationId = getStationId();
   $startDate = getStartDate();
   $endDate = getEndDate();
   
   $dailySummaries = DailySummary::getDailySummaries($stationId, $shiftId, $startDate, $endDate);
   
   foreach ($dailySummaries as $dailySummary)
   {
      $stationInfo = StationInfo::load($dailySummary->stationId);
      
      $dateTime = new DateTime($dailySummary->date, new DateTimeZone('America/New_York'));
      $dateString = $dateTime->format("m-d-Y");
      
      $averageCountTime = floor(($dailySummary->countTime / $dailySummary->count));
      $hours = floor(($averageCountTime / 3600));
      $minutes = floor((($averageCountTime % 3600) / 60));
      $seconds = ($averageCountTime % 60);
      
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
      if ($dailySummary->countTime > 0)
      {
         $timeString = "";
         
         if ($hours > 0)
         {
            $timeString .= $hours . " hours ";
         }
         
         if (($hours > 0) || ($minutes > 0))
         {
            $timeString .= $minutes . " minutes ";
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
            <td>$dateString</td>
            <td>$dailySummary->count</td>
            <td>$firstEntryString</td>
            <td>$lastEntryString</td>
            <td>$timeString</td>
         </tr>
HEREDOC;
   }
   
   echo "</table>";
}

function renderHourlyCountsTable($shiftId)
{
   echo
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Date</th>
         <th>Hour</th>
         <th>Screen Count</th>
      </tr>
HEREDOC;
    
   $stationId = getStationId();
   $startDate = getStartDate();
   $endDate = getEndDate();
    
   $startTime = Time::startOfDay($startDate);
   $endTime = Time::endOfDay($endDate);
    
   $database = FlexscreenDatabase::getInstance();
    
   if ($database && $database->isConnected())
   {
      $result = $database->getHourlyCounts($stationId, $shiftId, $startTime, $endTime);
        
      while ($result && ($row = $result->fetch_assoc()))
      {
         $stationInfo = StationInfo::load($row["stationId"]);
            
         $dateTime = new DateTime(Time::fromMySqlDate($row["dateTime"], "Y-m-d H:i:s"), 
                                    new DateTimeZone('America/New_York'));
         $dateString = $dateTime->format("m-d-Y");
         $hourString = $dateTime->format("h A");
           
         $count = intval($row["count"]);
            
         echo
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
               <td>$dateString</td>
               <td>$hourString</td>
               <td>$count</td>
            </tr>
HEREDOC;
      }
   }
    
   echo "</table>";
}

function renderBreaksTable($shiftId)
{
   echo
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Date</th>
         <th>Start time</th>
         <th>End time</th>
         <th>Duration</th>
      </tr>
HEREDOC;
   
   $stationId = getStationId();
   $startDate = getStartDate();
   $endDate = getEndDate();
   
   $startTime = Time::startOfDay($startDate);
   $endTime = Time::endOfDay($endDate);
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getBreaks($stationId, $startTime, $endTime);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $breakInfo = BreakInfo::load($row["breakId"]);
         
         $stationInfo = StationInfo::load($row["stationId"]);
         
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
         
         echo
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$dateString</td>
            <td>$startTimeString</td>
            <td>$endTimeString</td>
            <td>$durationString</td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function renderStationOptions()
{
   $selectedStationId = getStationId();
   
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
?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Historical Data</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   
   <style>
      body {
         color: white;
      }
      
      table, th, td {
         color: white;
         border: 1px solid white;
      }
   </style>
   
</head>

<body>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php include 'common/header.php';?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">

      <div class="flex-horizonal historical-data-filter-div">
         <form action="#">
         <label>Station ID: </label><select name="stationId"><?php renderStationOptions();?></select>
         <label>Start date: </label><input type="date" name="startDate" value="<?php echo getStartDate();?>">
         <label>End date: </label><input type="date" name="endDate" value="<?php echo getEndDate();?>">
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