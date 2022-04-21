<?php

require_once 'root.php';

class DisplayFirmwareInfo
{
   static function getOptions()
   {
      $html = "";
   
      global $DISPLAY_FIRMWARE_DIR;
      
      echo $DISPLAY_FIRMWARE_DIR;
      if ($files = scandir($DISPLAY_FIRMWARE_DIR))
      {
         foreach ($files as $file)
         {
            if (is_file($DISPLAY_FIRMWARE_DIR . "/" . $file))
            {
               $label = pathinfo($file, PATHINFO_FILENAME);
               $html .= "<option value=\"$file\">$label</option>";
            }
         }
      }
      
      return ($html);
   }
}