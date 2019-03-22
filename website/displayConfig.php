<?php

require_once 'common/displayInfo.php';
require_once 'common/database.php';
require_once 'common/stationInfo.php';

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
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getDisplays();
      
      while ($result && $row = $result->fetch_assoc())
      {
         $displayInfo = DisplayInfo::load($row["displayId"]);
         
         $stationName = "";
         if ($displayInfo->stationId != StationInfo::UNKNOWN_STATION_ID)
         {
            $stationInfo = StationInfo::load($displayInfo->stationId);
            
            if ($stationInfo)
            {
               $stationName = $stationInfo->name;
            }
         }
         
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
            <td>$status <div class="$ledClass"></div></td>
            <td><button>Configure</button></div></td>
            <td><button>Delete</button></div></td>
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
   
   <title>Display Config</title>
   
   <!--  Material Design Lite -->
   <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
   
   <link rel="stylesheet" type="text/css" href="css/flex.css"/>
   <link rel="stylesheet" type="text/css" href="css/flexscreen.css"/>
   
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
   
   <div class="flex-horizontal main">
      <?php renderTable();?>
   </div>
     
</div>

<script src="script/flexscreen.js"></script>
<script>
   setMenuSelection(MenuItem.CONFIGURATION);
</script>

</body>

</html>