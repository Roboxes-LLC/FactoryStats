<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/stationInfo.php';

class Demo
{
   const MIN_COUNT = 5;
   const MAX_COUNT = 50;
   
   public static function isDemoSite()
   {
      return (CustomerInfo::getSubdomain() == "demo");
   }
   
   public static function showedInstructions($page)
   {
      return (isset($_SESSION["showedInstructions"]) && 
              isset($_SESSION["showedInstructions"][$page]) &&
              (intval($_SESSION["showedInstructions"][$page]) == 1));
   }
   
   public static function setShowedInstructions($page, $value)
   {
      if (!isset($_SESSION["showedInstructions"]))
      {
         $_SESSION["showedInstructions"] = array();
      }

      $_SESSION["showedInstructions"][$page] = ($value ? 1 : 0);
   }
   
   public static function generateData()
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStations();
         
         foreach ($result as $row)
         {
            $stationId = intval($row["stationId"]);
            
            $shiftId = ShiftInfo::getShift(Time::now("Y-m-d H:i:s"));
            
            $simulationData = Demo::getSimulationData($stationId, $shiftId);
            
            foreach ($simulationData as $data)
            {
               $database->setHourlyCount($stationId, $shiftId, $data->hour, $data->count, $data->countTime);
            }
            
            // Update the count to set stationInfo.updateTime.
            $database->updateCount($stationId, $shiftId, 1);
         }
      }
   }
   
   private static function getSimulationData($stationId, $shiftId)
   {
      $simulationData = array();
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $shiftInfo = ShiftInfo::load($shiftId);
         
         $now = Time::now("Y-m-d H:i:s");
         
         if ($shiftInfo)
         {
            $shiftTimes = $shiftInfo->getShiftTimes($now, $now);
            
            $dateTime = $shiftTimes->startDateTime;
            
            while (($dateTime < $shiftTimes->endDateTime) &&
                   ($dateTime < $now))
            {
               if ($database->hasCountEntry($stationId, $shiftId, $dateTime))
               {
                  $data = new stdClass();
                  $data->hour = $dateTime;
                  $data->count = rand(Demo::MIN_COUNT, Demo::MAX_COUNT);
                  $data->countTime = 3600;  // seconds in an hour
                  
                  $simulationData[] = $data;
               }
               
               $dateTime = Time::incrementHour($dateTime);
            }
         }
      }
      
      return ($simulationData);
   }
}
