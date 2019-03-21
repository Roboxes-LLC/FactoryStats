<?php 
require_once 'root.php';

global $ROOT;
?>

<div class="flex-horizontal menu-div">
   <div class="menu-item-spacer"></div>
   <div id="menu-item-workstation-summary" class="menu-item"><a href="<?php echo $ROOT?>/workstationSummary.php">Workstation Summary</a></div>
   <div id="menu-item-production-history" class="menu-item"><a href="<?php echo $ROOT?>/productionHistory.php">Production History</a></div>
   <div id="menu-item-configuration"class="menu-item"><a href="<?php echo $ROOT?>/buttonConfig.php">Configuration</a></div>
   <div class="menu-item-spacer"></div>
</div>