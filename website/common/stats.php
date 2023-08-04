<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';

class Stats
{
   public static function getAverageCountTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $averageCountTime = 0;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $firstEntry = $database->getFirstEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         $lastEntry = $database->getLastEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         
         if ($firstEntry && $lastEntry && ($firstEntry != $lastEntry))
         {
            // Determine the interval between the last and first entries.  (seconds)
            $totalCountTime = Time::differenceSeconds($firstEntry, $lastEntry);
            
            // Determine the total amount of break time in this period.
            $breakTime = Stats::getBreakTime($stationId, $shiftId, $firstEntry, $lastEntry);
            
            // Subtract off breaks.
            $netCountTime = ($totalCountTime - $breakTime);
            
            // Determine the total count in the specified interval.
            $count = $database->getCount($stationId, $shiftId, $startDateTime, $endDateTime);
            
            // Account for the fact that we don't know the count time for the first entry.
            // Note: This means that we require at least two entries to compute an average.
            $netcount = ($count - 1);
            
            if ($netcount > 0)
            {
               $averageCountTime = round($netCountTime / $netcount);
            }
         }
      }
      
      return ($averageCountTime);
   }
   
   public static function getTotalCountTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $totalCountTime = 0;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $firstEntry = $database->getFirstEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         $lastEntry = $database->getLastEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         
         if ($firstEntry && $lastEntry && ($firstEntry != $lastEntry))
         {
            // Determine the interval between the last and first entries.  (seconds)
            $totalCountTime = Time::differenceSeconds($firstEntry, $lastEntry);
         }
      }
      
      return ($totalCountTime);
   }
   
   public static function getBreakTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $breakTime = 0;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreaks($stationId, $shiftId, $startDateTime, $endDateTime);
         
         foreach ($result as $row)
         {
            // Only count complete breaks.
            if ($row["endTime"] != null)
            {
               $breakTime += Time::differenceSeconds($row["startTime"], $row["endTime"]);
            }
         }
      }
      
      return ($breakTime);
   }
}

?>