<?php

require_once '../common/buttonInfo.php';
require_once '../common/breakInfo.php';
require_once '../common/database.php';
require_once '../common/displayInfo.php';
require_once '../common/stationInfo.php';
require_once '../common/shiftInfo.php';
require_once '../common/time.php';
require_once '../common/workstationStatus.php';
require_once 'rest.php';

function updateCount($stationId, $shiftId, $screenCount)
{
   FlexscreenDatabase::getInstance()->updateCount($stationId, $shiftId, $screenCount);
}

function getCount($stationId, $shiftId, $startDateTime, $endDateTime)
{
   $count = FlexscreenDatabase::getInstance()->getCount($stationId, $shiftId, $startDateTime, $endDateTime);

   return ($count);
}

function getStations()
{
   $stations = array();

   $result = FlexscreenDatabase::getInstance()->getStations();

   while ($result && $row = $result->fetch_assoc())
   {
      $stations[] = $row["stationId"];
   }

   return ($stations);
}

// *****************************************************************************
//                                   Begin

Time::init();

$router = new Router();
$router->setLogging(false);

$router->add("registerButton", function($params) {
   $result = new stdClass();
   
   if (isset($params["macAddress"]))
   {
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
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
   
               FlexscreenDatabase::getInstance()->updateButton($buttonInfo);
            }
         }
         else
         {
            $buttonInfo = new ButtonInfo();
   
            $buttonInfo->macAddress = $params->get("macAddress");
            $buttonInfo->ipAddress = $params->get("ipAddress");
            $buttonInfo->lastContact = Time::now("Y-m-d H:i:s");
   
            FlexscreenDatabase::getInstance()->newButton($buttonInfo);
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
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

      $database = FlexscreenDatabase::getInstance();

      if ($database && $database->isConnected())
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
   $shiftId = ShiftInfo::DEFAULT_SHIFT_ID;

   if (isset($params["stationId"]))
   {
      $stationId = $params->get("stationId");
   }
   else if (isset($params["macAddress"]))
   {
      $macAddress = $params->get("macAddress");

      $database = FlexscreenDatabase::getInstance();

      if ($database && $database->isConnected())
      {
         $queryResult = $database->getButtonByMacAddress($macAddress);

         if ($queryResult && ($row = $queryResult->fetch_assoc()))
         {
            $stationId = $row["stationId"];
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }

   if (isset($params["shiftId"]))
   {
      $shiftId = $params->get("shiftId");
   }

   if (($stationId != StationInfo::UNKNOWN_STATION_ID) &&
       ($stationId != ShiftInfo::UNKNOWN_SHIFT_ID) &&
       isset($params["count"]))
   {
      if (BreakInfo::getCurrentBreak($stationId, $shiftId))
      {
         BreakInfo::endBreak($stationId, $shiftId);
      }

      updateCount($stationId, $shiftId, $params->get("count"));

      $now = Time::now("Y-m-d H:i:s");
      $startDateTime = Time::startOfDay($now);
      $endDateTime = Time::endOfDay($now);

      $count = getCount($stationId, $shiftId, $startDateTime, $endDateTime);

      $result->stationId = $stationId;
      $result->shiftId = $shiftId;
      $result->count = $count;
   }

   echo json_encode($result);
});

$router->add("break", function($params) {
   $result = new stdClass();

   $stationId = $params->getInt("stationId");
   $shiftId = $params->getInt("shiftId");

   if (($stationId != StationInfo::UNKNOWN_STATION_ID) &&
       ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID))
   {
      $status = $params->get("status");

      if ($status == "start")
      {
         $breakDescriptionId = ($params->keyExists("breakDescriptionId")) ? $params->get("breakDescriptionId") : BreakDescription::UNKNOWN_DESCRIPTION_ID;

         $breakInfo = BreakInfo::startBreak($stationId, $shiftId, $breakDescriptionId);
         
         $result->success = ($breakInfo != null);

         if ($result->success)
         {
            $result->breakInfo = $breakInfo;
         }
      }
      else if ($status == "end")
      {
         $breakInfo = BreakInfo::endBreak($stationId, $shiftId);

         $result->success = ($breakInfo != null);

         if ($result->success)
         {
            $result->breakInfo = $breakInfo;
         }
      }
      else
      {
         $result->success = false;
         $result->error = "Invalid parameters.";
      }
   }

   echo json_encode($result);
});

$router->add("count", function($params) {
   $stationId = isset($params["stationId"]) ? $params->get("stationId") : "ALL";
   $shiftId = isset($params["shiftId"]) ? $params->get("shiftId") : ShiftInfo::UNKNOWN_SHIFT_ID;

   $startDateTime =  isset($params["startDateTime"]) ? $params->get("startDateTime") : Time::now("Y-m-d H:i:s");
   $startDateTime = Time::startOfHour($startDateTime);

   $endDateTime = isset($params["endDateTime"])? $params->get("endDateTime") : Time::now("Y-m-d H:i:s");
   $endDateTime = Time::endOfHour($endDateTime);

   $count = getCount($stationId, $shiftId, $startDateTime, $endDateTime);

   $result["stationId"] = $stationId;
   $result["shiftId"] = $shiftId;
   $result["count"] = $count;

   echo json_encode($result);
});

$router->add("status", function($params) {
   $result = new stdClass();

   if (!isset($params["stationId"]))
   {
      $result->success = false;
      $result->error = "Invalid stationId";
   }
   else
   {
      $stationId = $params->get("stationId");

      $shiftId = isset($params["shiftId"]) ? $params->get("shiftId") : ShiftInfo::UNKNOWN_SHIFT_ID;

      $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);

      if ($workstationStatus)
      {
         $result = $workstationStatus;
      }
      else
      {
         $result->success = false;
         $result->error = "Failed to retrieve status";
      }
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

   $shiftId = isset($params["shiftId"]) ? $params->get("shiftId") : ShiftInfo::UNKNOWN_SHIFT_ID;

   foreach ($stations as $stationId)
   {
      $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);

      if ($workstationStatus)
      {
         $result->workstationSummary[] = $workstationStatus;
      }
   }

   echo json_encode($result);
});

$router->add("stationInfoSummary", function($params) {
   $result = new stdClass();
   $result->stationInfoSummary = array();

   $stations = getStations();

   foreach ($stations as $stationId)
   {
      $stationInfo = StationInfo::load($stationId);

      if ($stationInfo)
      {
         $dateTime = new DateTime($stationInfo->updateTime, new DateTimeZone('America/New_York'));
         $stationInfo->updateTime = $dateTime->format("m-d-Y h:i a");

         $result->stationInfoSummary[] = $stationInfo;
      }
   }

   echo json_encode($result);
});

$router->add("session", function($params) {
   $result = new stdClass();
   
   $action = $params->get("action");
   $key = $params->get("key");
   $value = $params->get("value");

   if ($action && $key && $value)
   {
      session_start();
      
      if ($action == "set")
      {
         $_SESSION[$key] = $value;
         
         $result->success = true;
      }
      else if ($action == "get")
      {
         // TODO
         $result->success = false;
         $result->error = "Unsupported action.";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Invalid parameters.";
   }
   
   echo json_encode($result);
});

$router->add("shift", function($params) {
   $result = new stdClass();
   
   $shiftId = ShiftInfo::getShift(Time::now("H:i:s"));
   
   $result->shiftId = $shiftId;
   $result->success = true;
   
   echo json_encode($result);
});

$router->add("displayStatus", function($params) {
   $result = new stdClass();
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result->success = true;
      $result->displayStatuses = array();
      
      $dbaseResult = $database->getDisplays();
      
      while ($dbaseResult && ($row = $dbaseResult->fetch_assoc()))
      {
         $displayInfo = DisplayInfo::load(intval($row["displayId"]));
         
         $displayStatus = new stdClass();
         $displayStatus->displayId = $displayInfo->displayId;
         $displayStatus->isOnline = $displayInfo->isOnline();
         $displayStatus->label = $displayStatus->isOnline ? "Online" : "Offline";
         $displayStatus->ledClass = $displayStatus->isOnline ? "led-green" : "led-red";

         $result->displayStatuses[] = $displayStatus;
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No database connection";
   }
   
   echo json_encode($result);
});

$router->route();
?>