<?php

require_once 'common/buttonInfo.php';
require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Button ID</th>
         <th>MAC Address</th>
         <th>IP Address</th>
         <th>Workstation ID</th>
         <th>Last Contact</th>
         <th>Status</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getButtons();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $buttonInfo = ButtonInfo::load($row["buttonId"]);
         
         $stationName = "";
         if ($buttonInfo->stationId != StationInfo::UNKNOWN_STATION_ID)
         {
            $stationInfo = StationInfo::load($buttonInfo->stationId);
            
            if ($stationInfo)
            {
               $stationName = $stationInfo->name;
            }
         }
         
         $isOnline = $buttonInfo->isOnline();
         $status = $isOnline ? "Online" : "Offline";
         $ledClass = $isOnline ? "led-green" : "led-red";
         
         echo 
<<<HEREDOC
         <tr>
            <td>$buttonInfo->buttonId</td>
            <td>$buttonInfo->macAddress</td>
            <td>$buttonInfo->ipAddress</td>
            <td>$stationName</td>
            <td>$buttonInfo->lastContact</td>
            <td>$status <div class="$ledClass"></div></td>
            <td><button class="config-button" onclick="setButtonId($buttonInfo->buttonId); setStationId($buttonInfo->stationId); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setButtonId($buttonInfo->buttonId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function getOptions()
{
   $options = "";

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

function deleteButton($buttonId)
{
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteButton($buttonId);
   }
}

function updateButton($buttonId, $stationId)
{
   $buttonInfo = ButtonInfo::load($buttonId);
   $buttonInfo->stationId = $stationId;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->updateButton($buttonInfo);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteButton($params->get("buttonId"));
      break;      
   }
   
   case "update":
   {
      updateButton($params->get("buttonId"), $params->get("stationId"));
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
   
   <title>Hardware Button Status</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="button-id-input" type="hidden" name="buttonId">
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
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   setTimeout(function(){
      if (!isModalVisible())
      {
         window.location.reload(1);
      }
   }, 5000);

   function setButtonId(buttonId)
   {
      var input = document.getElementById('button-id-input');
      input.setAttribute('value', buttonId);
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
</script>

</body>

</html>