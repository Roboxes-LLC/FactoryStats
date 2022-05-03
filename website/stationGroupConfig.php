<?php

require_once 'common/database.php';
require_once 'common/header.php';
require_once 'common/params.php';
require_once 'common/stationGroup.php';
require_once 'common/version.php';

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
         <th>Group Name</th>
         <th>Workstation Count</th>
         <th>Workstations</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStationGroups();
      
      foreach ($result as $row)
      {
         $stationGroup = StationGroup::load(intval($row["groupId"]));
         
         $count = count($stationGroup->stationIds);
         
         $workstations = "";
         $requireComma = false;
         foreach ($stationGroup->stationIds as $stationId)
         {
            $stationInfo = StationInfo::load($stationId);
            if ($stationInfo)
            {
               $workstations .= $requireComma ? ", " : "";
               $workstations .= $stationInfo->label;
               $requireComma = true;
            }
            
            if (strlen($workstations) > 16)
            {
               $workstations .= " ...";
               break;
            }
         }
         
         $stationIdArray = getStationIdArray($stationGroup->groupId);
         
         echo 
<<<HEREDOC
         <tr>
            <td>$stationGroup->name</td>
            <td>$count</td>
            <td>$workstations</td>
            <td><button class="config-button" onclick="setStationGroup($stationGroup->groupId, '$stationGroup->name', $stationIdArray); showModal('config-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="setGroupId($stationGroup->groupId); showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
}

function getWorkstationInputs()
{
   $html = "<div style=\"max-height:300px; overflow-y: scroll;\">";
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $result = $database->getStations();
      
      foreach ($result as $row)
      {
         $stationInfo = StationInfo::load(intval($row["stationId"]));
         if ($stationInfo)
         {           
            $name = "station_" . $stationInfo->stationId;
            $id = "station-input-" . $stationInfo->stationId;
            $html .= "<div class=\"flex-horizontal\"><input id=\"$id\"  class=\"stationCheckbox\" type=\"checkbox\" form=\"config-form\" name=\"$name\">$stationInfo->name</div>";
         }
      }      
   }
   
   $html .= "</div>";
   
   return ($html);
}

function getStationIdArray($groupId)
{
   $stationIds = array();
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $stationIds = array();
      
      $result = $database->getStationsForGroup($groupId);
      
      foreach ($result as $row)
      {
         $stationIds[] = intval($row["stationId"]);
      }
   }
   
   $html = "[" . implode(", ", $stationIds) . "]";
      
   return ($html);
}

function addStationGroup($name, $stationIds)
{
   $stationGroup = new StationGroup();
   
   $stationGroup->name = $name;
   $stationGroup->stationIds = $stationIds;
   
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      if ($database->newStationGroup($stationGroup))
      {
         $groupId = $database->lastInsertId();
      
         foreach ($stationIds as $stationId)
         {
            $database->addStationToGroup($stationId, $groupId);
         }
      }
   }
}

function deleteStationGroup($groupId)
{
   $database = FactoryStatsDatabase::getInstance();
   
   if ($database && $database->isConnected())
   {
      $database->deleteStationGroup($groupId);
   }
}

function updateStationGroup($groupId, $name, $stationIds)
{
   $stationGroup = StationGroup::load($groupId);
   
   if ($stationGroup)
   {
      $stationGroup->name = $name;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $database->updateStationGroup($stationGroup);
         
         //
         // Add/remove station ids.
         //
         
         $result = $database->getStations();
         
         foreach ($result as $row)
         {
            $stationId = intval($row["stationId"]);
            
            // Remove unchecked stations.
            if ($database->stationInGroup($stationId, $groupId) &&
                !in_array($stationId, $stationIds))
            {
               $database->removeStationFromGroup($stationId, $groupId);
            }
            
            // Add checked stations.
            if (in_array($stationId, $stationIds) &&
                !$database->stationInGroup($stationId, $groupId))
            {
               $database->addStationToGroup($stationId, $groupId);
            }
         }            
      }
   }
}

// *****************************************************************************
//                              Action handling

Time::init();

$params = Params::parse();

switch ($params->get("action"))
{
   case "delete":
   {
      deleteStationGroup($params->get("groupId"));
      break;      
   }
   
   case "update":
   {
      $database = FactoryStatsDatabase::getInstance();
      
      //
      // Get all checked stations.
      //
      
      $stationIds = [];
      
      $database = FactoryStatsDatabase::getInstance();    
      
      if ($database && $database->isConnected())
      {
         $stationIds = array();
         
         $result = $database->getStations();
         
         foreach ($result as $row)
         {
            $stationId = intval($row["stationId"]);
            
            $name = "station_" . $stationId;
            
            if ($params->keyExists($name))
            {
               $stationIds[] = $stationId;
            }
         }
      }
      
      if ($params->getInt("groupId") == StationGroup::UNKNOWN_GROUP_ID)
      {
         addStationGroup($params->get("name"), $stationIds);
      }
      else
      {
         updateStationGroup(
            $params->getInt("groupId"),
            $params->get("name"),
            $stationIds);
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
   
   <title>Station Group Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css<?php echo versionQuery();?>"/>
   <link rel="stylesheet" type="text/css" href="css/modal.css<?php echo versionQuery();?>"/>
   
</head>

<body>

<form id="config-form" method="post">
   <input id="action-input" type="hidden" name="action">
   <input id="group-id-input" type="hidden" name="groupId">
</form>

<div class="flex-vertical" style="align-items: flex-start;">

   <?php Header::render(false, false, true);?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <div class="flex-vertical" style="align-items: flex-end;">
         <div class="flex-horizontal">
            <button class="config-button" onclick="setStationGroup(0, '', []); showModal('config-modal');">New Group</button>
         </div>
         <br>
         <?php renderTable();?>
      </div>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Group Name</label>
      <input id="name-input" type="text" form="config-form" name="name" value="">
      <label>Workstations</label>
      <?php echo getWorkstationInputs() ?>
      <div class="flex-horizontal">
         <button class="config-button" type="submit" form="config-form" onclick="setAction('update')">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete group?</p>
      <button class="config-button" type="submit" form="config-form" onclick="setAction('delete')">Confirm</button>
   </div>
</div>

<script src="script/flexscreen.js<?php echo versionQuery();?>"></script>
<script src="script/modal.js<?php echo versionQuery();?>"></script>
<script src="script/stationGroupConfig.js<?php echo versionQuery();?>"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);
</script>

</body>

</html>