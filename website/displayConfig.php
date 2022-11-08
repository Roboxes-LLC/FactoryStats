<?php

require_once 'common/database.php';
require_once 'common/displayFirmwareInfo.php';
require_once 'common/displayInfo.php';
require_once 'common/displayRegistry.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';
require_once 'common/version.php';

Time::init();

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::DISPLAY_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

function renderTable()
{
   echo
<<<HEREDOC
   <table id="display-table">
      <tr>
         <th>ID</th>
         <th>Description</th>
         <th>IP Address</th>
         <th>Version</th>
         <th>Last Contact</th>
         <th>Status</th>
         <th>Online</th>
         <th></th>
         <th></th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;

   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getDisplays();

      foreach ($result as $row)
      {
         $displayInfo = DisplayInfo::load($row["displayId"]);

         $id = "display-" . $displayInfo->displayId;
         
         $dateTime = new DateTime($displayInfo->lastContact, new DateTimeZone('America/New_York'));
         $formattedDateTime = $dateTime->format("m/d/Y h:i A");
         
         $displayStatus = $displayInfo->getDisplayStatus();
         $displayStatusLabel = DisplayStatus::getLabel($displayStatus);
         $displayStatusClass = DisplayStatus::getClass($displayStatus);
         $isOnline = $displayInfo->isOnline();
         $ledClass = $isOnline ? "led-green" : "led-red";

         echo
<<<HEREDOC
         <tr id="$id">
            <td>$displayInfo->uid</td>
            <td>$displayInfo->name</td>
            <td>$displayInfo->ipAddress</td>
            <td>$displayInfo->version</td>
            <td>$formattedDateTime</td>
            <td class="$displayStatusClass">$displayStatusLabel</td>
            <td><div class="display-led $ledClass"></div></td>
            <td><button class="config-button" onclick="setDisplayConfig($displayInfo->displayId, '$displayInfo->name', $displayInfo->scaling, $displayInfo->presentationId, $displayInfo->enabled); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setDisplayId($displayInfo->displayId); showModal('confirm-reset-modal');"">Reset</button></div></td>
            <td><button class="config-button" onclick="setDisplayId($displayInfo->displayId); showModal('select-firmware-modal');"">Upgrade</button></div></td>
            <td><button class="config-button" onclick="setDisplayId($displayInfo->displayId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }

   echo "</table>";
}

function getPresentationOptions()
{
   $options = "<option value=\"0\">None</option>";

   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $result = $database->getPresentations();

      foreach ($result as $row)
      {
         $options .= "<option value=\"{$row["presentationId"]}\">{$row["name"]}</option>";
      }
   }

   return ($options);
}

function deleteDisplay($displayId)
{
   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->deleteDisplay($displayId);
   }
}

function updateDisplay($displayId, $name, $scaling, $presentationId, $enabled)
{
   $displayInfo = DisplayInfo::load($displayId);
   $displayInfo->name = $name;
   $displayInfo->scaling = $scaling;
   $displayInfo->presentationId = $presentationId;
   $displayInfo->enabled = $enabled;

   $database = FactoryStatsDatabase::getInstance();

   if ($database && $database->isConnected())
   {
      $database->updateDisplay($displayInfo);
   }
}

function resetDisplay($displayId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->setDisplayResetTime($displayId, Time::now("Y-m-d H:i:s"));
   }
}

function upgradeDisplay($displayId, $firmwareImage)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->setDisplayUpgradeTime($displayId, Time::now("Y-m-d H:i:s"), $firmwareImage);
   }
}

function getParams()
{
   static $params = null;
   
   if (!$params)
   {
      $params = Params::parse();
   }
   
   return ($params);
}

function getUid()
{
   $uid = "";
   
   $params = getParams();
   
   if ($params->keyExists("uid"))
   {
      $uid = $params->get("uid");
   }
   
   return ($uid);
}

$displayAdded = false;

function showAddSuccess()
{
   global $displayAdded;
   
   $params = getParams();
   
   return (($params->get("action") == "add") &&
           ($displayAdded == true));
}

function showAddFailure()
{
   global $displayAdded;
   
   $params = getParams();
   
   return (($params->get("action") == "add") &&
           ($displayAdded == false));
}

// *****************************************************************************
//                              Action handling

$params = getParams();

switch ($params->get("action"))
{
   case "delete":
   {
      $displayInfo = DisplayInfo::load($params->get("displayId"));
      
      if ($displayInfo)
      {
         // Remove the display from the domain database.
         deleteDisplay($displayInfo->displayId);
         
         // Remove from the global registry.
         DisplayRegistry::unregister($displayInfo->uid);
      }
      break;
   }

   case "update":
   {
      updateDisplay($params->getInt("displayId"), $params->get("name"), $params->getInt("scaling"), $params->getInt("presentationId"), $params->getBool("enabled"));
      break;
   }
   
   case "add":
   {
      $uid = getUid();
      $subdomain = CustomerInfo::getSubdomain();

      if (($uid != "") &&
          ($subdomain != "") &&
          (DisplayRegistry::isRegistered($uid)))
      {
         DisplayRegistry::associateWithSubdomain($uid, $subdomain);
         $displayAdded = true;
      }
      break;
   }
   
   case "reset":
   {
      resetDisplay($params->get("displayId"));
      break;
   }
   
   case "upgrade":
   {
      upgradeDisplay($params->get("displayId"), $params->get("firmwareImage"));
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

   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>

</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="display-id-input" type="hidden" name="displayId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>

   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="showModal('add-modal');">Add Display</button>
         <br>   
         <?php renderTable();?>
      </div>
   </div>

</div>

<!--  Modal dialogs -->

<div id="add-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>

      <div class="flex-vertical input-block">
         To add a new Factory Stats display to your system:
         <ul>
            <li>Connect power and HDMI connections</li>
            <li>Connect Ethernet connection or setup Wifi using Berry Lan app</li>
            <li>Wait for configuration screen to load</li>
            <li>Enter the six digit display ID</li>          
         </ul>
      </div>
      
      <div class="flex-vertical input-block">
         <label>Display ID</label>
         <input type="text" form="config-form" name="uid"> 
      </div>
      
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('add')">Add</button>
      </div>
   </div>
</div>

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      
      <div class="flex-vertical input-block">
         <label>Description</label>
         <input id="name-input" type="text" form="config-form" name="name">
      </div>

      <div class="flex-vertical input-block">
         <label>Scaling</label>
         <select id="scaling-input" form="config-form" name="scaling" oninput="onPresentationIdChanged(parseInt(this.value))">
            <?php echo DisplaySize::getOptions(null);?>
         </select>
      </div>
      
      <div class="flex-vertical input-block">
         <label>Presentation</label>
         <div class="flex-horizontal">
            <select id="presentation-id-input" form="config-form" name="presentationId" oninput="onPresentationIdChanged(parseInt(this.value))">
               <?php echo getPresentationOptions();?>
            </select>
            &nbsp;
            <button id="edit-presentation-button" class="config-button small">Edit</button>
         </div>
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
      <p>Really delete display?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<div id="confirm-reset-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really reset display?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('reset')">Confirm</button>
   </div>
</div>

<div id="select-firmware-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Select display firmware</p>
      <select form="config-form" name="firmwareImage"><?php echo DisplayFirmwareInfo::getOptions() ?></select>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('upgrade')">Confirm</button>
   </div>
</div>

<div id="add-success-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Factory Stats display <b><?php echo getUid(); ?></b> is registered with <?php echo $_SERVER['HTTP_HOST'] ?></p>
   </div>
</div>

<div id="add-failure-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Factory Stats display <b><?php echo getUid(); ?></b> could not be registered with <?php echo $_SERVER['HTTP_HOST'] ?>.</p>
      <p>Please make sure your device is powered on and connected to the Internet.</p>
   </div>
</div>

<script src="script/common.js<?php echo versionQuery();?>"></script>
<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/displayConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setDisplayId(displayId)
   {
      var input = document.getElementById('display-id-input');
      input.value = displayId;
   }

   function setDisplayConfig(displayId, name, scaling, presentationId, enabled)
   {
      setDisplayId(displayId);
      
      document.getElementById('name-input').value = name;
      document.getElementById('scaling-input').value = scaling;
      document.getElementById('presentation-id-input').value = presentationId;
      document.getElementById('enabled-input').checked = enabled;
      
      // Hide the edit presentation button for unconfigured displays.
      onPresentationIdChanged(presentationId);
   }
   
   function onPresentationIdChanged(presentationId)
   {
      // Hide the edit presentation button for unconfigured displays.
      if (presentationId != 0)
      {
         show("edit-presentation-button", "block");
         document.getElementById('edit-presentation-button').onclick = function(){document.location = "slideConfig.php?action=edit&presentationId=" + presentationId};
      }
      else
      {
         hide("edit-presentation-button");     
      }
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }

   // Start a 10 second timer to update the display status LEDs.
   setInterval(function(){updateDisplayStatus();}, 10000);
   
   // Show add display success/failure modal dialogs, if necessary.
   if (<?php echo showAddSuccess() ? "true" : "false";?>)
   {
      showModal("add-success-modal");
   }
   else if (<?php echo showAddFailure() ?  "true" : "false"; ?>)
   {
      showModal("add-failure-modal");
   }
</script>

</body>

</html>