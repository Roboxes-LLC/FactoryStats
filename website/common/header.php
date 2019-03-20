<?php 
require_once 'root.php';

global $ROOT;

function getUsername()
{
   return ("User1");
}

?>

<div class="flex-horizontal header">
   <div><img src="<?php echo $ROOT?>/images/flexscreen-logo-hompage-2.png" width="350px"></div>
   
   <div class="flex-horizontal">
      <i class="material-icons" style="margin-right:5px; color: #ffffff; font-size: 35px;">person</i>
      <div class="nav-username"><?php echo getUsername()?> &nbsp | &nbsp</div>
      <a class="nav-link" href="<?php echo $ROOT?>/splash.php">Logout</a>
   </div>
</div>