<?php

require_once 'common/displayInfo.php';
require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';

Time::init();

session_start();

function renderTable()
{
   echo
<<<HEREDOC
   <table>
      <tr>
         <th>Display ID</th>
         <th>MAC Address</th>
         <th>IP Address</th>
         <th>Workstation</th>
         <th>Last Contact</th>
         <th>Status</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;

   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getDisplays();

      while ($result && $row = $result->fetch_assoc())
      {
         $displayInfo = DisplayInfo::load($row["displayId"]);

         $stationName = "Workstation Summary";
         if ($displayInfo->stationId != StationInfo::UNKNOWN_STATION_ID)
         {
            $stationInfo = StationInfo::load($displayInfo->stationId);

            if ($stationInfo)
            {
               $stationName = $stationInfo->name;
            }
         }

         $id = "display-" . $displayInfo->displayId;
         $isOnline = $displayInfo->isOnline();
         $status = $isOnline ? "Online" : "Offline";
         $ledClass = $isOnline ? "led-green" : "led-red";

         echo
<<<HEREDOC
         <tr>
            <td>$displayInfo->displayId</td>
            <td>$displayInfo->macAddress</td>
            <td>$displayInfo->ipAddress</td>
            <td>$stationName</td>
            <td>$displayInfo->lastContact</td>
            <td id="$id"><div>$status</div><div class="$ledClass"></div></td>
            <td><button class="config-button" onclick="setDisplayId($displayInfo->displayId); setStationId($displayInfo->stationId); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setDisplayId($displayInfo->displayId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }

   echo "</table>";
}

function getOptions()
{
   $options = "<option value=\"0\">Workstation Summary</option>";

   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getStations();

      while ($result && $row = $result->fetch_assoc())
      {
         $options .= "<option value=\"{$row["stationId"]}\">{$row["name"]}</option>";
      }
   }

   return ($options);
}

function deleteDisplay($displayId)
{
   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->deleteDisplay($displayId);
   }
}

function updateDisplay($displayId, $stationId)
{
   $diplayInfo = DisplayInfo::load($displayId);
   $diplayInfo->stationId = $stationId;

   $database = FlexscreenDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->updateDisplay($diplayInfo);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
      {
         deleteDisplay($params->get("displayId"));
         break;
      }

   case "update":
      {
         updateDisplay($params->get("displayId"), $params->get("stationId"));
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

   <title>Display Config</title>

   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css"/>

</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="display-id-input" type="hidden" name="displayId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>

   <div class="flex-horizontal main">
      <?php renderTable();?>
   </div>

</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Associated workstation</label>
      <select id="station-id-input" form="config-form" name="stationId">
         <?php echo getOptions();?>
      </select>
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete button?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js"></script>
<script src="script/modal.js"></script>
<script src="script/displayConfig.js"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setDisplayId(displayId)
   {
      var input = document.getElementById('display-id-input');
      input.value = displayId;
   }

   function setStationId(stationId)
   {
      var input = document.getElementById('station-id-input');
      input.value = stationId;
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }

   // Start a 10 second timer to update the display status LEDs.
   setInterval(function(){updateDisplayStatus();}, 10000);
</script>

</body>

</html>