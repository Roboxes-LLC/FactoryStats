<?php

if (!defined('ROOT')) require_once 'root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/header.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::CUSTOMER_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

Time::init(CustomerInfo::getTimeZone());

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Shift ID</th>
         <th>Name</th>
         <th>Start time</th>
         <th>End time</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getShifts();
      
      foreach ($result as $row)
      {
         $shiftInfo = ShiftInfo::load($row["shiftId"]);
         
         if ($shiftInfo)
         {
            $startTime = new DateTime($shiftInfo->startTime);
            $endTime = new DateTime($shiftInfo->endTime);
            
            echo 
<<<HEREDOC
            <tr>
               <td>$shiftInfo->shiftId</td>
               <td>$shiftInfo->shiftName</td>
               <td>{$startTime->format("h:i:s A")}</td>
               <td>{$endTime->format("h:i:s A")}</td>
               <td><button class="config-button" onclick="setShiftId($shiftInfo->shiftId); setShiftInfo('$shiftInfo->shiftName', '$shiftInfo->startTime', '$shiftInfo->endTime'); showModal('config-modal');">Configure</button></div></td>
               <td><button class="config-button" onclick="setShiftId($shiftInfo->shiftId); showModal('confirm-delete-modal');">Delete</button></div></td>
            </tr>
HEREDOC;
         }
      }
   }
   
   echo "</table>";
}

function addShift($shiftName, $startTime, $endTime)
{
   $shiftInfo = new ShiftInfo();
   $shiftInfo->shiftName = $shiftName;
   $shiftInfo->startTime = $startTime;
   $shiftInfo->endTime = $endTime;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->newShift($shiftInfo);
   }
}

function deleteShift($shiftId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteShift($shiftId);
   }
}

function updateShift($shiftId, $shiftName, $startTime, $endTime)
{
   $shiftInfo = ShiftInfo::load($shiftId);
   $shiftInfo->shiftName = $shiftName;
   $shiftInfo->startTime = $startTime;
   $shiftInfo->endTime = $endTime;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->updateShift($shiftInfo);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteShift($params->get("shiftId"));
      break;      
   }
   
   case "update":
   {
      if (is_numeric($params->get("shiftId")))
      {
         updateShift(
            $params->get("shiftId"), 
            $params->get("shiftName"), 
            $params->get("startTime"), 
            $params->get("endTime"));
      }
      else
      {
         addShift(
            $params->get("shiftName"),
            $params->get("startTime"),
            $params->get("endTime"));
      }
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
   
   <title>Shift Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="shift-id-input" type="hidden" name="shiftId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setShiftInfo('', '', ''); showModal('config-modal');">New Shift</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Name</label>
      <input id="shift-name-input" type="text" form="config-form" name="shiftName"></input>
      <label>Start time</label>
      <input id="start-time-input" type="time" form="config-form" name="startTime"></input>
      <label>End time</label>
      <input id="end-time-input" type="time" form="config-form" name="endTime"></input>
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete shift?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   function setShiftId(shiftId)
   {
      var input = document.getElementById('shift-id-input');
      input.setAttribute('value', shiftId);
   }

   function setShiftInfo(shiftName, startTime, endTime)
   {
      var input = document.getElementById('shift-name-input');
      input.value = shiftName;

      input = document.getElementById('start-time-input');
      input.value = startTime;

      input = document.getElementById('end-time-input');
      input.value = endTime;
   }

   function setAction(action)
   {
      var input = document.getElementById('action-input');
      input.value = action;
   }
</script>

</body>

</html>