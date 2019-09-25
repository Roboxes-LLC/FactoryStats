<?php
require_once 'database.php';
require_once 'time.php';

class Settings
{
   public $shiftStart;
   public $shiftEnd;

   public static function load()
   {
      $settings = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getSettings();
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $settings = new Settings();
            
            $settings->shiftStart = $row['shiftStart'];
            $settings->shiftEnd = $row['shiftEnd'];
         }
      }
      
      return ($settings);
   }
   
   public static function isShiftActive($time)
   {
      $isShiftActive = true;

      $settings = Settings::load();

      if ($settings)
      {
         $isShiftActive = Time::between($time, $settings->shiftStart, $settings->shiftEnd);
      }
      
      return ($isShiftActive);
   }
}

/*
$settings = Settings::load();

if ($settings)
{
   echo "now: " . Time::now("H:i:s") . "<br/>";
   echo "shiftStart: " . $settings->shiftStart . "<br/>";
   echo "shiftEnd: " .   $settings->shiftEnd .   "<br/>";
   echo "isShiftActive(\"04:59:59\"): " . (Settings::isShiftActive("04:59:59") ? "true" : "false") . "<br/>";
   echo "isShiftActive(\"05:00:00\"): " . (Settings::isShiftActive("05:00:00") ? "true" : "false") . "<br/>";
   echo "isShiftActive(\"18:00:00\"): " . (Settings::isShiftActive("18:00:00") ? "true" : "false") . "<br/>";
   echo "isShiftActive(\"18:00:01\"): " . (Settings::isShiftActive("18:00:01") ? "true" : "false") . "<br/>";
   echo "isShiftActive(\"now\"): " . (Settings::isShiftActive(Time::now("H:i:s")) ? "true" : "false") . "<br/>";
}
*/
?>