<?php
require_once 'database.php';
require_once 'time.php';

class DailySummary
{
   public $stationId;
   public $date;
   public $count = 0;
   public $countTime = 0;
   public $firstEntry = null;
   public $lastEntry = null;

   public static function getDailySummary($stationId, $shiftId, $date)
   {
      $dailySummary = null;

      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected() && $database->stationExists($stationId))
      {
         $dailySummary = new DailySummary();

         $startOfDay = Time::startOfDay($date);
         $endOfDay = Time::endOfDay($date);

         $dailySummary->stationId = $stationId;
         $dailySummary->date = $date;
         $dailySummary->count = $database->getCount($stationId, $shiftId, $startOfDay, $endOfDay);
         $dailySummary->firstEntry = $database->getFirstEntry($stationId, $startOfDay, $endOfDay);
         $dailySummary->lastEntry = $database->getLastEntry($stationId, $startOfDay, $endOfDay);
         $dailySummary->countTime = $database->getCountTime($stationId, $shiftId, $startOfDay, $endOfDay);
      }

      return ($dailySummary);
   }

   public static function getDailySummaries($stationId, $shiftId, $startDate, $endDate)
   {
      $dailySummaries = array();

      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $firstDay = Time::startOfDay($startDate);
         $lastDay = Time::startOfDay($endDate);
         $day = $firstDay;

         $stations = array();
         if ($stationId != "ALL")
         {
            $stations[] = $stationId;
         }
         else
         {
            $result = $database->getStations();
            
            while ($result && ($row = $result->fetch_assoc()))
            {
               $stations[] = $row["stationId"];
            }
         }

         while (new DateTime($day) <= new DateTime($lastDay))
         {
            foreach ($stations as $stationId)
            {
               if (($dailySummary = DailySummary::getDailySummary($stationId, $shiftId, $day)) &&
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
   $shiftId = isset($_GET["shiftId"]) ? $_GET["shiftId"] : ShiftInfo::DEFAULT_SHIFT_ID;
   $dailySummary = DailySummary::getDailySummary($stationId, $shiftId, Time::now("Y-m-d H:i:s"));

   if ($dailySummary)
   {
      echo "stationId: " . $dailySummary->stationId .  "<br/>";
      echo "date: " .      $dailySummary->date .       "<br/>";
      echo "count: " .     $dailySummary->count .      "<br/>";
      echo "firstEntry" .  $dailySummary->firstEntry . "<br/>";
      echo "lastEntry" .   $dailySummary->lastEntry .  "<br/>";
      echo "countTime: " . $dailySummary->countTime .  "<br/>";
   }
   else
   {
      echo "No station ID found.";
   }
}
else if (isset($_GET["startDate"]) && isset($_GET["startDate"]))
{
   $stationId = isset($_GET["stationId"]) ? $_GET["stationId"] : "ALL";
   $shiftId = isset($_GET["shiftId"]) ? $_GET["shiftId"] : ShiftInfo::DEFAULT_SHIFT_ID;
   $startDate = $_GET["startDate"];
   $endDate = $_GET["endDate"];

   $dailySummaries = DailySummary::getDailySummaries($stationId, $shiftId, $startDate, $endDate);

   foreach ($dailySummaries as $dailySummary)
   {
      echo $dailySummary->stationId . "|" . $dailySummary->date . "|" . $dailySummary->count . "|" . $dailySummary->firstEntry . "|" . $dailySummary->lastEntry . "|" . $dailySummary->countTime . "<br>";
   }
}
*/
?>