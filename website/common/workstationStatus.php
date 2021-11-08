<?php

require_once 'buttonInfo.php';
require_once 'breakInfo.php';
require_once 'buttonInfo.php';
require_once 'cycleTimeStatus.php';
require_once 'database.php';
require_once 'stats.php';
require_once 'time.php';

class WorkstationStatus
{
   public $stationId;
   public $shiftId;
   public $label;
   public $count;
   public $hourlyCount;
   public $firstEntry;
   public $updateTime;
   public $averageCountTime;
   public $hardwareButtonStatus;
   public $cycleTimeStatus;
   public $cycleTimeStatusLabel;
   public $isOnBreak;
   
   public static function getWorkstationStatus($stationId, $shiftId)
   {
      $workstationStatus = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      $shiftInfo = ShiftInfo::load($shiftId);

      if ($database && 
          $database->isConnected() && 
          $database->stationExists($stationId) &&
          $shiftInfo)
      {
         $workstationStatus = new WorkstationStatus();
         
         $dateTime = Time::now("Y-m-d H:i:s");
         
         // If we're viewing a shift that spans days, we may actually want to compile the stats from the previous day, 
         // depending on the when this is being viewed.
         if ($shiftInfo->shiftSpansDays() &&
             ($dateTime < Time::midDay($dateTime)))
         {
             $dateTime = Time::decrementDay($dateTime);
         }
         
         // Get start and end times based on the shift.
         $evaluationTimes = $shiftInfo->getEvaluationTimes($dateTime, $dateTime);
         
         // Get start and end times based on the shift.
         $shiftTimes = $shiftInfo->getShiftTimes($dateTime);
         
         $workstationStatus->stationId = $stationId;
         
         $workstationStatus->shiftId = $shiftId;
         
         $workstationStatus->shiftStartTime = $shiftTimes->startDateTime;
         $workstationStatus->shiftEndTime = $shiftTimes->endDateTime;
         
         $workstationStatus->label = WorkStationStatus::getWorkstationLabel($stationId, $database);
         
         $workstationStatus->count = WorkstationStatus::getCount($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime, $database);
         
         $workstationStatus->hourlyCount = WorkstationStatus::getHourlyCount($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime, $database);
         
         $workstationStatus->firstEntry = $database->getFirstEntry($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
         
         $workstationStatus->updateTime = $database->getLastEntry($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
         
         $workstationStatus->averageCountTime = Stats::getAverageCountTime($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
         
         $workstationStatus->hardwareButtonStatus = WorkstationStatus::getHardwareButtonStatus($stationId, $database);
         
         $workstationStatus->cycleTimeStatus = WorkstationStatus::getCycleTimeStatus($stationId, $shiftId, $workstationStatus->updateTime);
         $workstationStatus->cycleTimeStatusLabel = CycleTimeStatus::getClassLabel($workstationStatus->cycleTimeStatus);
         
         $workstationStatus->isOnBreak = $database->isOnBreak($stationId, $shiftId);
         if ($workstationStatus->isOnBreak)
         {
            $workstationStatus->breakInfo = BreakInfo::getCurrentBreak($stationId, $shiftId);
         }
      }
      
      return ($workstationStatus);
   }
   
   private static function getWorkstationLabel($stationId, $database)
   {
      $name = "";
      
      $result = $database->getStation($stationId);
      
      if ($result && ($row = $result[0]))
      {
         $name = $row['label'];
      }
      
      return ($name);
   }
   
   private static function getCount($stationId, $shiftId, $startDateTime, $endDateTime, $database)
   {
      $screenCount = $database->getCount($stationId, $shiftId, $startDateTime, $endDateTime);
      
      return ($screenCount);
   }
   
   private static function getHourlyCount($stationId, $shiftId, $startDateTime, $endDateTime, $database)
   {
      $hourlyCount = array();
      
      $tempDateTime = $startDateTime;
      
      // Initialize array with 24 hours.
      while (new DateTime($tempDateTime) < new DateTime($endDateTime))
      {
         $index = (new DateTime($tempDateTime))->format("Y-m-d H:00:00");
         $hourlyCount[$index] = 0;

         $prevTempDateTime = $tempDateTime;
         $tempDateTime = Time::incrementHour($tempDateTime);
         
         // Workaround for daylight savings time.
         // Increment hour uses DateTime::add().  During daylight savings time in the fall, 
         // DateTime::add(new DateInterval("P1D")) on 1:00 AM -> 1:00 AM.  
         // This extra logic detects that and skips ahead two hours to compensate.
         if ($prevTempDateTime == $tempDateTime)
         {
            $tempDateTime = Time::incrementHour($tempDateTime, 2);
         }
      }

      // Retrieve hourly counts from the database.
      $result = $database->getHourlyCounts($stationId, $shiftId, $startDateTime, $endDateTime);
      
      // Fill in hourly counts from the database.
      foreach ($result as $row)
      {
         $index = Time::fromMySqlDate($row["dateTime"], "Y-m-d H:00:00");
         $hourlyCount[$index] = intval($row["count"]);
      }
      
      return ($hourlyCount);
   }
   
   private static function getUpdateTime($stationId, $database)
   {
      $updateTime = $database->getUpdateTime($stationId);

      return ($updateTime);
   }
   
   private static function getHardwareButtonStatus($stationId, $database)
   {
      $hardwareButtonStatus = new stdClass();
      $hardwareButtonStatus->chipId = ButtonInfo::UNKNOWN_BUTTON_ID;
      
      // Note: Results returned ordered by lastContact, DESC.
      $results = $database->getButtonsForStation($stationId);
      
      if ($results && ($row = $results[0]))
      {
         $buttonInfo = ButtonInfo::load($row["buttonId"]);
         
         $hardwareButtonStatus->buttonId = $buttonInfo->buttonId;
         $hardwareButtonStatus->ipAddress = $buttonInfo->ipAddress;
         $hardwareButtonStatus->lastContact = $buttonInfo->lastContact;
      }
      
      return ($hardwareButtonStatus);
   }
   
   private static function getCycleTimeStatus($stationId, $shiftId, $updateTime)
   {
      $stationInfo = StationInfo::load($stationId);

      return (CycleTimeStatus::calculateCycleTimeStatus($shiftId, $updateTime, $stationInfo->cycleTime));
   }
}

/*
if (isset($_GET["stationId"]) && isset($_GET["shiftiD"]))
{
   $stationId = intval($_GET["stationId"]);
   $shiftId = intval($_GET["stationId"]);
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);
 
   if ($workstationStatus)
   {
      echo "stationId: " .            $workstationStatus->stationId .             "<br/>";
      echo "count: " .                $workstationStatus->count .                 "<br/>";
      echo "hourlyCount: ";
      
      foreach ($workstationStatus->hourlyCount as $count)
      {
         echo "$count, ";
      }
      echo "<br/>";
      
      echo "firstEntry: " .           $workstationStatus->firstEntry .           "<br/>";
      echo "updateTime: " .           $workstationStatus->updateTime .           "<br/>";
      echo "averageCountTime: " .     $workstationStatus->averageCountTime .     "<br/>";
      echo "hardwareButtonStatus: " . $workstationStatus->hardwareButtonStatus->lastContact . "<br/>";
      echo "cycleTimeStatus: " .      CycleTimeStatus::getClassLabel($workstationStatus->cycleTimeStatus) . "<br/>";
      echo "isOnBreak" .              ($workstationStatus->isOnBreak ? "true" : "false") . "<br/>";
   }
}
*/
?>