<?php

require_once 'common/stationInfo.php';
require_once 'common/database.php';

function renderTable()
{
   echo 
<<<HEREDOC
   <table>
      <tr>
         <th>Workstation</th>
         <th>Label</th>
         <th>Description</th>
         <th>Last Update</th>
         <th></th>
         <th></th>
      </tr>
HEREDOC;
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getStations();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $stationInfo = StationInfo::load($row["stationId"]);

         echo 
<<<HEREDOC
         <tr>
            <td>$stationInfo->name</td>
            <td>$stationInfo->label</td>
            <td>$stationInfo->description</td>
            <td>$stationInfo->updateTime</td>
            <td><button class="config-button" onclick="showModal('config-station-modal');">Configure</button></div></td>
            <td><button class="config-button" onclick="showModal('confirm-delete-modal');">Delete</button></div></td>
         </tr>
HEREDOC;
      }
   }
   
   echo "</table>";
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

<div class="flex-vertical" style="align-items: flex-start;">

   <?php include 'common/header.php';?>
   
   <?php include 'common/menu.php';?>
   
   <div class="main vertical">
      <?php renderTable();?>
   </div>
     
</div>

<!--  Modal dialogs -->

<div id="config-station-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <label>Station name</label>
      <input type="text" name="name"></input>
      <label>Station label</label>
      <input type="text" name="label"></input>
      <label>Station description</label>
      <input type="text" name="description"></input>
      <div class="flex-horizontal">
         <button class="config-button">Save</button>
      </div>
   </div>
</div>

<div id="confirm-delete-modal" class="modal">
   <div class="flex-vertical modal-content" style="width:300px;">
      <div id="close" class="close">&times;</div>
      <p>Really delete station?</p>
      <button class="config-button">Confirm</button>
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
</script>

</body>

</html>