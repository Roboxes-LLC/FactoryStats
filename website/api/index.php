<?php

require_once '../common/database.php';
require_once '../common/registryEntry.php';
require_once '../common/time.php';
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
   $startDateTime = startOfDay($startDateTime);
   $endDateTime = endOfDay($endDateTime);
   
   while (new DateTime($startDateTime) < new DateTime($endDateTime))
   {
      $hourlyCount[$startDateTime] = getCount($stationId, $startDateTime, $startDateTime);
      
      $startDateTime = incrementHour($startDateTime);
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
      $startDateTime = startOfDay($startDateTime);
      $endDateTime = endOfDay($endDateTime);
      
      $totalCountTime = $database->getCountTime($stationId, $startDateTime, $endDateTime);
      
      $count = $database->getCount($stationId, $startDateTime, $endDateTime);
      
      if ($count > 0)
      {
         $averageUpdateTime = round($totalCountTime / $count);
      }
   }
   
   return ($averageUpdateTime);
}

function startOfHour($dateTime)
{
   $startDateTime = new DateTime($dateTime);
   return ($startDateTime->format("Y-m-d H:00:00"));
}

function endOfHour($dateTime)
{
   $endDateTime = new DateTime($dateTime);
   $endDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
   return ($endDateTime->format("Y-m-d H:00:00"));
}

function startOfDay($dateTime)
{
   $startDateTime = new DateTime($dateTime);
   return ($startDateTime->format("Y-m-d 00:00:00"));
}

function endOfDay($dateTime)
{
   $startDateTime = new DateTime($dateTime);
   return ($startDateTime->format("Y-m-d 23:00:00"));
}

function incrementHour($dateTime)
{
   $incrementedDateTime = new DateTime($dateTime);
   
   $incrementedDateTime->add(new DateInterval("PT1H"));  // period, time, 1 hour
   
   return ($incrementedDateTime->format("Y-m-d H:i:s"));
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
      
      echo "New screen count for this hour: " . getCount($stationId, startOfHour(Time::now("Y-m-d H:i:s")), endOfHour(Time::now("Y-m-d H:i:s")));
   }
});

$router->add("count", function($params) {
   $stationId = isset($params["stationId"]) ? $params->get("stationId") : "ALL";
   
   $startDateTime =  isset($params["startDateTime"]) ? $params->get("startDateTime") : Time::now("Y-m-d H:i:s");
   $startDateTime = startOfHour($startDateTime);
   
   $endDateTime = isset($params["endDateTime"])? $params->get("endDateTime") : Time::now("Y-m-d H:i:s");
   $endDateTime = endOfHour($endDateTime);
   
   $count = getCount($stationId, $startDateTime, $endDateTime);
   
   $result["stationId"] = $stationId;
   $result["count"] = $count;
   
   echo json_encode($result);
});

$router->add("buttonStatus", function($params) {
   $result["stationId"] = $stationId;
   
   if (isset($params["stationId"]))
   {
      $stationId = $params->get("stationId");
      
      $isConnected = isButtonConnected($stationId);
      
      $result["isConnected"] = isButtonConnected($stationId);
   }
   else
   {
      $result["error"] = "Invalid stationd ID";   
   }
   
   echo json_encode($result);
});

$router->route();
?>