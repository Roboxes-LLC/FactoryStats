<?php

require_once 'common/displayInfo.php';
require_once 'common/root.php';
require_once 'common/stationInfo.php';

Time::init();

session_start();

function getStationId()
{
   $stationId = "";
   
   if (isset($_GET["stationId"]))
   {
      $stationId = $_GET["stationId"];
   }
   else if (isset($_GET["displayId"]))
   {
      $displayId = $_GET["displayId"];
      
      $displayInfo = DisplayInfo::load($displayId);
      if ($displayInfo)
      {
         $stationId = $displayInfo->stationId;
      }
   }
   else if (isset($_GET["macAddress"]))
   {
      $macAddress = $_GET["macAddress"];
      
      $displayId = DisplayInfo::getDisplayIdFromMac($macAddress);
      
      $displayInfo = DisplayInfo::load($displayId);
      
      if ($displayInfo)
      {
         $stationId = $displayInfo->stationId;
      }
   }
   
   return ($stationId);
}

$stationId = getStationId();

// Default to workstation summary.
$url = "$ROOT/workstationSummary.php?kiosk=true";

// Defer to any specified station ID.
if ($stationId != StationInfo::UNKNOWN_STATION_ID)
{
   $url = "$ROOT/workstation.php?stationId=$stationId&kiosk=true";
}

// Redirect.
header("Location: $url");
   
?>