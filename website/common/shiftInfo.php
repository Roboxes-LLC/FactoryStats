<?php

require_once 'database.php';
require_once 'params.php';
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
      $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShifts();
         
         $now = new DateTime(Time::now("Y-m-d H:i:s"));
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            // Calculate today's shift start time.
            $startDateTime = new DateTime($row["startTime"]);
            $startDateTime->setDate($now->format("Y"), $now->format("m"), $now->format("d"));
            
            // Calculate today's shift end time.
            // Note: Extra logic for if the shift extends into the next day.
            $endDateTime = new DateTime($row["endTime"]);
            $day = intval($now->format("d"));
            $startHour = intval($startDateTime->format("H"));
            $endHour = intval($endDateTime->format("H"));
            if ($endHour <= $startHour)
            {
               $day++;
            }
            $endDateTime->setDate($now->format("Y"), $now->format("m"), $day);

            if (Time::between($now->format("Y-m-d H:i:s"), $startDateTime->format("Y-m-d H:i:s"), $endDateTime->format("Y-m-d H:i:s")))
            {
               $shiftId = intval($row["shiftId"]);
               break;
            }
         }
      }
      
      return ($shiftId);
   }
   
   public static function getShiftOptions($selectedShiftId, $includeAllShifts)
   {
      $html = "";
      
      $selected = ($selectedShiftId == ShiftInfo::UNKNOWN_SHIFT_ID) ? "selected" : "";
      if ($includeAllShifts)
      {
         $html .= "<option value=\"" . ShiftInfo::UNKNOWN_SHIFT_ID . "\" $selected>All shifts</option>";
      }
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShifts();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $shiftId = $row["shiftId"];
            $shiftName = $row["shiftName"];
            $selected = ($shiftId == $selectedShiftId) ? "selected" : "";
            
            $html .= "<option value=\"$shiftId\" $selected>$shiftName</option>";
         }
      }
      
      return ($html);
   }
   
   static function getShiftId()
   {
      $shiftId = ShiftInfo::DEFAULT_SHIFT_ID;
      
      $params = Params::parse();
      
      $currentShiftId = ShiftInfo::getShift(Time::now("H:i:s"));
      
      if ($params->keyExists("shiftId"))
      {
         $shiftId = $params->getInt("shiftId");
      }
      else if ($currentShiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftId = $currentShiftId;
      }
      
      return ($shiftId);
   }
   
   public function shiftSpansDays()
   {
      return ($this->startTime >= $this->endTime);
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