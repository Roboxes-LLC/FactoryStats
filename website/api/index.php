<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/api/rest.php';
require_once ROOT.'/common/authentication.php';
require_once ROOT.'/common/buttonInfo.php';
require_once ROOT.'/common/breakInfo.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/displayInfo.php';
require_once ROOT.'/common/displayRegistry.php';
require_once ROOT.'/common/presentationInfo.php';
require_once ROOT.'/common/sensorInfo.php';
require_once ROOT.'/common/stationInfo.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/common/workstationStatus.php';
require_once ROOT.'/core/manager/countManager.php';

function getStations()
{
   $stations = array();

   $result = FactoryStatsDatabase::getInstance()->getStations();

   foreach ($result as $row)
   {
      $stations[] = intval($row["stationId"]);
   }

   return ($stations);
}

function getShifts()
{
   $shifts = array();
   
   $result = FactoryStatsDatabase::getInstance()->getShifts();
   
   foreach ($result as $row)
   {
      $shifts[] = intval($row["shiftId"]);
   }
   
   return ($shifts);
}

function getBreakDescriptions()
{
   $breakDescriptions = array();   
   
   $breakDescription = new BreakDescription();
   
   $result = FactoryStatsDatabase::getInstance()->getBreakDescriptions();
   
   foreach ($result as $row)
   {
      $breakDescription = new BreakDescription();
      
      $breakDescription->initialize($row);
      
      $breakDescriptions[] = $breakDescription;
   }
   
   return ($breakDescriptions);
}

// *****************************************************************************
//                                   Begin

session_start();

Authentication::authenticate();

if (!Authentication::isAuthenticated())
{
   // HACK!!! Remove once API authentication is required.
   $customerId = CustomerInfo::getCustomerId();
   if ($customerId != CustomerInfo::UNKNOWN_CUSTOMER_ID)
   {
      $_SESSION["customerId"] = $customerId;
      $_SESSION["database"] = CustomerInfo::getDatabase();
   }
   /*
   else 
   {
      $result = new stdClass();
      $result->success = false;
      $result->error = "Authentication error";
      
      echo json_encode($result);
      exit;
   }
   */
}

Time::init(CustomerInfo::getTimeZone());

$router = new Router();
$router->setLogging(false);

$router->add("button", function($params) {
   $result = new stdClass();
   
   if (isset($params["uid"]))
   {
      $uid = $params["uid"];
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $queryResult = $database->getButtonByUid($uid);
         
         $buttonInfo = null;
         
         if ($queryResult && ($row = $queryResult[0]))
         {
            // Load an existing button.
            $buttonInfo = ButtonInfo::load($row["buttonId"]);
         }
         else
         {
            // Register a new button.
            $buttonInfo = new ButtonInfo();
            
            $buttonInfo->uid = $uid;
            
            $database->newButton($buttonInfo);
         }
         
         if ($buttonInfo)
         {            
            // Set IP address, if provided.
            if (isset($params["ipAddress"]))
            {
               $buttonInfo->ipAddress = $params["ipAddress"];
            }
            
            // Update last contact.
            $buttonInfo->lastContact = Time::now("Y-m-d H:i:s");
            
            // Update button config in the database.
            $database->updateButton($buttonInfo);
            
            // Handle button presses.
            if (isset($params["press"]))
            {
               $buttonInfo->handleButtonPress(intval($params["press"]));
            }
            
            if ($buttonInfo->stationId != StationInfo::UNKNOWN_STATION_ID)
            {
               $workStationStatus = WorkstationStatus::getWorkstationStatus($buttonInfo->stationId, ShiftInfo::getShift(Time::now("Y-m-d H:i:s")));
               
               if ($workStationStatus)
               {
                  $result->count = $workStationStatus->count;
               }
            }
            
            $result->buttonInfo = $buttonInfo;
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to handle button";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});

$router->add("buttonStatus", function($params) {
   $result = new stdClass();
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result->success = true;
      $result->buttonStatuses = array();
      
      $dbaseResult = $database->getButtons();
      
      foreach ($dbaseResult as $row)
      {
         $buttonInfo = ButtonInfo::load(intval($row["buttonId"]));
         
         if ($buttonInfo)
         {
            $status = $buttonInfo->getButtonStatus();
            $dateTime = Time::getDateTime($buttonInfo->lastContact);
            $formattedDateTime = $dateTime->format("m/d/Y h:i A");
            
            $buttonStatus = new stdClass();

            $buttonStatus->buttonId = $buttonInfo->buttonId;
            $buttonStatus->lastContact = $formattedDateTime;
            $buttonStatus->buttonStatus = $status;
            $buttonStatus->buttonStatusLabel = ButtonStatus::getLabel($status);
            $buttonStatus->buttonStatusClass = ButtonStatus::getClass($status);        
            $buttonStatus->recentlyPressed = $buttonInfo->recentlyPressed();         

            $result->buttonStatuses[] = $buttonStatus;
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No database connection";
   }
   
   echo json_encode($result);
});

$router->add("sensor", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   if (isset($params["uid"]))
   {
      $uid = $params["uid"];
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $queryResult = $database->getSensorByUid($uid);
         
         $sensorInfo = null;
         
         if ($queryResult && ($row = $queryResult[0]))
         {
            // Load an existing sensor.
            $sensorInfo = SensorInfo::load($row["sensorId"]);
         }
         else
         {
            // Register a new sensor.
            $sensorInfo = new SensorInfo();
            
            $sensorInfo->uid = $uid;
            
            $database->newSensor($sensorInfo);
         }
         
         if ($sensorInfo)
         {
            // Set IP address, if provided.
            if (isset($params["ipAddress"]))
            {
               $sensorInfo->ipAddress = $params["ipAddress"];
            }

            // Set software version, if provided.
            if (isset($params["version"]))
            {
               $sensorInfo->version = $params["version"];
            }
            
            // Update last contact.
            $sensorInfo->lastContact = Time::now("Y-m-d H:i:s");
            
            // Update sensor config in the database.
            $database->updateSensor($sensorInfo);
            
            // Handle sensor update.
            $sensorInfo->handleSensorUpdate($params, $result);
            
            $result->success = true;
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to handle the sensor update";
         }
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});

$router->add("sensorStatus", function($params) {
   $result = new stdClass();
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result->success = true;
      $result->sensorStatuses = array();
      
      $dbaseResult = $database->getSensors();
      
      foreach ($dbaseResult as $row)
      {
         $sensorInfo = SensorInfo::load(intval($row["sensorId"]));
         
         if ($sensorInfo)
         {
            $status = $sensorInfo->getSensorStatus();
            $dateTime = Time::getDateTime($sensorInfo->lastContact);
            $formattedDateTime = $dateTime->format("m/d/Y h:i A");
            
            $sensorStatus = new stdClass();
            
            $sensorStatus->sensorId = $sensorInfo->sensorId;
            $sensorStatus->lastContact = $formattedDateTime;
            $sensorStatus->ipAddress = $sensorInfo->ipAddress;
            $sensorStatus->version = $sensorInfo->version;
            $sensorStatus->sensorStatus = $status;
            $sensorStatus->sensorStatusLabel = SensorStatus::getLabel($status);
            $sensorStatus->sensorStatusClass = SensorStatus::getClass($status);
            $sensorStatus->isOnline = $sensorInfo->isOnline();
            $sensorStatus->ledClass = $sensorInfo->isOnline() ? "led-green" : "led-red";
            
            $result->sensorStatuses[] = $sensorStatus;
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "No database connection";
   }
   
   echo json_encode($result);
});

$router->add("display", function($params) {
   global $DISPLAY_REGISTRY;
   
   $result = new stdClass();
   $result->success = false;
   
   if (isset($params["uid"]))
   {
      $result->success = true;
      
      $uid = $params["uid"];
      
      // Is this display registered?
      if (DisplayRegistry::isRegistered($uid))
      {
         // Retrieve the associated subdomain (if any).
         $subdomain = DisplayRegistry::getAssociatedSubdomain($uid);  // TODO: Change to getAssociatedCustomer().
         
         // Is this display associated with *this* subdomain?
         if (($subdomain != DisplayRegistry::UNKNOWN_SUBDOMAIN) &&
             (CustomerInfo::getSubdomain()) && 
             ($subdomain == CustomerInfo::getSubdomain()))
         {
            $database = FactoryStatsDatabase::getInstance();
            
            if ($database && $database->isConnected())
            {
               $queryResult = $database->getDisplayByUid($uid);
               
               $displayInfo = null;
               
               if ($queryResult && ($row = $queryResult[0]))
               {
                  // Load an existing display.
                  $displayInfo = DisplayInfo::load($row["displayId"]);
               }
               else
               {
                  // Register a new display.
                  $displayInfo = new DisplayInfo();
                  
                  $displayInfo->uid = $uid;
                  
                  $database->newDisplay($displayInfo);
                  
                  $displayInfo->displayId = $database->lastInsertId();
               }
               
               if ($displayInfo)
               {
                  // Set IP address, if provided.
                  if (isset($params["ipAddress"]))
                  {
                     $displayInfo->ipAddress = $params["ipAddress"];
                  }
                  
                  // Set version, if provided.
                  if (isset($params["version"]))
                  {
                     $displayInfo->version = $params["version"];
                  }
                  
                  // Update last contact.
                  $displayInfo->lastContact = Time::now("Y-m-d H:i:s");
                  
                  // Update display info in the database.
                  $database->updateDisplay($displayInfo);
                  
                  $presentation = PresentationInfo::load($displayInfo->presentationId);
                  
                  // If a presentation has been configured for this display ...
                  if ($presentation)
                  {
                     // If the display is enabled ...
                     if ($displayInfo->enabled)
                     {
                        $result->presentation = $presentation->getTabRotateConfig($displayInfo->scaling);
                        
                     }
                     // If the display is disabled ...
                     else
                     {
                        $result->presentation = PresentationInfo::getDefaultPresentation($displayInfo->uid)->getTabRotateConfig();
                     }
                  }
                  // If no presentation has been configured ...
                  else
                  {
                     $result->presentation = PresentationInfo::getUnconfiguredPresentation($uid)->getTabRotateConfig();
                  }
                  
                  // Mark for reset.
                  if ($displayInfo->isResetPending())
                  {
                     $result->resetPending = true;
                     
                     $database->setDisplayResetTime($displayInfo->displayId, null);
                  }
                  
                  if ($displayInfo->isUpgradePending())
                  {
                     $result->upgradePending = true;
                     $result->firmwareImage = $displayInfo->firmwareImage;
                                          
                     $database->setDisplayUpgradeTime($displayInfo->displayId, null, null);
                  }
               }
            }
         }
         // The display is associated with another subdomain.
         else if ($subdomain && ($subdomain != ""))
         {
            // Redirect to correct subdomain.
            $result->subdomain = $subdomain;
            $result->server = $subdomain . ".factorystats.com";
            $result->presentation = PresentationInfo::getRedirectingPresentation($uid)->getTabRotateConfig();
         }
         // No associated subdomain.
         else
         {
            // Redirect back to the display registry.
            $result->server = $DISPLAY_REGISTRY . ".factorystats.com";
            
            // Poor choice of naming here.  It is registered, just not associated with a subdomain.
            $result->presentation = PresentationInfo::getUnregisteredPresentation($uid)->getTabRotateConfig();            
         }
      }
      // Unregistered display.
      else
      {
         // Register with the display registry.
         DisplayRegistry::register($uid);

         // Poor choice of naming here.  It is registered (now), just not associated with a subdomain.         
         $result->presentation = PresentationInfo::getUnregisteredPresentation($uid)->getTabRotateConfig();         
      }
   }
   // No valid UID specified in query.
   else
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }
   
   echo json_encode($result);
});

$router->add("display2", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   if (!Authentication::isAuthenticated())
   {
      $result->success = true;      
      $result->displayState = DisplayState::UNAUTHORIZED;
   }
   else if (isset($params["uid"]))
   {
      $result->success = true;
      
      $uid = $params["uid"];
      
      // Is this display registered?
      if (DisplayRegistry::isRegistered($uid))
      {
         // Retrieve the associated customer (if any).
         $customerId = DisplayRegistry::getAssociatedCustomerId($uid);
         $subdomain = DisplayRegistry::getAssociatedSubdomain($uid);

         // Is this display associated with the currently authenticated customer?
         if (($customerId != CustomerInfo::UNKNOWN_CUSTOMER_ID) &&
             ($customerId == Authentication::getAuthenticatedCustomer()->customerId)) 
         {
            $database = FactoryStatsDatabase::getInstance();
            
            if ($database && $database->isConnected())
            {
               $queryResult = $database->getDisplayByUid($uid);
               
               $displayInfo = null;
               
               if ($queryResult && ($row = $queryResult[0]))
               {
                  // Load an existing display.
                  $displayInfo = DisplayInfo::load($row["displayId"]);
               }
               else
               {
                  // Register a new display.
                  $displayInfo = new DisplayInfo();
                  
                  $displayInfo->uid = $uid;
                  
                  $database->newDisplay($displayInfo);
                  
                  $displayInfo->displayId = $database->lastInsertId();
               }
               
               if ($displayInfo)
               {
                  // Set IP address, if provided.
                  if (isset($params["ipAddress"]))
                  {
                     $displayInfo->ipAddress = $params["ipAddress"];
                  }
                  
                  // Set version, if provided.
                  if (isset($params["version"]))
                  {
                     $displayInfo->version = $params["version"];
                  }
                  
                  // Update last contact.
                  $displayInfo->lastContact = Time::now("Y-m-d H:i:s");
                  
                  // Update display info in the database.
                  $database->updateDisplay($displayInfo);
                  
                  $presentation = PresentationInfo::load($displayInfo->presentationId);
                  
                  // If a presentation has been configured for this display ...
                  if ($presentation)
                  {
                     // If the display is enabled ...
                     if ($displayInfo->enabled)
                     {
                        $result->presentation = $presentation->getTabRotateConfig($displayInfo->scaling);
                     }
                     // If the display is disabled ...
                     else
                     {
                        $result->presentation = PresentationInfo::getDefaultPresentation($displayInfo->uid)->getTabRotateConfig();
                     }
                     
                     $result->fullyConfigured = true;
                     $result->displayState = DisplayState::READY;
                  }
                  // If no presentation has been configured ...
                  else
                  {
                     $result->subdomain = $subdomain;
                     $result->displayState = DisplayState::UNCONFIGURED;
                  }
                  
                  // Mark for reset.
                  if ($displayInfo->isResetPending())
                  {
                     $result->resetPending = true;
                     
                     $database->setDisplayResetTime($displayInfo->displayId, null);
                  }
                  
                  if ($displayInfo->isUpgradePending())
                  {
                     $result->upgradePending = true;
                     $result->firmwareImage = $displayInfo->firmwareImage;
                     
                     $database->setDisplayUpgradeTime($displayInfo->displayId, null, null);
                  }
               }
            }
         }
         // The display is associated with another customer.
         else if ($customerId != CustomerInfo::UNKNOWN_CUSTOMER_ID)
         {
            if (Authentication::setCustomer($customerId))
            {
               // Redirect to correct customer.
               $result->subdomain = $subdomain;
               $result->displayState = DisplayState::REDIRECTING;
            }
            else 
            {
               $result->displayState = DisplayState::UNAUTHORIZED;
            }
         }
         // No associated customer.
         else
         {
            // Poor choice of naming here.  It is registered, just not associated with a subdomain.
            $result->displayState = DisplayState::UNREGISTERED;
         }
      }
      // Unregistered display.
      else
      {
         // Register with the display registry.
         DisplayRegistry::register($uid);
         
         // Poor choice of naming here.  It is registered (now), just not associated with a subdomain.
         $result->displayState = DisplayState::UNREGISTERED;
      }
   }
   else if (isset($params["generateUid"]))
   {
      $result->success = true;
      $result->uid = DisplayInfo::generateUid();
   }
   // No valid UID specified in query.
   else
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }
   
   echo json_encode($result);
});

$router->add("displayStatus", function($params) {
   $result = new stdClass();
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result->success = true;
      $result->displayStatuses = array();
      
      $dbaseResult = $database->getDisplays();
      
      foreach ($dbaseResult as $row)
      {
         $displayInfo = DisplayInfo::load(intval($row["displayId"]));
         
         $dateTime = Time::getDateTime($displayInfo->lastContact);
         $formattedDateTime = $dateTime->format("m/d/Y h:i A");
         
         $displayStatus = new stdClass();
         $displayStatus->displayId = $displayInfo->displayId;
         
         $displayStatus->ipAddress = $displayInfo->ipAddress;
         $displayStatus->version = $displayInfo->version;
         $displayStatus->lastContact = $formattedDateTime;
         
         $status = $displayInfo->getDisplayStatus();
         $displayStatus->displayStatus = $status;
         $displayStatus->displayStatusLabel = DisplayStatus::getLabel($status);
         $displayStatus->displayStatusClass = DisplayStatus::getClass($status);
         $displayStatus->isOnline = $displayInfo->isOnline();
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

$router->add("update", function($params) {
   $result = new stdClass();
   $result->success = false;

   $stationId = StationInfo::UNKNOWN_STATION_ID;
   $shiftId = ShiftInfo::getDefaultShift();

   if (isset($params["stationId"]))
   {
      $stationId = $params->get("stationId");
   }
   else if (isset($params["macAddress"]))
   {
      $macAddress = $params->get("macAddress");

      $database = FactoryStatsDatabase::getInstance();

      if ($database && $database->isConnected())
      {
         $queryResult = $database->getButtonByMacAddress($macAddress);

         if ($queryResult && ($row = $queryResult[0]))
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
      $count = $params->getInt("count");
      
      if ($count != 0)
      {
         if (BreakInfo::getCurrentBreak($stationId, $shiftId))
         {
            BreakInfo::endBreak($stationId, $shiftId);
         }
   
         CountManager::updateCount($stationId, $shiftId, $count);
      }

      $now = Time::now("Y-m-d H:i:s");
      $startDateTime = Time::startOfDay($now);
      $endDateTime = Time::endOfDay($now);

      $totalCount = CountManager::getCount($stationId, $shiftId, $startDateTime, $endDateTime);

      $result->success = true;
      $result->stationId = $stationId;
      $result->shiftId = $shiftId;
      $result->count = $totalCount;
   }

   echo json_encode($result);
});

$router->add("break", function($params) {
   $result = new stdClass();

   $stationId = $params->getInt("stationId");
   
   $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
   if ($params->keyExists("shiftId"))
   {
      $shiftId = $params->getInt("shiftId");
   }
   else
   {
      $shiftId = ShiftInfo::getShift(Time::now("Y-m-d H:i:s"));
   }

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

$router->add("breakDescriptions", function($params) {
   $result = new stdClass();
   
   $result->success = true;
   
   $breakDescriptions = getBreakDescriptions();
   
   if ($params->getBool("flatten"))
   {
      // break.0.id = 1
      // break.0.code = "001"
      // break.0.description = "Bathroom"
      
      $index = 0;
      foreach ($breakDescriptions as $breakDescription)
      {
         $result->{"break." . $index . ".id"} = $breakDescription->breakDescriptionId;
         $result->{"break." . $index . ".code"} = $breakDescription->code;
         $result->{"break." . $index . ".desc"} = $breakDescription->description;
         
         $index++;
      }
   }
   else
   {
      $result->breakDescriptions = getBreakDescriptions();
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

   $count = CountManager::getCount($stationId, $shiftId, $startDateTime, $endDateTime);

   $result["stationId"] = $stationId;
   $result["shiftId"] = $shiftId;
   $result["count"] = $count;

   echo json_encode($result);
});

$router->add("status", function($params) {
   $result = new stdClass();
   $result->success = true;

   if (!isset($params["stationId"]) && !isset($params["stationIds"]))
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }
   else
   {
      $stationIds = array();
      if (isset($params["stationIds"]))
      {
         $stationIds = $params["stationIds"];
      }
      else  // isset($params["stationId"])
      {
         $stationIds[] = $params->getInt("stationId");
      }

      $shiftId = isset($params["shiftId"]) ? $params->get("shiftId") : ShiftInfo::UNKNOWN_SHIFT_ID;

      $result->workstations = array();
      
      foreach ($stationIds as $stationId)
      {
         $workstationStatus = WorkstationStatus::getWorkstationStatus($stationId, $shiftId);
   
         if ($workstationStatus)
         {
            $result->workstations[] = $workstationStatus;
         }
         else
         {
            $result->success = false;
            $result->error = "Failed to retrieve status";
            break;
         }
      }
      
      $result->currentShiftId = ShiftInfo::getShift(Time::now("H:i:s"));
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
   $result->success = true;
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
         if ($stationInfo->updateTime)
         {
            $dateTime = Time::getDateTime($stationInfo->updateTime);
            $stationInfo->updateTime = $dateTime->format("m-d-Y h:i a");
         }

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

$router->add("presentation", function($params) {
   $result = new stdClass();
   
   $presentationId = $params->get("presentationId");
   
   if ($presentationId)
   {
      $presentationInfo = PresentationInfo::load($presentationId);
      
      if ($presentationInfo)
      {
         $result->success = true;
         $result->presentation = $presentationInfo;
      }
      else
      {
         $result->success = false;
         $result->error = "No presentation found";
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }
   
   echo json_encode($result);
});

$router->add("slideOrder", function($params) {
   $result = new stdClass();
   
   // An associative array of slide ids to slide indexes.
   $json = stripslashes(html_entity_decode($params->get("slides")));
   $slides = json_decode($json);
   
   if ($slides)
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         foreach ($slides as $slideId => $slideIndex)
         {
            $database->updateSlideOrder(intval($slideId), $slideIndex);
         }
         
         $result->success = true;
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
      $result->error = "Invalid parameters";
   }
   
   echo json_encode($result);
});

$router->add("customer", function($params) {
   $result = new stdClass();
   $result->success = false;
   
   if (isset($params["customerId"]))
   {
      $customerId = $params->getInt("customerId");
      
      $customerInfo = CustomerInfo::load($customerId);
      
      if ($customerInfo)
      {
         if (Authentication::setCustomer($customerId))
         {
            $result->success = true;
            $result->customerId = $customerId;
            $result->customerName = $customerInfo->name;
         }
         else
         {
            $result->success = false;
            $result->error = "Authentication failure";
         }
      }
      else 
      {
         $result->error = "Invalid customer";
      }
   }
   else 
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }

   echo json_encode($result);
});

$router->add("userCustomer", function($params) {
   $result = new stdClass();
   
   if (isset($params["userId"]) &&
       isset($params["customerId"]) &&
       isset($params["action"]))
   {
      $userId = intval($params["userId"]);
      $customerId = intval($params["customerId"]);
      $action = $params["action"];
      
      if (!Authentication::checkPermissions(Permission::USER_CONFIG))
      {
         $result->success = false;
         $result->error = "Permissions error";
      }
      else if (!CustomerInfo::validateUserForCustomer(Authentication::getAuthenticatedUser()->userId, $customerId))
      {
         $result->success = false;
         $result->error = "Site permissions error";
      }
      else
      {
         $database = FactoryStatsGlobalDatabase::getInstance();
         
         if ($database && $database->isConnected())
         {
            $userInfo = UserInfo::load($userId);
            $customerInfo = CustomerInfo::load($customerId);
            
            if (!$userInfo)
            {
               $result->success = false;
               $result->error = "Invalid user";
            }
            else if (!$customerInfo)
            {
               $result->success = false;
               $result->error = "Invalid customer";
            }
            else if ($action == "add")
            {
               $result->success = $database->addUserToCustomer($userId, $customerId);
            }
            else if ($action == "remove")
            {
               $result->success = $database->removeUserFromCustomer($userId, $customerId);
            }
            else
            {
               $result->success = false;
               $result->error = "Invalid action";
            }
         }
         else
         {
            $result->success = false;
            $result->error = "No database connection";
         }
      }
   }
   else
   {
      $result->success = false;
      $result->error = "Invalid parameters";
   }
      
   echo json_encode($result);
});
   
// *****************************************************************************
//                                     Public API
// *****************************************************************************

$router->add("apiUser", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->users = array();
   
   /*
   Authentication::authenticate();
   
   if (!Authentication::checkPermissions(Permission::USER_CONFIG))
   {
      $result->success = false;
      $result->error = "Permissions error";
   }
   else
   */
   {
      /*
      $database = FactoryStatsGlobalDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $customerId = CustomerInfo::getCustomerId($_SESSION['authenticatedUserId']);
         
         $databaseResult = $database->getUsersForCustomer($customerId);
      */
      
       $database = FactoryStatsDatabase::getInstance();
       
       if ($database && $database->isConnected())
       {
         $databaseResult = $database->getUsers();
         
         foreach ($databaseResult as $row)
         {
            $userInfo = UserInfo::load(intval($row["userId"]));
            unset($userInfo->passwordHash);
            unset($userInfo->authToken);
            
            $result->users[$userInfo->userId] = $userInfo;
         }
         
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});
   
$router->add("apiShift", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->shifts = array();
   
   /*
   Authentication::authenticate();
      
   if (!Authentication::checkPermissions(Permission::CUSTOMER_CONFIG))
   {
      $result->success = false;
      $result->error = "Permissions error";
   }
   else
   */
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $databaseResult = $database->getShifts();
         
         foreach ($databaseResult as $row)
         {
            $shiftInfo = ShiftInfo::load(intval($row["shiftId"]));
            
            $result->shifts[$shiftInfo->shiftId] = $shiftInfo;
         }
         
         $result->currentShiftId = ShiftInfo::getShift(Time::now("H:i:s"));
         
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});

$router->add("apiStation", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->stations = array();
   
   /*
   Authentication::authenticate();
   
   if (!Authentication::checkPermissions(Permission::STATION_CONFIG))
   {
      $result->success = false;
      $result->error = "Permissions error";
   }
   else
   */
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $databaseResult = $database->getStations();
         
         foreach ($databaseResult as $row)
         {
            $stationInfo = StationInfo::load(intval($row["stationId"]));
            
            $result->stations[$stationInfo->stationId] = $stationInfo;
         }
         
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});
            
$router->add("apiButton", function($params) {
   $result = new stdClass();
   
   echo json_encode($result);
});
               
$router->add("apiSensor", function($params) {
   $result = new stdClass();
   
   echo json_encode($result);
});
                  
$router->add("apiDisplay", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->displays = array();
   
   /*
   Authentication::authenticate();
   
   if (!Authentication::checkPermissions(Permission::STATION_CONFIG))
   {
      $result->success = false;
      $result->error = "Permissions error";
   }
   else
   */
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $databaseResult = $database->getDisplays();
         
         foreach ($databaseResult as $row)
         {
            $displayInfo = DisplayInfo::load(intval($row["displayId"]));
            
            $result->displays[$displayInfo->displayId] = $displayInfo;
         }
         
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});
                     
$router->add("apiCount", function($params) {
   $result = new stdClass();
   $result->success = false;
   $result->counts = array();
   
   /*
   Authentication::authenticate();
   
   if (!Authentication::checkPermissions(Permission::WORKSTATION))
   {
      $result->success = false;
      $result->error = "Permissions error";
   }
   else
   */
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $stations = array();
         if (isset($params["stationId"]))
         {
            $stations[] = $params->getInt("stationId");
         }
         else
         {
            $stations = getStations();
         }
         
         $shifts = array();
         if (isset($params["shiftId"]))
         {
            $shifts[] = $params->getInt("shiftId");
         }
         else
         {
            $shifts = getShifts();
         }
         
         $dateTime =
            (isset($params["date"])) ?
               $params->get("date") :
               Time::now("H:i:s");
         
         foreach ($shifts as $shiftId)
         {
            // Get start and end times based on the shift.
            $shiftInfo = ShiftInfo::load($shiftId);
            $evaluationTimes = $shiftInfo->getEvaluationTimes($dateTime, $dateTime);
            
            foreach ($stations as $stationId)
            {
               $result->counts[$stationId] = array();
               
               $result->counts[$stationId][$shiftId] = new stdClass();
 
               $result->counts[$stationId][$shiftId]->stationId = $stationId;
               $result->counts[$stationId][$shiftId]->shiftId = $shiftId;
               $result->counts[$stationId][$shiftId]->startDateTime = $evaluationTimes->startDateTime;
               $result->counts[$stationId][$shiftId]->endDateTime = $evaluationTimes->endDateTime;
               
               $result->counts[$stationId][$shiftId]->count = FactoryStatsDatabase::getInstance()->getCount($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
               
               $result->counts[$stationId][$shiftId]->firstEntry = $database->getFirstEntry($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
               
               $result->counts[$stationId][$shiftId]->updateTime = $database->getLastEntry($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);
               
               $result->counts[$stationId][$shiftId]->averageCountTime = Stats::getAverageCountTime($stationId, $shiftId, $evaluationTimes->startDateTime, $evaluationTimes->endDateTime);               
            }
         }
         
         $result->success = true;
      }
      else
      {
         $result->success = false;
         $result->error = "No database connection";
      }
   }
   
   echo json_encode($result);
});

$router->route();
?>