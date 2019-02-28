<?php
require_once 'database.php';
require_once 'time.php';

class DailySummary
{
   public $stationId;
   public $date;
   public $count = 0;
   public $countTime = 0;

   public static function getDailySummary($stationId, $date)
   {
      $dailySummary = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if (($database->isConnected()) &&
          ($database->stationExists($stationId)))
      {
         $dailySummary = new DailySummary();
         
         $startOfDay = Time::startOfDay($date);
         $endOfDay = Time::endOfDay($date);
         
         $dailySummary->stationId = $stationId;
         $dailySummary->date = $date;
         $dailySummary->count = $database->getCount($stationId, $startOfDay, $endOfDay);
         $dailySummary->countTime = $database->getCountTime($stationId, $startOfDay, $endOfDay);
      }
      
      return ($dailySummary);
   }
   
   public static function getDailySummaries($stationId, $startDate, $endDate)
   {
      $dailySummaries = array();
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $firstDay = Time::startOfDay($startDate);
         $lastDay = Time::startOfDay($endDate);
         $day = $firstDay;
         
         $stations = array();
         $stationCount = 0;
         if ($stationId != "ALL")
         {
            $station = array();
            $station["stationId"] = $stationId;
            $stations[] = $station;
            $stationCount = 1;
         }
         else
         {
            $result = $database->getStations();
            
            if ($result && ($result->num_rows > 0))
            {
               $stations = $result->fetch_all(MYSQLI_ASSOC);
               $stationCount = $result->num_rows;
            }
         }
            
         while (new DateTime($day) <= new DateTime($lastDay))
         {
            for ($i = 0; $i < $stationCount; $i++)
            {
               $stationId = $stations[$i]["stationId"];
               
               if (($dailySummary = DailySummary::getDailySummary($stationId, $day)) &&
                   ($dailySummary->count > 0))
               {
                  $dailySummaries[] = $dailySummary;
               }
            }
            
            $day = Time::incrementDay($day);
         }
      }
      
      return ($dailySummaries);
   }
}

/*
if (isset($_GET["stationId"]))
{
   $stationId = $_GET["stationId"];
   $dailySummary = DailySummary::getDailySummary($stationId, Time::now("Y-m-d H:i:s"));
    
   if ($dailySummary)
   {
      echo "stationId: " . $dailySummary->stationId . "<br/>";
      echo "date: " .      $dailySummary->date .      "<br/>";
      echo "count: " .     $dailySummary->count .     "<br/>";
      echo "countTime: " . $dailySummary->countTime . "<br/>";
   }
   else
   {
      echo "No station ID found.";
   }
}
else if (isset($_GET["startDate"]) && isset($_GET["startDate"]))
{
   $stationId = isset($_GET["stationId"]) ? $_GET["stationId"] : "ALL";
   $startDate = $_GET["startDate"];
   $endDate = $_GET["endDate"];
    
   $dailySummaries = DailySummary::getDailySummaries($stationId, $startDate, $endDate);
    
   foreach ($dailySummaries as $dailySummary)
   {
      echo $dailySummary->stationId . "|" . $dailySummary->date . "|" . $dailySummary->count . "|" . $dailySummary->countTime . "<br>";
   }
}
*/
?>