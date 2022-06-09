<?php 
require_once 'root.php';
require_once 'language.php';

function getMenuItem($id, $permissionId, $url, $title)
{
   global $ROOT;
   
   $html = "";
   
   if (Authentication::checkPermissions($permissionId))
   {
      $html = 
<<<HEREDOC
      <div id="$id" class="menu-item"><a href="$ROOT/$url">$title</a></div>
HEREDOC;
   }
   
   return ($html);
}
?>

<script>
function toggleSubmenu(submenu)
{
   var element = document.getElementById(submenu);

   if (element.style.display === "block")
   {
      element.style.display = "none";
   }
   else
   {
      element.style.display = "flex";
   }
}
</script>

<div class="flex-horizontal menu-div">
   <div class="menu-item-spacer"></div>
   <?php 
      echo getMenuItem("menu-item-workstation-summary", Permission::WORKSTATION_SUMMARY, "workstationSummary.php", "Workstation Summary");
      echo getMenuItem("menu-item-production-history", Permission::PRODUCTION_HISTORY, "productionHistory.php", "Production History"); 
   ?>
   <div>
      <div id="menu-item-configuration" class="menu-item" ontouch="toggleSubmenu('config-submenu')">Config</div>
      <div id="config-submenu" class="flex-vertical submenu-div">
         <?php
            echo getMenuItem("menu-item-user-config", Permission::USER_CONFIG, "userConfig.php", LANG("_USERS"));
            echo getMenuItem("menu-item-shift-config", Permission::CUSTOMER_CONFIG, "shiftConfig.php", LANG("_SHIFTS"));
            echo getMenuItem("menu-item-station-config", Permission::STATION_CONFIG, "stationConfig.php", LANG("_WORKSTATIONS"));
            echo getMenuItem("menu-item-station-group-config", Permission::STATION_CONFIG, "stationGroupConfig.php", LANG("_STATION_GROUPS"));
            echo getMenuItem("menu-item-button-config", Permission::BUTTON_CONFIG, "buttonConfig.php", LANG("_HARDWARE_BUTTONS"));
            echo getMenuItem("menu-item-sensor-config", Permission::SENSOR_CONFIG, "sensorConfig.php", LANG("_SENSORS"));
            echo getMenuItem("menu-item-display-config", Permission::DISPLAY_CONFIG, "displayConfig.php", LANG("_DISPLAYS"));
            echo getMenuItem("menu-item-presentation-config", Permission::PRESENTATION_CONFIG, "presentationConfig.php", LANG("_PRESENTATIONS"));
            echo getMenuItem("menu-item-break-config", Permission::BREAK_CONFIG, "breakDescriptionConfig.php", LANG("_BREAKS"));
         ?>
      </div>
   </div>
   <div class="menu-item-spacer"></div>
</div>