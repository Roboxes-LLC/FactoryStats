<?php

require_once 'common/dailySummary.php';
require_once 'common/database.php';
require_once 'common/stationInfo.php';

function getStationId()
{
   $stationId =  "ALL";
   
   if (($_SERVER["REQUEST_METHOD"] === "GET") &&
       (isset($_GET["stationId"])))
   {
      $stationId = $_GET["stationId"];
   }
   else if (($_SERVER["REQUEST_METHOD"] === "POST") &&
            (isset($_PUT["stationId"])))
   {
      $stationId = $_PUT["stationId"];
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

function renderTable()
{
   echo 
<<<HEREDOC
   <table class="historical-data-table">
      <tr>
         <th>Station ID</th>
         <th>Date</th>
         <th>Screen Count</th>
         <th>Average Time Between Screens</th>
      </tr>
HEREDOC;

   $stationId = getStationId();
   $startDate = getStartDate();
   $endDate = getEndDate();
   
   $dailySummaries = DailySummary::getDailySummaries($stationId, $startDate, $endDate);
   
   foreach ($dailySummaries as $dailySummary)
   {
      $stationInfo = StationInfo::load($dailySummary->stationId);
      
      $dateTime = new DateTime($dailySummary->date, new DateTimeZone('America/New_York'));
      $dateString = $dateTime->format("m-d-Y");
      
      $averageCountTime = round(($dailySummary->countTime / $dailySummary->count), 0);
      $hours = round(($averageCountTime / 3600), 0);
      $minutes = round((($averageCountTime % 3600) / 60), 0);
      $seconds = ($averageCountTime % 60);
      
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
      
      echo
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$dateString</td>
            <td>$dailySummary->count</td>
            <td>$timeString</td>
         </tr>
HEREDOC;
   }
   
   echo "</table>";
}

function renderStationOptions()
{
   $selectedStationId = getStationId();
   
   echo "<option value=\"ALL\" $selected>All stations</option>";

   $database = new FlexscreenDatabase();

   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $stationId = $row["stationId"];
         $selected = ($stationId == $selectedStationId) ? "selected" : "";
         
         echo "<option value=\"$stationId\" $selected>$stationId</option>";
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