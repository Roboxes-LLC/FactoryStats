<?php 
require_once 'root.php';

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
            echo getMenuItem("menu-item-user-config", Permission::USER_CONFIG, "userConfig.php", "Users");
            echo getMenuItem("menu-item-shift-config", Permission::CUSTOMER_CONFIG, "shiftConfig.php", "Shifts");
            echo getMenuItem("menu-item-station-config", Permission::STATION_CONFIG, "stationConfig.php", "Workstations");
            echo getMenuItem("menu-item-button-config", Permission::BUTTON_CONFIG, "buttonConfig.php", "Hardware Buttons");
            echo getMenuItem("menu-item-display-config", Permission::DISPLAY_CONFIG, "displayConfig.php", "Displays");
            echo getMenuItem("menu-item-presentation-config", Permission::PRESENTATION_CONFIG, "presentationConfig.php", "Presentations");
            echo getMenuItem("menu-item-break-config", Permission::BREAK_CONFIG, "breakDescriptionConfig.php", "Breaks");
         ?>
      </div>
   </div>
   <div class="menu-item-spacer"></div>
</div>