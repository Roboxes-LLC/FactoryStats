<?php

require_once 'common/database.php';

function updateScreenCount($stationId, $screenCount)
{
   $database = new FlexscreenDatabase();
   
   $database->connect();

   if ($database->isConnected())
   {
      $database->updateScreenCount($stationId, $screenCount);
   }
}

function getScreenCount($stationId, $startDateTime, $endDateTime)
{
   $screenCount = 0;
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $screenCount = $database->getScreenCount($stationId, $startDateTime, $endDateTime);
   }
   
   return ($screenCount);
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
               updateScreenCount($stationId, $screenCount);
               
               echo "New screen count for this hour: " . getScreenCount($stationId, startOfHour(Time::now("Y-m-d H:i:s")), endOfHour(Time::now("Y-m-d H:i:s")));
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
         
         $count = getScreenCount($stationId, $startDateTime, $endDateTime);
         
         $result["stationId"] = $stationId;
         $result["count"] = $count;
         
         echo json_encode($result);
         
         break;
      }
      
      case "hourlyCount":
      {
         $totalCount = 0;
         $hourlyCount = array();
         
         $stationId = isset($_GET["stationId"]) ? $_GET["stationId"] : "ALL";
         
         $startDateTime = isset($_GET["startDateTime"]) ? $_GET["startDateTime"] : Time::now("Y-m-d H:i:s");
         $startDateTime = startOfDay($startDateTime);
         
         $endDateTime = isset($_GET["endDateTime"]) ? $_GET["endDateTime"] : Time::now("Y-m-d H:i:s");
         $endDateTime = endOfDay($endDateTime);

         while (new DateTime($startDateTime) < new DateTime($endDateTime))
         {
            $count = getScreenCount($stationId, $startDateTime, $startDateTime);
            
            $totalCount += $count; 
            $hourlyCount[$startDateTime] = getScreenCount($stationId, $startDateTime, $startDateTime);
            
            $startDateTime = incrementHour($startDateTime);
         }
         
         $result["stationId"] = $stationId;
         $result["totalCount"] = $totalCount;
         $result["hourlyCount"] = $hourlyCount;
         
         echo json_encode($result);
         
         break;
      }
         
      case "ping":
      {
         updatePartCount($sensorId, 0);
         break;
      }
         
      default:
      {
         break;
      }
   }
}

?>