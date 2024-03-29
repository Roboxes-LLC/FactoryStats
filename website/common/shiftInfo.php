<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/params.php';
require_once ROOT.'/common/time.php';

class ShiftInfo
{
   const UNKNOWN_SHIFT_ID = 0;
   
   const DEFAULT_SHIFT_ID = 1;
   
   public $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
   public $shiftName;
   public $startTime;
   public $endTime;
   
   public function __construct()
   {
      $this->shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
      $this->shiftName = "";
      $this->startTime = null;
      $this->endTime = null;
   }
   
   public static function load($shiftId)
   {
      $shiftInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShift($shiftId);
         
         if ($result && ($row = $result[0]))
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
   
   public static function getDefaultShift()
   {
      static $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
      
      if ($shiftId == ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $result = FactoryStatsDatabase::getInstance()->getShifts();
         
         if ($result && ($row = $result[0]))
         {
            $shiftId = intval($row["shiftId"]);
         }
      }
      
      return ($shiftId);
   }
   
   public static function getShift($time)
   {
      $shiftId = ShiftInfo::UNKNOWN_SHIFT_ID;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShifts();

         // Get the current time.
         // Note: Zero out date for easier comparison.         
         $now = new DateTime(Time::now("Y-m-d H:i:s"));
         $now->setDate(0, 0, 0);
         
         foreach ($result as $row)
         {
            $shiftInfo = ShiftInfo::load($row["shiftId"]);
            
            if ($shiftInfo)
            {
               // Get shift start/end times.
               // Note: Zero out date for easier comparison.
               $startDateTime = new DateTime($shiftInfo->startTime);
               $startDateTime->setDate(0, 0, 0);
               $endDateTime = new DateTime($shiftInfo->endTime);
               $endDateTime->setDate(0, 0, 0);
               
               if ($shiftInfo->shiftSpansDays())
               {
                  // Extra logic for shifts that span days.
                  $startOfDay = new DateTime(Time::now("Y-m-d H:i:s"));
                  $startOfDay->setDate(0, 0, 0);
                  $startOfDay->setTime(0, 0, 0, 0);  
                  $endOfDay = new DateTime(Time::now("Y-m-d H:i:s"));
                  $endOfDay->setDate(0, 0, 0);
                  $endOfDay->setTime(23, 59, 59, 0);
                  
                  if ((($now >= $startDateTime) &&
                       ($now <= $endOfDay)) ||
                      (($now >= $startOfDay) &&
                       ($now <= $endDateTime)))                    
                  {
                     $shiftId = $shiftInfo->shiftId;
                     break;
                  }
               }
               else
               {
                  if (($now >= $startDateTime) &&
                      ($now <= $endDateTime))
                  {
                     $shiftId = $shiftInfo->shiftId;
                     break;
                  }
               }
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
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getShifts();
         
         foreach ($result as $row)
         {
            $shiftId = $row["shiftId"];
            $shiftInfo = ShiftInfo::load($shiftId);
            $shiftName = $row["shiftName"];
            $selected = ($shiftId == $selectedShiftId) ? "selected" : "";
            $shiftSpansDays = $shiftInfo->shiftSpansDays() ? "1" : "0";
            
            $html .= "<option value=\"$shiftId\" shiftSpansDays=\"$shiftSpansDays\" $selected>$shiftName</option>";
         }
      }
      
      return ($html);
   }
   
   static function getShiftId()
   {
      $shiftId = ShiftInfo::getDefaultShift();
      
      $params = Params::parse();
      
      $currentShiftId = ShiftInfo::getShift(Time::now("H:i:s"));
      
      if (($params->keyExists("shiftId")) &&
          ($params->getInt("shiftId") != ShiftInfo::UNKNOWN_SHIFT_ID))
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
   
   // This function returns an object containing the start/end times that can be used for database searches on the specified shift, over the specified date range.
   // For most shifts, this will be 12:00:00 am on the start date, and 11:59:59 pm on the end date.
   // However, for shifts that span two days this will be 12:00:00 pm on the start date, and 12:00:00 pm on the day *after* the end date.
   public function getEvaluationTimes($startDate, $endDate)
   {
      $evaluationTimes = new stdClass();
       
      if ($this->shiftSpansDays())
      {
         // If the specified shift is configured to span two days (ex. 11pm to 1am) then gather
         // data from the middle of the first day to the middle of the last day.
         $evaluationTimes->startDateTime = Time::midDay($startDate);
         $evaluationTimes->endDateTime = Time::midDay(Time::incrementDay($endDate));  // TODO: This will pick up 12pm entries.
      }
      else
      {
         $evaluationTimes->startDateTime = Time::startOfDay($startDate);
         $evaluationTimes->endDateTime = Time::endOfDay($endDate);
      }
       
      return ($evaluationTimes);
   }
   
   // This function returns an object containing the exact shift start/end times for the specified date.
   public function getShiftTimes($startDate)
   {
      $shiftTimes = new stdClass();
      
      // Start time.
      $shiftStartDateTime = new DateTime($startDate);
      $shiftStartTime = new DateTime($this->startTime);
      $shiftStartDateTime->setTime(intval($shiftStartTime->format('G')), intval($shiftStartTime->format('i')), 0);
      $shiftTimes->startDateTime =  $shiftStartDateTime->format("Y-m-d H:i:s");
      
      // End time.
      $shiftEndDateTime = new DateTime($startDate);
      
      if ($this->shiftSpansDays())
      {
         $shiftEndDateTime = new DateTime(Time::incrementDay($startDate));
      }
      
      $shiftEndTime = new DateTime($this->endTime);
      $shiftEndDateTime->setTime(intval($shiftEndTime->format('G')), intval($shiftEndTime->format('i')), 0);
      $shiftTimes->endDateTime = $shiftEndDateTime->format("Y-m-d H:i:s");
      
      return ($shiftTimes);
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