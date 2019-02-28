<?php
require_once 'database.php';
require_once 'registryEntry.php';
require_once 'time.php';

class WorkstationStatus
{
   public $stationId;
   public $count;
   public $hourlyCount;
   public $updateTime;
   public $averageCountTime;
   public $hardwareButtonStatus;
   
   public static function getWorkstationStatus($stationId)
   {
      $dailySummary = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if (($database->isConnected()) &&
          ($database->stationExists($stationId)))
      {
         $workstationStatus = new WorkstationStatus();
         
         $now = Time::now("Y-m-d H:i:s");
         $startDateTime= Time::startOfDay($now);
         $endDateTime= Time::endOfDay($now);
         
         $workstationStatus->stationId = $stationId;
         
         $workstationStatus->count = WorkstationStatus::getCount($stationId, $startDateTime, $endDateTime, $database);
         
         $workstationStatus->hourlyCount = WorkstationStatus::getHourlyCount($stationId, $startDateTime, $endDateTime, $database);
         
         $workstationStatus->updateTime = WorkstationStatus::getUpdateTime($stationId, $database);
         
         $workstationStatus->averageCountTime = WorkstationStatus::getAverageCountTime($stationId, $startDateTime, $endDateTime, $database);
         
         $workstationStatus->hardwareButtonStatus = WorkstationStatus::getHardwareButtonStatus($stationId, $database);
      }
      
      return ($workstationStatus);
   }
   
   private static function getCount($stationId, $startDateTime, $endDateTime, $database)
   {
      $screenCount = $database->getCount($stationId, $startDateTime, $endDateTime);
      
      return ($screenCount);
   }
   
   private static function getHourlyCount($stationId, $startDateTime, $endDateTime, $database)
   {
      $startDateTime = Time::startOfDay($startDateTime);
      $endDateTime = Time::endOfDay($endDateTime);
      
      while (new DateTime($startDateTime) < new DateTime($endDateTime))
      {
         $hourlyCount[$startDateTime] = WorkstationStatus::getCount($stationId, $startDateTime, $startDateTime, $database);
         
         $startDateTime = Time::incrementHour($startDateTime);
      }
      
      return ($hourlyCount);
   }
   
   private static function getUpdateTime($stationId, $database)
   {
      $updateTime = $database->getUpdateTime($stationId);

      return ($updateTime);
   }
   
   private static function getAverageCountTime($stationId, $startDateTime, $endDateTime, $database)
   {
      $averageUpdateTime = 0;

      $startDateTime = Time::startOfDay($startDateTime);
      $endDateTime = Time::endOfDay($endDateTime);
      
      $totalCountTime = $database->getCountTime($stationId, $startDateTime, $endDateTime);
      
      $count = $database->getCount($stationId, $startDateTime, $endDateTime);
      
      if ($count > 0)
      {
         $averageUpdateTime = round($totalCountTime / $count);
      }
      
      return ($averageUpdateTime);
   }
   
   private static function getHardwareButtonStatus($stationId, $database)
   {
      $hardwareButtonStatus = new stdClass();
      $hardwareButtonStatus->chipId = RegistryEntry::UNKNOWN_CHIP_ID;
      
      // Note: Results returned ordered by lastContact, DESC.
      $results = $database->getRegistryEntriesForStation($stationId);
      
      if ($results && ($row = $results->fetch_assoc()))
      {
         $registryEntry = RegistryEntry::load($row["chipId"]);
         
         $hardwareButtonStatus->chipId = $registryEntry->chipId;
         $hardwareButtonStatus->ipAddress = $registryEntry->ipAddress;
         $hardwareButtonStatus->lastContact = $registryEntry->lastContact;
      }
      
      return ($hardwareButtonStatus);
   }
}

/*
if (isset($_GET["stationId"]))
{
   $stationId = $_GET["stationId"];
   $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, Time::now("Y-m-d H:i:s"));
 
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
      
      echo "updateTime: " .           $workstationStatus->updateTime .           "<br/>";
      echo "averageCountTime: " .     $workstationStatus->averageCountTime .     "<br/>";
      echo "hardwareButtonStatus: " . $workstationStatus->hardwareButtonStatus->lastContact . "<br/>";
   }
}
*/
?>