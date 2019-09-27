<?php 

require_once 'kiosk.php';
require_once 'root.php';
require_once 'shiftInfo.php';

class Header
{
   public static function getHtml($includeShiftIdInput)
   {
      global $ROOT;
      
      $shiftIdInput = "";
      if ($includeShiftIdInput)
      {
         $shiftOptions = ShiftInfo::getShiftOptions(ShiftInfo::getShiftId(), false);
         $shiftIdInput = 
<<<HEREDOC
         <select id="shift-id-input" name="shiftId" onchange="storeInSession('shiftId', this.value); update();">$shiftOptions</select>
HEREDOC;
      }
      
      $username = Header::getUsername();
      
      $html = 
<<<HEREDOC
      <div class="flex-horizontal header">
         <div class="flex-horizontal" style="width:33%; justify-content:flex-start; margin-left: 20px;">
            $shiftIdInput
         </div>

         <div class="flex-horizontal" style="width:33%; justify-content:center;">
            <img src="$ROOT/images/flexscreen-logo-hompage-2.png" width="350px">
         </div>

         <div class="flex-horizontal" style="width:33%; justify-content:flex-end; margin-right: 20px;">
HEREDOC;
      
      if (!isKioskMode())
      {
         $html .=
<<<HEREDOC
            <i class="material-icons" style="margin-right:5px; color: #ffffff; font-size: 35px;">person</i>
            <div class="nav-username">$username &nbsp | &nbsp</div>
            <a class="nav-link" href="$ROOT/index.php">Logout</a>
HEREDOC;
      }
         
      $html .= 
<<<HEREDOC
         </div>
      </div>
HEREDOC;
      
      return ($html);
   }
   
   public static function render($includeShiftIdInput)
   {
      echo (Header::getHtml($includeShiftIdInput));
   }

   static private function getUsername()
   {
      return ("User1");
   }
}

?>
