<?php
require_once 'breakDescription.php';
require_once 'database.php';
require_once 'shiftInfo.php';
require_once 'stationInfo.php';
require_once 'time.php';

class BreakInfo
{
   const UNKNOWN_BREAK_ID = 0;
   
   public $breakId = BreakInfo::UNKNOWN_BREAK_ID;
   public $stationId = StationInfo::UNKNOWN_STATION_ID;
   public $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
   public $breakDescriptionId = BreakDescription::UNKNOWN_DESCRIPTION_ID;
   public $startTime;
   public $endTime;
   
   public static function load($breakId)
   {
      $breakInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getBreak($breakId);
         
         if ($result && ($row = $result[0]))
         {
            $breakInfo = new BreakInfo();
            
            $breakInfo->breakId = intval($row['breakId']);
            $breakInfo->stationId = intval($row['stationId']);
            $breakInfo->shiftId = intval($row['shiftId']);
            $breakInfo->breakDescriptionId = intval($row['breakDescriptionId']);
            $breakInfo->startTime = Time::fromMySqlDate($row['startTime'], "Y-m-d H:i:s");
            if ($row['endTime'] != null)
            {
               $breakInfo->endTime = Time::fromMySqlDate($row['endTime'], "Y-m-d H:i:s");
            }
         }
      }
      
      return ($breakInfo);
   }
   
   public static function getCurrentBreak($stationId, $shiftId)
   {
      $breakInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $breakId = $database->getCurrentBreakId($stationId, $shiftId);

         if ($breakId != BreakInfo::UNKNOWN_BREAK_ID)
         {
            $breakInfo = BreakInfo::load($breakId);
         }
      }
      
      return ($breakInfo);
   }
   
   public static function isOnBreak($stationId, $shiftId)
   {
      $isOnBreak = false;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $isOnBreak = $database->isOnBreak($stationId, $shiftId);
      }
      
      return ($isOnBreak);
   }
   
   public static function startBreak($stationId, $shiftId, $breakDescriptionId)
   {
      $breakInfo = null;
      
      if (BreakInfo::isOnBreak($stationId, $shiftId) == false)
      {
         $database = FactoryStatsDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $database->startBreak($stationId, $shiftId, $breakDescriptionId, Time::now("Y-m-d H:i:s"));
            
            $breakInfo = BreakInfo::getCurrentBreak($stationId, $shiftId);
         }
      }
      
      return ($breakInfo);
   }
   
   public static function endBreak($stationId, $shiftId)
   {
      $breakInfo = BreakInfo::getCurrentBreak($stationId, $shiftId);
      
      if ($breakInfo)
      {
         $database = FactoryStatsDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $database->endBreak($stationId, $shiftId, Time::now("Y-m-d H:i:s"));
            
            $breakInfo = BreakInfo::load($breakInfo->breakId);
         }
      }
      
      return ($breakInfo);
   }
   
   public function getDuration()
   {
      return (Time::differenceSeconds($this->startTime, $this->endTime));
   }
}

/*
Time::init();

if (isset($_GET["breakId"]))
{
   $breakId = $_GET["breakId"];
   $breakInfo = BreakInfo::load($breakId);
 
   if ($breakInfo)
   {
      echo "breakId: " .            $breakInfo->breakId .            "<br/>";
      echo "stationId: " .          $breakInfo->stationId .          "<br/>";
      echo "shiftId: " .            $breakInfo->shiftId .            "<br/>";
      echo "breakId: " .            $breakInfo->breakId .            "<br/>";
      echo "breakDescriptionId: " . $breakInfo->breakDescriptionId . "<br/>";
      echo "startTime: " .          $breakInfo->startTime .          "<br/>";
      echo "endTime: " .            $breakInfo->endTime .            "<br/>";
   }
   else
   {
      echo "No break info found.";
   }
}
*/

?>