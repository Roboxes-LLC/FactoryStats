<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/sensorInfo.php';
require_once ROOT.'/common/stationInfo.php';
require_once ROOT.'/common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::SENSOR_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function renderTable()
{
   echo 
<<<HEREDOC
   <table id="sensor-table">
      <tr>
         <th>ID</th>
         <th>Type</th>
         <th>Workstation</th>
         <th>Last Contact</th>
         <th>IP Address</th>
         <th>Firmware</th>
         <th>Status</th>
         <th>Online</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getSensors();
      
      foreach ($result as $row)
      {
         $sensorInfo = SensorInfo::load($row["sensorId"]);
         
         $stationName = "";
         if ($sensorInfo->stationId != StationInfo::UNKNOWN_STATION_ID)
         {
            $stationInfo = StationInfo::load($sensorInfo->stationId);
            
            if ($stationInfo)
            {
               $stationName = $stationInfo->name;
            }
         }
         
         $rowId = "sensor-" . $sensorInfo->sensorId;
         
         $dateTime = Time::getDateTime($sensorInfo->lastContact);
         $lastContact = $dateTime->format("m/d/Y h:i A");
         
         $sensorTypeLabel = SensorType::getLabel($sensorInfo->sensorType);
         $enabled = $sensorInfo->enabled ? "true" : "false";
         $sensorStatus = $sensorInfo->getSensorStatus();
         $sensorStatusLabel = SensorStatus::getLabel($sensorStatus);
         $sensorStatusClass = SensorStatus::getClass($sensorStatus);
         $isOnline = $sensorInfo->isOnline();
         $ledClass = $isOnline ? "led-green" : "led-red";
         
         echo 
<<<HEREDOC
         <tr id="$rowId">
            <td>$sensorInfo->uid</td>
            <td>$sensorTypeLabel</td>
            <td>$stationName</td>
            <td>$lastContact</td> 
            <td>$sensorInfo->ipAddress</td>
            <td>$sensorInfo->version</td>
            <td class="$sensorStatusClass">$sensorStatusLabel</td>
            <td><div class="display-led $ledClass"></div></td>
            <td><button class="config-button" onclick="setSensorId($sensorInfo->sensorId); setSensorConfig($sensorInfo->sensorId, $sensorInfo->sensorType, $sensorInfo->stationId, $enabled); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setSensorId($sensorInfo->sensorId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function getStationOptions()
{
   $options = "";

   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations(false);  // Exclude virtual stations
      
      foreach ($result as $row)
      {
         $options .= "<option value=\"{$row["stationId"]}\">{$row["name"]}</option>";
      }
   }
   
   return ($options);
}

function getSensorTypeOptions()
{
   $html = "<option style=\"display:none\">";
   
   foreach (SensorType::$values as $sensorType)
   {
      $label = SensorType::getLabel($sensorType);
      
      $html .= "<option value=\"$sensorType\">$label</option>";
   }
   
   return ($html);
}

function deleteSensor($sensorId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteSensor($sensorId);
   }
}

function updateSensor($sensorId, $sensorType, $stationId, $enabled)
{
   $sensorInfo = SensorInfo::load($sensorId);
   
   if ($sensorInfo)
   {
      $sensorInfo->sensorType = $sensorType;
      $sensorInfo->stationId = $stationId;
      $sensorInfo->enabled = $enabled;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $database->updateSensor($sensorInfo);
      }
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteSensor($params->get("sensorId"));
      break;      
   }
   
   case "update":
   {
      updateSensor(
         $params->getInt("sensorId"), 
         $params->getInt("sensorType"),
         $params->getInt("stationId"),
         $params->getBool("enabled"));         
      break;
   }
   
   default:
   {
      break;
   }
}

?>

<html>

<head>

   <meta name="viewport" content="width=device-width, initial-scale=1">
   
   <title>Sensor Status</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="sensor-id-input" type="hidden" name="sensorId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="flex-horizontal main">
      <?php renderTable();?>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      
      <!--div class="flex-vertical input-block">
         <label>Sensor name</label>
         <input id="sensor-name-input" type="text" form="config-form" name="name"></input>
      </div-->
      
     <div class="flex-vertical input-block">
         <label>Sensor type</label>
         <select id="sensor-type-input" form="config-form" name="sensorType">
            <?php echo getSensorTypeOptions();?>
         </select>
      </div>
            
      <div class="flex-vertical input-block">
         <label>Associated workstation</label>
         <select id="station-id-input" form="config-form" name="stationId">
            <?php echo getStationOptions();?>
         </select>
      </div>
      
      <div class="flex-horizontal input-block">
         <label>Enabled</label>
         <input id="enabled-input" type="checkbox" form="config-form" name="enabled">
      </div>
      
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete sensor?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/sensorConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setSensorId(sensorId)
   {
      var input = document.getElementById('sensor-id-input');
      input.setAttribute('value', sensorId);
   }
   
   function setSensorConfig(sensorId, sensorType, stationId, enabled)
   {
      setSensorId(sensorId);
      
      document.getElementById('sensor-type-input').value = sensorType;
      document.getElementById('station-id-input').value = stationId;
      document.getElementById('enabled-input').checked = enabled;
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
   
   // Start a 1 second timer to update the sensor statuses.
   setInterval(function(){updateSensorStatus();}, 1000);
</script>

</body>

</html>