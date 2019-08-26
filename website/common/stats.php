<?php

require_once 'database.php';

class Stats
{
   static function getAverageCountTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $averageCountTime = 0;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $firstEntry = $database->getFirstEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         $lastEntry = $database->getLastEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         
         if ($firstEntry && $lastEntry && ($firstEntry != $lastEntry))
         {
            // Determine the interval between the last and first entries.  (seconds)
            $countTime = Time::differenceSeconds($firstEntry, $lastEntry);
            
            // Determine the total count in the specified interval.
            $count = $database->getCount($stationId, $shiftId, $startDateTime, $endDateTime);
            
            // We require at least two entries to compute an average as we don't know the count time for the first entry.
            if ($count > 1)
            {
               $averageCountTime = round($countTime / ($count - 1));
            }
         }
      }
      
      return ($averageCountTime);
   }
   
   // Start here!
   /*
   public function getBreakTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $breakTime = 0;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $lastEntry = $database->getLastEntry($stationId, $shiftId, $startDateTime, $endDateTime);
         
         $result = $database->getBreaks($stationId, $startDateTime, $endDateTime);
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            // Only count complete breaks.
            $isCompleteBreak = ($row["endTime"] != null);
            
            // Don't count breaks that start *after* the last screen count.
            $startTime = Time::fromMySqlDate($row["startTime"], "Y-m-d H:i:s");
            $isValidBreak = (!$lastEntry || (new DateTime($startTime) < new DateTime($lastEntry)));
            
            if ($isCompleteBreak && $isValidBreak)
            {
               $breakTime += Time::differenceSeconds($row["startTime"], $row["endTime"]);
            }
         }
      }
   }
   */
}

?>