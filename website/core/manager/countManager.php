<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/core/manager/stationManager.php';
require_once ROOT.'/plugin/pluginManager.php';

class CountManager
{
   public static function getCount($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $count = FactoryStatsDatabase::getInstance()->getCount($stationId, $shiftId, $startDateTime, $endDateTime);
      
      return ($count);
   }
   
   public static function updateCount($stationId, $shiftId, $deltaCount)
   {
      FactoryStatsDatabase::getInstance()->updateCount($stationId, $shiftId, $deltaCount);
      
      PluginManager::handleEvent(
         PluginEvent::STATION_COUNT_CHANGED, 
         new StationCountChangedPayload($stationId, $shiftId, $deltaCount));
      
      CountManager::updateVirtualCounts($stationId, $shiftId, $deltaCount);
   }
   
   public static function getCycleTimeChartData($stationId, $shiftId, $startDateTime, $endDateTime, $maxCycleTime)
   {
      $data = [];
      
      $exactCounts = FactoryStatsDatabase::getInstance()->getExactCounts($stationId, $shiftId, $startDateTime, $endDateTime);
      
      $prevDateTime = null;
      
      foreach ($exactCounts as $row)
      {
         $dateTime = Time::fromMySqlDate($row["dateTime"], "Y-m-d H:i:s.u");  // Why is $row["dateTime"] rounded?
         
         if ($prevDateTime != null)
         {
            $cycleTime = round(CountManager::differenceSeconds($prevDateTime, $dateTime), 1);  //  Custom, for more precision.
            
            if ($cycleTime < $maxCycleTime)
            {
               $data[$dateTime] = $cycleTime;
            }
            else
            {
               $data[$dateTime] = null;
            }
         }
         
         $prevDateTime = $dateTime;
      }
      
      return ($data);
   }
   
   public static function differenceSeconds($startTime, $endTime)
   {
      $startDateTime = Time::getDateTime($startTime);
      $endDateTime = Time::getDateTime($endTime);
      
      $diff = $startDateTime->diff($endDateTime);
      
      // Convert to *fractional* seconds.
      $seconds = (($diff->d * 12 * 60 * 60) + ($diff->h * 60 * 60) + ($diff->i * 60) + $diff->s + $diff->f);
      
      return ($seconds);
   }
   
   private static function updateVirtualCounts($stationId, $shiftId, $deltaCount)
   {
      $virtualStationIds = StationManager::getVirtualStationIds($stationId);
      
      foreach ($virtualStationIds as $virtualStationId)
      {
         CountManager::updateCount($virtualStationId, $shiftId, $deltaCount);
      }
   }
}

?>
