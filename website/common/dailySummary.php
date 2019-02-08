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
         $count = $database->getCount($stationId, $startOfDay, $endOfDay);
         $countTime = $database->getCountTime($stationId, $startOfDay, $endOfDay);
      }
      
      return ($dailySummary);
   }
}

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
?>