<?php

require_once '../common/database.php';
require_once '../common/registryEntry.php';
require_once '../common/time.php';
require_once '../common/workstationStatus.php';
require_once 'rest.php';

function updateCount($stationId, $screenCount)
{
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $database->updateCount($stationId, $screenCount);
   }
}

function getCount($stationId, $startDateTime, $endDateTime)
{
   $screenCount = 0;
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $screenCount = $database->getCount($stationId, $startDateTime, $endDateTime);
   }
   
   return ($screenCount);
}

function getHourlyCount($stationId, $startDateTime, $endDateTime)
{
   $startDateTime = Time::startOfDay($startDateTime);
   $endDateTime = Time::endOfDay($endDateTime);
   
   while (new DateTime($startDateTime) < new DateTime($endDateTime))
   {
      $hourlyCount[$startDateTime] = getCount($stationId, $startDateTime, $startDateTime);
      
      $startDateTime = Time::incrementHour($startDateTime);
   }
   
   return ($hourlyCount);
}

function getUpdateTime($stationId)
{
   $updateTime = "";
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $updateTime = $database->getUpdateTime($stationId);
   }
   
   return ($updateTime);
}

function getAverageCountTime($stationId, $startDateTime, $endDateTime)
{
   $averageUpdateTime = 0;
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $startDateTime = Time::startOfDay($startDateTime);
      $endDateTime = Time::endOfDay($endDateTime);
      
      $totalCountTime = $database->getCountTime($stationId, $startDateTime, $endDateTime);
      
      $count = $database->getCount($stationId, $startDateTime, $endDateTime);
      
      if ($count > 0)
      {
         $averageUpdateTime = round($totalCountTime / $count);
      }
   }
   
   return ($averageUpdateTime);
}

function getHardwareButtonStatus($stationId)
{
   $hardwareButtonStatus = new stdClass();
   $hardwareButtonStatus->chipId = RegistryEntry::UNKNOWN_CHIP_ID;
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      // Note: Results returned ordered by lastContact, DESC.
      $results = $database->getRegistryEntriesForStation($stationId);
      
      if ($results && ($row = $results->fetch_assoc()))
      {
         $registryEntry = RegistryEntry::load($row["chipId"]);
         
         $hardwareButtonStatus->chipId = $registryEntry->chipId;
         $hardwareButtonStatus->ipAddress = $registryEntry->ipAddress;
         $hardwareButtonStatus->lastContact = $registryEntry->lastContact;
      }
   }
   
   return ($hardwareButtonStatus);
}

function getStations()
{
   $stations = array();

   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $stations[] = $row["stationId"];
      }
   }
   
   return ($stations);
}

// *****************************************************************************
//                                   Begin

$router = new Router();

$router->add("register", function($params) {
   if (isset($params["chipId"]))
   {
      $registryEntry = new RegistryEntry();
      $registryEntry->chipId = $params->get("chipId");
      $registryEntry->macAddress = $params->get("macAddress");
      $registryEntry->ipAddress = $params->get("ipAddress");
      $registryEntry->roboxName = $params->get("roboxName");
      $registryEntry->userId = $params->get("userId");
      $registryEntry->lastContact = Time::now("Y-m-d H:i:s");
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         if ($database->existsInRegistry($registryEntry->chipId))
         {
            $database->updateRegistry($registryEntry);
         }
         else
         {
            $database->register($registryEntry);
         }
      }
   }
});

$router->add("unregister", function($params) {
   
   if (isset($params["chipId"]))
   {
      $chipId = $params->get("chipId");
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         if ($database->existsInRegistry($chipId))
         {
            $database->unregister($chipId);
         }
      }
   }
});

$router->add("update", function($params) {
   if (isset($params["stationId"]) && isset($params["count"]))
   {
      updateCount($params->get("stationId"), $params->get("count"));
      
      echo "New screen count for this hour: " . getCount($params->get("stationId"), Time::startOfHour(Time::now("Y-m-d H:i:s")), Time::endOfHour(Time::now("Y-m-d H:i:s")));
   }
});

$router->add("count", function($params) {
   $stationId = isset($params["stationId"]) ? $params->get("stationId") : "ALL";
   
   $startDateTime =  isset($params["startDateTime"]) ? $params->get("startDateTime") : Time::now("Y-m-d H:i:s");
   $startDateTime = Time::startOfHour($startDateTime);
   
   $endDateTime = isset($params["endDateTime"])? $params->get("endDateTime") : Time::now("Y-m-d H:i:s");
   $endDateTime = Time::endOfHour($endDateTime);
   
   $count = getCount($stationId, $startDateTime, $endDateTime);
   
   $result["stationId"] = $stationId;
   $result["count"] = $count;
   
   echo json_encode($result);
});

$router->add("status", function($params) {
   $result = new stdClass();
   
   $stationId = 0;
   $totalCount = 0;
   $lastUpdateTime = "UNKNOWN";
   $averageUpdateTime = 0;
   $hourlyCount = array();
   $hardwareButtonStatus = new stdClass();
   
   if (isset($params["stationId"]))
   {
      $stationId = $params->get("stationId");
      
      $now = Time::now("Y-m-d H:i:s");
      $startDateTime = Time::startOfDay($now);
      $endDateTime = Time::endOfDay($now);
      
      $count = getCount($stationId, $startDateTime, $endDateTime);
      
      $hourlyCount = getHourlyCount($stationId, $startDateTime, $endDateTime);
      
      $updateTime = getUpdateTime($stationId);
      
      $averageCountTime = getAverageCountTime($stationId, $startDateTime, $endDateTime);
      
      $hardwareButtonStatus = getHardwareButtonStatus($stationId);
   }
   else
   {
      $result->error = "Invalid stationId";
   }
   
   $result->stationId = $stationId;
   $result->count = $count;
   $result->hourlyCount = $hourlyCount;
   $result->updateTime = $updateTime;
   $result->averageCountTime = $averageCountTime;
   $result->hardwareButtonStatus = $hardwareButtonStatus;
   
   echo json_encode($result);
});

$router->add("stations", function($params) {
   $result = new stdClass();
   
   $result->stations = getStations();
   
   echo json_encode($result);
});

$router->add("workstationSummary", function($params) {
   $result = new stdClass();
   $result->workstationSummary = array();
   
   $stations = getStations();
   
   foreach ($stations as $stationId)
   {
      $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId);
      
      if ($workstationStatus)
      {
         $result->workstationSummary[] = $workstationStatus;
      }
   }
   
   echo json_encode($result);
});

$router->route();
?>