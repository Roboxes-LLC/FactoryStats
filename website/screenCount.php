<?php

require_once 'common/database.php';

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

Time::init();

if (isset($_GET["action"]))
{
   $action = $_GET["action"];
   
   switch ($action)
   {
      case "update":
      {
         if (isset($_GET["stationId"]) && isset($_GET["count"]))
         {
            $stationId = $_GET["stationId"];
            $screenCount = $_GET["count"];
            
            if ($stationId != "ALL")
            {
               updateCount($stationId, $screenCount);
               
               echo "New screen count for this hour: " . getCount($stationId, startOfHour(Time::now("Y-m-d H:i:s")), endOfHour(Time::now("Y-m-d H:i:s")));
            }
         }
         break;
      }
      
      case "count":
      {
         $stationId = isset($_GET["stationId"]) ? $_GET["stationId"] : "ALL";
         
         $startDateTime = isset($_GET["startDateTime"]) ? $_GET["startDateTime"] : Time::now("Y-m-d H:i:s");
         $startDateTime = startOfHour($startDateTime);
         
         $endDateTime = isset($_GET["endDateTime"]) ? $_GET["endDateTime"] : Time::now("Y-m-d H:i:s");
         $endDateTime = endOfHour($endDateTime);
         
         $count = getCount($stationId, $startDateTime, $endDateTime);
         
         $result["stationId"] = $stationId;
         $result["count"] = $count;
         
         echo json_encode($result);
         
         break;
      }
      
      case "status":
      {
         $totalCount = 0;
         $lastUpdateTime = "UNKNOWN";
         $averageUpdateTime = 0;
         $hourlyCount = array();
         
         if (isset($_GET["stationId"]))
         {
            $stationId = $_GET["stationId"];
            
            $now = Time::now("Y-m-d H:i:s");
            $startDateTime = startOfDay($now);
            $endDateTime = endOfDay($now);
            
            $count = getCount($stationId, $startDateTime, $endDateTime);
            
            $hourlyCount = getHourlyCount($stationId, $startDateTime, $endDateTime);
            
            $updateTime = getUpdateTime($stationId);
            
            $averageCountTime = getAverageCountTime($stationId, $startDateTime, $endDateTime);
         }
         
         $result["stationId"] = $stationId;
         $result["count"] = $count;
         $result["hourlyCount"] = $hourlyCount;
         $result["updateTime"] = $updateTime;
         $result["averageCountTime"] = $averageCountTime;
         
         echo json_encode($result);
         
         break;         
      }
      
      default:
      {
         break;
      }
   }
}

?>