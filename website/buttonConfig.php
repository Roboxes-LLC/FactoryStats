<?php

require_once 'common/buttonInfo.php';
require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';
require_once 'common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::BUTTON_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function renderTable()
{
   echo 
<<<HEREDOC
   <table id="button-table">
      <tr>
         <!--th>Button ID</th-->
         <th>ID</th>
         <!--th>Name</th-->
         <!--th>IP Address</th-->
         <th>Workstation</th>
         <th>Last Contact</th>
         <th>Status</th>
         <th>Test</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getButtons();
      
      foreach ($result as $row)
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
         
         $rowId = "button-" . $buttonInfo->buttonId;
         
         $clickAction = $buttonInfo->getButtonAction(ButtonPress::SINGLE_CLICK);
         $doubleClickAction = $buttonInfo->getButtonAction(ButtonPress::DOUBLE_CLICK);
         $holdAction = $buttonInfo->getButtonAction(ButtonPress::HOLD);
         $enabled = $buttonInfo->enabled ? "true" : "false";
         
         $buttonStatus = $buttonInfo->getButtonStatus();
         $buttonStatusLabel = ButtonStatus::getLabel($buttonStatus);
         $buttonStatusClass = ButtonStatus::getClass($buttonStatus);
         
         $ledClass = "led-green";
         
         echo 
<<<HEREDOC
         <tr id="$rowId">
            <!--td>$buttonInfo->buttonId</td-->
            <td>$buttonInfo->uid</td>
            <!--td>$stationName</td-->
            <!--td>$buttonInfo->ipAddress</td-->
            <td>$stationName</td>
            <td>$buttonInfo->lastContact</td>
            <td class="$buttonStatusClass">$buttonStatusLabel</td>
            <td><div class="button-led $ledClass"></div></td>
            <td><button class="config-button" onclick="setButtonId($buttonInfo->buttonId); setButtonConfig($buttonInfo->buttonId, $buttonInfo->stationId, $clickAction, $doubleClickAction, $holdAction, $enabled); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setButtonId($buttonInfo->buttonId); showModal('confirm-delete-modal');">Delete</button></div></td>
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
      $result = $database->getStations();
      
      foreach ($result as $row)
      {
         $options .= "<option value=\"{$row["stationId"]}\">{$row["name"]}</option>";
      }
   }
   
   return ($options);
}

function getButtonClickOptions()
{
   $noAction = ButtonAction::UNKNOWN;
   
   $options = "<option value=\"$noAction\">No action</option>";
   
   foreach (ButtonAction::$values as $buttonAction)
   {
      $label = ButtonAction::getLabel($buttonAction);
      
      $options .= "<option value=\"$buttonAction\">$label</option>";      
   }
   
   return ($options);
}

function deleteButton($buttonId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteButton($buttonId);
   }
}

function updateButton($buttonId, $stationId, $clickAction, $doubleClickAction, $holdAction, $enabled)
{
   $buttonInfo = ButtonInfo::load($buttonId);
   
   $buttonInfo->stationId = $stationId;
   $buttonInfo->setButtonAction(ButtonPress::SINGLE_CLICK, $clickAction);
   $buttonInfo->setButtonAction(ButtonPress::DOUBLE_CLICK, $doubleClickAction);
   $buttonInfo->setButtonAction(ButtonPress::HOLD, $holdAction);
   $buttonInfo->enabled = $enabled;
   
   $database = FactoryStatsDatabase::getInstance();
   
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
      updateButton(
         $params->getInt("buttonId"), 
         $params->getInt("stationId"),
         $params->getInt("clickAction"),
         $params->getInt("doubleClickAction"),
         $params->getInt("holdAction"),
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
   
   <title>Hardware Button Status</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="button-id-input" type="hidden" name="buttonId">
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
         <label>Button name</label>
         <input id="button-name-input" type="text" form="config-form" name="name"></input>
      </div-->
            
      <div class="flex-vertical input-block">
         <label>Associated workstation</label>
         <select id="station-id-input" form="config-form" name="stationId">
            <?php echo getStationOptions();?>
         </select>
      </div>
      
      <div class="flex-vertical input-block">
         <label>Click action</label>
         <select id="click-action-input" form="config-form" name="clickAction">
            <?php echo getButtonClickOptions();?>
         </select>
      </div>
      
      <div class="flex-vertical input-block">
         <label>Double-click action</label>
         <select id="double-click-action-input" form="config-form" name="doubleClickAction">
            <?php echo getButtonClickOptions();?>
         </select>
      </div>
          
      <div class="flex-vertical input-block">
         <label>Hold action</label>
         <select id="hold-action-input" form="config-form" name="holdAction">
            <?php echo getButtonClickOptions();?>
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
      <p>Really delete button?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/buttonConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setButtonId(buttonId)
   {
      var input = document.getElementById('button-id-input');
      input.setAttribute('value', buttonId);
   }
   
   function setButtonConfig(buttonId, stationId, clickAction, doubleClickAction, holdAction, enabled)
   {
      setButtonId(buttonId);
      
      document.getElementById('station-id-input').value = stationId;
      document.getElementById('click-action-input').value = clickAction;
      document.getElementById('double-click-action-input').value = doubleClickAction;
      document.getElementById('hold-action-input').value = holdAction;
      document.getElementById('enabled-input').checked = enabled;
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
   
   // Start a 1 second timer to update the button statuses.
   setInterval(function(){updateButtonStatus();}, 1000);
</script>

</body>

</html>