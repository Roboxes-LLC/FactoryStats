<?php

require_once '../common/database.php';
require_once '../common/registryEntry.php';

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
   
   $database = new FlexscreenDatabase();
   
   $database->connect();
   
   if ($database->isConnected())
   {
      $result = $database->getRegistryEntries("");
      
      while ($result && $row = $result->fetch_assoc())
      {
         $registryEntry = RegistryEntry::load($row["chipId"]);
         
         $isOnline = $registryEntry->isOnline();
         $status = $isOnline ? "Online" : "Offline";
         $ledClass = $isOnline ? "led-green" : "led-red";
         
         echo 
<<<HEREDOC
         <tr>
            <td>$registryEntry->chipId</td>
            <td>$registryEntry->macAddress</td>
            <td>$registryEntry->ipAddress</td>
            <td>$registryEntry->roboxName</td>
            <td>$registryEntry->lastContact</td>
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
   
   <title>Hardware Button Status</title>
   
   <link rel="stylesheet" type="text/css" href="../common/flex.css"/>
   <link rel="stylesheet" type="text/css" href="../flexscreen.css"/>
   <link rel="stylesheet" type="text/css" href="hardwareButton.css"/>
   
   <style>
      table, th, td {
         color: white;
         border: 1px solid white;
      }
   </style>
   
</head>

<body onload="update()">

<div class="flex-vertical" style="align-items: flex-start;">

   <div class="flex-horizontal header">
      <div><img src="../images/flexscreen-logo-hompage-2.png" width="350px"></div>
   </div>
   
   <?php renderTable();?>
     
</div>

<script src="hardwareButton.js"></script>
<script>
   // Start a five-second timer to update the count/hourly count div.
   setInterval(function(){update();}, 5000);
</script>

</body>

</html>