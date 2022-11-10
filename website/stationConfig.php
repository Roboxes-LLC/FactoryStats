<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationInfo.php';
require_once 'common/version.php';

session_start();

if (!(Authentication::isAuthenticated() &&
      Authentication::checkPermissions(Permission::STATION_CONFIG)))
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
         <th>Workstation</th>
         <th>Label</th>
         <th>Object Name</th>
         <th>Cycle Time (s)</th>
         <th>Hidden</th>
         <th>Last Update</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations();
      
      foreach ($result as $row)
      {
         $stationId = $row["stationId"];
         
         $stationInfo = StationInfo::load($stationId);
         
         $hideOnSummary = $stationInfo->hideOnSummary ? "true" : "false";
         $hideOnSummarySymbol = $stationInfo->hideOnSummary ? "&#x2714;" : "";  // Heavy check

         echo 
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$stationInfo->label</td>
            <td>"$stationInfo->objectName"</td>
            <td>$stationInfo->cycleTime</td>
            <td>$hideOnSummarySymbol</td>
            <td id="station-$stationId-update-time"></td>
            <td><button class="config-button" onclick="setStationId($stationInfo->stationId); setStationInfo('$stationInfo->name', '$stationInfo->label', '$stationInfo->objectName', $stationInfo->cycleTime, $hideOnSummary); showModal('config-station-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setStationId($stationInfo->stationId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function addStation($name, $label, $objectName, $cycleTime, $hideOnSummary)
{
   $stationInfo = new StationInfo();
   $stationInfo->name = $name;
   $stationInfo->label = $label;
   $stationInfo->objectName = $objectName;
   $stationInfo->cycleTime = is_numeric($cycleTime) ? intval($cycleTime) : 0;
   $stationInfo->hideOnSummary = $hideOnSummary;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->addStation($stationInfo);
   }
}

function deleteStation($stationId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteStation($stationId);
   }
}

function updateStation($stationId, $name, $label, $objectName, $cycleTime, $hideOnSummary)
{
   $stationInfo = StationInfo::load($stationId);
   $stationInfo->stationId = $stationId;
   $stationInfo->name = $name;
   $stationInfo->label = $label;
   $stationInfo->objectName = $objectName;
   $stationInfo->cycleTime = is_numeric($cycleTime) ? intval($cycleTime) : 0;
   $stationInfo->hideOnSummary = $hideOnSummary;
   
   $database = FactoryStatsDatabase::getInstance();
   
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
         updateStation($params->getInt("stationId"), 
                       $params->get("name"),
                       $params->get("label"),
                       $params->get("objectName"),
                       $params->getInt("cycleTime"),
                       $params->getBool("hideOnSummary"));
      }
      else
      {
         addStation($params->get("name"),
                    $params->get("label"),
                    $params->get("objectName"),
                    $params->getInt("cycleTime"),
                    $params->getBool("hideOnSummary"));
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
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
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

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
     
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <button class="config-button" onclick="setStationInfo('', '', '', 0, false); showModal('config-station-modal');">New Station</button>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-station-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      
      <div class="flex-vertical input-block">
         <label>Station name</label>
         <input id="station-name-input" type="text" form="config-form" name="name"></input>
      </div>

      <div class="flex-vertical input-block">
         <label>Station label</label>
         <input id="station-label-input" type="text" form="config-form" name="label"></input>
      </div>
      
      <div class="flex-vertical input-block">      
         <label>Object Name</label>
         <input id="object-name-input" type="text" form="config-form" name="objectName"></input>
      </div>
      
      <div class="flex-vertical input-block">
         <label>Cycle time</label>
         <input id="station-cycle-time-input" type="number" form="config-form" name="cycleTime"></input>
      </div>
      
      <div class="flex-horizontal input-block">
         <label>Hide on summary</label>
         <input id="hide-on-summary-input" type="checkbox" form="config-form" name="hideOnSummary">
      </div>
      
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

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/stationConfig.js<?php echo versionQuery();?>"></script>
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