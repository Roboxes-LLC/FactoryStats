<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';
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
   }
}