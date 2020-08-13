<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';

Time::init();

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::STATION_CONFIG)))
{
   header('Location: index.php?action=logout');
   exit;
}

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Label</th>
         <th>Description</th>
         <th>Cycle Time (s)</th>
         <th>Last Update</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $stationId = $row["stationId"];
         
         $stationInfo = StationInfo::load($stationId);

         echo 
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$stationInfo->label</td>
            <td>$stationInfo->description</td>
            <td>$stationInfo->cycleTime</td>
            <td id="station-$stationId-update-time"></td>
            <td><button class="config-button" onclick="setStationId($stationInfo->stationId); setStationInfo('$stationInfo->name', '$stationInfo->label', '$stationInfo->description', $stationInfo->cycleTime); showModal('config-station-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setStationId($stationInfo->stationId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function addStation($name, $label, $description, $cycleTime)
{
   $stationInfo = new StationInfo();
   $stationInfo->name = $name;
   $stationInfo->label = $label;
   $stationInfo->description = $description;
   $stationInfo->cycleTime = is_numeric($cycleTime) ? intval($cycleTime) : 0;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->addStation($stationInfo);
   }
}

function deleteStation($stationId)
{
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteStation($stationId);
   }
}

function updateStation($stationId, $name, $label, $description, $cycleTime)
{
   $stationInfo = StationInfo::load($stationId);
   $stationInfo->stationId = $stationId;
   $stationInfo->name = $name;
   $stationInfo->label = $label;
   $stationInfo->description = $description;
   $stationInfo->cycleTime = $cycleTime;
   
   $database = FlexscreenDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->updateStation($stationInfo);
   }
}

// *****************************************************************************
//                              Action handling

$params = Params::parse();

switch ($params->get("action"))
{      
   case "delete":
   {
      deleteStation($params->get("stationId"));
      break;
   }
      
   case "update":
   {
      if (is_numeric($params->get("stationId")))
      {
         updateStation($params->get("stationId"), 
                       $params->get("name"),
                       $params->get("label"),
                       $params->get("description"),
                       $params->get("cycleTime"));
      }
      else
      {
         addStation($params->get("name"),
                    $params->get("label"),
                    $params->get("description"),
                    $params->get("cycleTime"));
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
   
   <title>Workstation Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css"/>
   
   <style>
      table, th, td {
         color: white;
         border: 1px solid white;
      }
   </style>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="station-id-input" type="hidden" name="stationId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false);?>
   
   <?php include 'common/menu.php';?>
     
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setStationInfo('', '', '', 0); showModal('config-station-modal');">New Station</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-station-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Station name</label>
      <input id="station-name-input" type="text" form="config-form" name="name"></input>
      <label>Station label</label>
      <input id="station-label-input" type="text" form="config-form" name="label"></input>
      <label>Station description</label>
      <input id="station-description-input" type="text" form="config-form" name="description"></input>
      <label>Cycle time</label>
      <input id="station-cycle-time-input" type="number" form="config-form" name="cycleTime"></input>
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete station?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js"></script>
<script src="script/modal.js"></script>
<script src="script/stationConfig.js"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);

   updateStationInfo();

   setInterval(function(){
      if (!isModalVisible())
      {
         updateStationInfo();
      }
   }, 5000);
</script>

</body>

</html>