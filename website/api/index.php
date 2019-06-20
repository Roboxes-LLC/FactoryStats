<?php

require_once '../common/buttonInfo.php';
require_once '../common/breakInfo.php';
require_once '../common/database.php';
require_once '../common/displayInfo.php';
require_once '../common/stationInfo.php';
require_once '../common/time.php';
require_once '../common/workstationStatus.php';
require_once 'rest.php';

function getDatabase()
{
   static $database = null;
   
   if ($database == null)
   {
      $database = new FlexscreenDatabase();
      
      $database->connect();
   }
   
   return ($database);
}

function updateCount($stationId, $screenCount)
{
   $database = getDatabase();
   
   if ($database->isConnected())
   {
      $database->updateCount($stationId, $screenCount);
   }
}

function getCount($stationId, $startDateTime, $endDateTime)
{
   $screenCount = 0;
   
   $database = getDatabase();
   
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
   
   $database = getDatabase();
   
   if ($database->isConnected())
   {
      $updateTime = $database->getUpdateTime($stationId);
   }
   
   return ($updateTime);
}

function getAverageCountTime($stationId, $startDateTime, $endDateTime)
{
   $averageUpdateTime = 0;
   
   $database = getDatabase();
   
   if ($database->isConnected())
   {
      $startDateTime = Time::startOfDay($startDateTime);
      $endDateTime = Time::endOfDay($endDateTime);
      
      $countTime = $database->getCountTime($stationId, $startDateTime, $endDateTime);
      
      $breakTime = $database->getBreakTime($stationId, $startDateTime, $endDateTime);
      
      $netCountTime = $countTime - $breakTime;
      
      $count = $database->getCount($stationId, $startDateTime, $endDateTime);
      
      //echo "countTime, breakTime, netCountTime, count: " . $countTime . "/" . $breakTime . "/" . $netCountTime . "/" . $count . "<br>";
      
      // Subtract off the first screen.
      // The time for the first screen wasn't captured.  Including it in the count gives an inaccurate average.
      $count--;
      
      if ($count > 0)
      {
         $averageUpdateTime = round($netCountTime / $count);
      }
   }
   
   return ($averageUpdateTime);
}

function getHardwareButtonStatus($stationId)
{
   $hardwareButtonStatus = new stdClass();
   $hardwareButtonStatus->buttonId = ButtonInfo::UNKNOWN_BUTTON_ID;
   
   $database = getDatabase();
   
   if ($database->isConnected())
   {
      // Note: Results returned ordered by lastContact, DESC.
      $results = $database->getButtonsForStation($stationId);
      
      if ($results && ($row = $results->fetch_assoc()))
      {
         $buttonInfo = ButtonInfo::load($row["buttonId"]);
         
         $hardwareButtonStatus->buttonId= $buttonInfo->buttonId;
         $hardwareButtonStatus->ipAddress = $buttonInfo->ipAddress;
         $hardwareButtonStatus->lastContact = $buttonInfo->lastContact;
      }
   }
   
   return ($hardwareButtonStatus);
}

function getFirstEntry($stationId)
{
   $firstEntry = null;
   
   $database = getDatabase();
   
   if ($database->isConnected())
   {
      $now = Time::now("Y-m-d H:i:s");
      $startDateTime = Time::startOfDay($now);
      $endDateTime = Time::endOfDay($now);
      
      $firstEntry = $database->getFirstEntry($stationId, $startDateTime, $endDateTime);
   }
   
   return ($firstEntry);
}

function getStations()
{
   $stations = array();

   $database = getDatabase();
   
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
$router->setLogging(false);

$router->add("registerButton", function($params) {
   if (isset($params["macAddress"]))
   {
      $database = getDatabase();
      
      if ($database->isConnected())
      {
         $queryResult = $database->getButtonByMacAddress($params->get("macAddress"));
         
         if ($queryResult && ($row = $queryResult->fetch_assoc()))
         {
            $buttonInfo = ButtonInfo::load($row["buttonId"]);
            
            if ($buttonInfo)
            {
               $buttonInfo->macAddress = $params->get("macAddress");
               $buttonInfo->ipAddress = $params->get("ipAddress");
               $buttonInfo->lastContact = Time::now("Y-m-d H:i:s");
               
               $database->updateButton($buttonInfo);
            }
         }
         else
         {
            $buttonInfo = new ButtonInfo();
            
            $buttonInfo->macAddress = $params->get("macAddress");
            $buttonInfo->ipAddress = $params->get("ipAddress");
            $buttonInfo->lastContact = Time::now("Y-m-d H:i:s");
            
            $database->newButton($buttonInfo);
         }
      }
   }
});

$router->add("registerDisplay", function($params) {
   $result = new stdClass();
   
   if (isset($params["macAddress"]))
   {
      $displayInfo = new DisplayInfo();
      $displayInfo->macAddress = $params->get("macAddress");
      $displayInfo->ipAddress = $params->get("ipAddress");
      $displayInfo->lastContact = Time::now("Y-m-d H:i:s");
      
      $database = getDatabase();
      
      if ($database->isConnected())
      {
         $queryResult = $database->getDisplayByMacAddress($displayInfo->macAddress);
         
         if ($queryResult && ($row = $queryResult->fetch_assoc()))
         {
            $displayInfo->displayId = $row["displayId"];
            $database->updateDisplay($displayInfo);
         }
         else
         {
            $database->newDisplay($displayInfo);
         }
         
         $result->success = true;
         $result->displayInfo = $displayInfo;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No MAC address supplied";
   }
   
   echo json_encode($result);
});

$router->add("update", function($params) {
   $result = new stdClass();
   
   $stationId = StationInfo::UNKNOWN_STATION_ID;
   
   if (isset($params["stationId"]))
   {
      $stationId = $params->get("stationId");
   }
   else if (isset($params["macAddress"]))
   {
      $macAddress = $params->get("macAddress");
      
      $database = getDatabase();
      
      if ($database->isConnected())
      {
         $queryResult = $database->getButtonByMacAddress($macAddress);
         
         if ($queryResult && ($row = $queryResult->fetch_assoc()))
         {
            $stationId = $row["stationId"];
         }
      }
   }
   
   if (($stationId != StationInfo::UNKNOWN_STATION_ID) && 
       isset($params["count"]))
   {
      if (BreakInfo::getCurrentBreak($stationId))
      {
         BreakInfo::endBreak($stationId);
      }
      
      updateCount($stationId, $params->get("count"));
      
      $now = Time::now("Y-m-d H:i:s");
      $startDateTime = Time::startOfDay($now);
      $endDateTime = Time::endOfDay($now);
      
      $count = getCount($stationId, $startDateTime, $endDateTime);
      
      $result->stationId = $stationId;
      $result->count = $count;
   }
   
   echo json_encode($result);
});

$router->add("break", function($params) {
   $result = new stdClass();
   
   $stationId = $params->getInt("stationId");
   
   if ($stationId != StationInfo::UNKNOWN_STATION_ID)
   {     
      $status = $params->get("status");
      
      if ($status == "start")
      {
         $breakInfo = BreakInfo::startBreak($stationId);
         
         $result->success = ($breakInfo != null);
         
         if ($result->success)
         {
            $result->breakInfo = $breakInfo;
         }
      }
      else if ($status == "end")
      {
         $breakInfo = BreakInfo::endBreak($stationId);
         
         $result->success = ($breakInfo != null);
         
         if ($result->success)
         {
            $result->breakInfo = $breakInfo;
         }
      }
   }
   
   echo json_encode($result);
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
   $breakInfo = null;
   $firstEntry = null;
   
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
      
      $breakInfo = BreakInfo::getCurrentBreak($stationId);
      
      $firstEntry = getFirstEntry($stationId);
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
   $result->isOnBreak = ($breakInfo != null);
   $result->firstEntry = $firstEntry;

   if ($result->isOnBreak == true)
   {
      $result->breakInfo = $breakInfo;
   }
   
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