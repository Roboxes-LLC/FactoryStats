<?php 
require_once 'root.php';

global $ROOT;
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
   <div id="menu-item-workstation-summary" class="menu-item"><a href="<?php echo $ROOT?>/workstationSummary.php">Workstation Summary</a></div>
   <div id="menu-item-production-history" class="menu-item"><a href="<?php echo $ROOT?>/productionHistory.php">Production History</a></div>
   <div>
      <div id="menu-item-configuration" class="menu-item" ontouch="toggleSubmenu('config-submenu')">Config</div>
      <div id="config-submenu" class="flex-vertical submenu-div">
         <div id="menu-item-station-config" class="menu-item"><a href="<?php echo $ROOT?>/stationConfig.php">Workstation</a></div>
         <div id="menu-item-button-config" class="menu-item"><a href="<?php echo $ROOT?>/buttonConfig.php">Hardware Buttons</a></div>
         <div id="menu-item-display-config" class="menu-item"><a href="<?php echo $ROOT?>/displayConfig.php">Displays</a></div>
         <div id="menu-item-station-config" class="menu-item"><a href="<?php echo $ROOT?>/breakDescriptionConfig.php">Breaks</a></div>
      </div>
   </div>
   <div class="menu-item-spacer"></div>
</div>