<?php
require_once 'database.php';
require_once 'shiftInfo.php';
require_once 'stationInfo.php';

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
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStations();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $stationId = intval($row["stationId"]);
            
            $shiftId = ShiftInfo::getShift(Time::now("Y-m-d H:i:s"));
            
            $simulationData = Demo::getSimulationData($stationId, $shiftId);
            
            foreach ($simulationData as $data)
            {
               Demo::setHourlyCount($stationId, $shiftId, $data->hour, $data->count, $data->countTime);
            }
            
            // Update the count to set stationInfo.updateTime.
            $database->updateCount($stationId, $shiftId, 1);
         }
      }
   }
   
   private static function getSimulationData($stationId, $shiftId)
   {
      $simulationData = array();
      
      $shiftInfo = ShiftInfo::load($shiftId);
      
      $now = Time::now("Y-m-d H:i:s");
      
      if ($shiftInfo)
      {
         $shiftTimes = $shiftInfo->getShiftTimes($now, $now);
         
         $dateTime = $shiftTimes->startDateTime;
         
         while (($dateTime < $shiftTimes->endDateTime) &&
                ($dateTime < $now))
         {
            if (!Demo::hasEntry($stationId, $shiftId, $dateTime))
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
      
      return ($simulationData);
   }
   
   private static function setHourlyCount($stationId, $shiftId, $dateTime, $count, $countTime)
   {
      $database = FlexscreenDatabase::getInstance();
      
      $dateTime = Time::toMySqlDate($dateTime);
      
      $firstEntry = Time::toMySqlDate(Time::startOfHour($dateTime));
      $lastEntry = Time::toMySqlDate(Time::endOfHour($dateTime));
      
      if ($database && $database->isConnected())
      {
         $query =
         "INSERT INTO screencount " .
         "(stationId, shiftId, dateTime, count, countTime, firstEntry, lastEntry) " .
         "VALUES " .
         "('$stationId', '$shiftId', '$dateTime', '$count', '$countTime', '$firstEntry', '$lastEntry');";

         $database->query($query);
      }
   }
   
   private static function hasEntry($stationId, $shiftId, $dateTime)
   {
      $database = FlexscreenDatabase::getInstance();
      
      $dateTime = Time::toMySqlDate($dateTime);
      
      $result = null;
      
      if ($database && $database->isConnected())
      {
         $query = "SELECT * FROM screencount WHERE stationId = $stationId AND shiftId = $shiftId AND dateTime = '$dateTime';";
  
         $result = $database->query($query);
      }
      
      return ($result && ($database->countResults($result) > 0));
   }
}

Demo::generateData();
