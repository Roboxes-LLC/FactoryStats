<?php
require_once 'database.php';
require_once 'time.php';

class ShiftInfo
{
   const UNKNOWN_SHIFT_ID = 0;
   
   const DEFAULT_SHIFT_ID = 1;
   
   public $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
   public $shiftName;
   public $startTime;
   public $endTime;
   
   public static function load($shiftId)
   {
      $shiftInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShift($shiftId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $shiftInfo= new ShiftInfo();
            
            $shiftInfo->shiftId = intval($row['shiftId']);
            $shiftInfo->shiftName = $row['shiftName'];
            $shiftInfo->startTime = $row['startTime'];
            $shiftInfo->endTime = $row['endTime'];
         }
      }
      
      return ($shiftInfo);
   }
   
   public static function getShift($time)
   {
      $shiftInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShifts();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $tempShiftInfo = ShiftInfo::load($shifts["shiftId"]);
            
            if (Time::between($time, $tempShiftInfo->startTime, $tempShiftInfo->endTime))
            {
               $shiftInfo = $tempShiftInfo;
               break;
            }
         }
      }
      
      return ($shiftInfo);
   }
}

/*
if (isset($_GET["shiftId"]))
{
   $shiftId = $_GET["shiftId"];
   $shiftInfo = ShiftInfo::load($shiftId);
   
   if ($shiftInfo)
   {
      $startTime = new DateTime($shiftInfo->startTime);
      $endTime = new DateTime($shiftInfo->endTime);
      
      echo "shiftId: " .   $shiftInfo->shiftId .           "<br/>";
      echo "shiftName: " . $shiftInfo->shiftName .         "<br/>";
      echo "startTime: " . $shiftInfo->startTime . " = " . $startTime->format("h:i:s A") . "<br/>";
      echo "endTime: " .   $shiftInfo->endTime . " = " . $endTime->format("h:i:s A") .   "<br/>";
   }
   else
   {
      echo "No shift info found.";
   }
}
*/
 
?>