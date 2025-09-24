<?php

if (!defined('ROOT')) require_once '../../root.php';
require_once ROOT.'/common/database.php';

class StationManager
{
   public static function createVirtualStation($stationGroup)
   {
      $stationInfo = new StationInfo();
      
      // Initialize name and label from group name.
      $stationInfo->name = $stationGroup->name;
      $stationInfo->label = $stationGroup->name;
      $stationInfo->cycleTime = 0;
      $stationInfo->isVirtualStation = true;
      $stationInfo->cycleTime = 0;
      
      // Initialize objectName using first station in the group.
      if (count($stationGroup->stationIds) > 0)
      {
         $firstStation = StationInfo::load($stationGroup->stationIds[0]);
         if ($firstStation)
         {
            $stationInfo->objectName = $firstStation->objectName;
         }
      }
      
      StationInfo::save($stationInfo);
      
      return ($stationInfo->stationId);
   }
      
   public static function deleteVirtualStation($stationId)
   {
      StationInfo::delete($stationId);
   }
   
   public static function getVirtualStationIds($stationId)
   {
      $virtualStationIds = [];
      
      $result = FactoryStatsDatabase::getInstance()->getVirtualStationIds($stationId);
      
      foreach ($result ?? [] as $row)
      {
         $virtualStationIds[] = intval($row["virtualStationId"]);
      }
      
      return ($virtualStationIds);
   }   
}

?>