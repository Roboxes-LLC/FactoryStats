<?php 
require_once 'root.php';
require_once 'shiftInfo.php';

global $ROOT;

function getUsername()
{
   return ("User1");
}

?>

<div class="flex-horizontal header">
   <div class="flex-horizontal" style="width:33%; justify-content:flex-start; margin-left: 20px;">
      <select id="shift-id-input" name="shiftId" onchange="storeInSession('shiftId', this.value); update();"><?php echo ShiftInfo::getShiftOptions(ShiftInfo::getShiftId(), false); ?></select>
   </div>
   
   <div class="flex-horizontal" style="width:33%; justify-content:center;">
      <img src="<?php echo $ROOT?>/images/flexscreen-logo-hompage-2.png" width="350px">
   </div>
   
   <div class="flex-horizontal" style="width:33%; justify-content:flex-end; margin-right: 20px;">
         <i class="material-icons" style="margin-right:5px; color: #ffffff; font-size: 35px;">person</i>
         <div class="nav-username"><?php echo getUsername()?> &nbsp | &nbsp</div>
         <a class="nav-link" href="<?php echo $ROOT?>/index.php">Logout</a>
   </div>
</div>