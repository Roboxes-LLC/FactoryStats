<?php
require_once 'database.php';
require_once 'time.php';

class BreakInfo
{
   const UNKNOWN_BREAK_ID = 0;
   
   public $breakId = BreakInfo::UNKNOWN_BREAK_ID;
   public $startTime;
   public $endTime;
   
   public static function load($breakId)
   {
      $breakInfo = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getBreak($breakId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $breakInfo = new BreakInfo();
            
            $breakInfo->breakId = intval($row['breakId']);
            $breakInfo->startTime = Time::fromMySqlDate($row['startTime'], "Y-m-d H:i:s");
            if ($row['endTime'] != null)
            {
               $breakInfo->endTime = Time::fromMySqlDate($row['endTime'], "Y-m-d H:i:s");
            }
         }
      }
      
      return ($breakInfo);
   }
   
   public static function getCurrentBreak($stationId)
   {
      $breakInfo = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $breakId = $database->getCurrentBreakId($stationId);
         
         if ($breakId != BreakInfo::UNKNOWN_BREAK_ID)
         {
            $breakInfo = BreakInfo::load($breakId);
         }
      }
      
      return ($breakInfo);
   }
   
   public static function isOnBreak($stationId)
   {
      $isOnBreak = false;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $isOnBreak = $database->isOnBreak($stationId);
      }
      
      return ($isOnBreak);
   }
   
   public static function startBreak($stationId)
   {
      $breakInfo = null;
      
      if (BreakInfo::isOnBreak($stationId) == false)
      {
         $database = new FlexscreenDatabase();
         
         $database->connect();
         
         if ($database->isConnected())
         {
            $database->startBreak($stationId, Time::now("Y-m-d H:i:s"));
            
            $breakInfo = BreakInfo::getCurrentBreak($stationId);
         }
      }
      
      return ($breakInfo);
   }
   
   public static function endBreak($stationId)
   {
      $breakInfo = BreakInfo::getCurrentBreak($stationId);
      
      if ($breakInfo)
      {
         $duration = 0;
         
         $database = new FlexscreenDatabase();
         
         $database->connect();
         
         if ($database->isConnected())
         {
            $database->endBreak($stationId, Time::now("Y-m-d H:i:s"));
            
            $breakInfo = BreakInfo::getCurrentBreak($breakInfo->breakId);
         }
      }
      
      return ($breakInfo);
   }
   
   public function getDuration()
   {
      return (0);
   }
}

Time::init();

if (isset($_GET["breakId"]))
{
   $breakId = $_GET["breakId"];
   $breakInfo = BreakInfo::load($breakId);
 
   if ($breakInfo)
   {
      echo "breakId: " .    $breakInfo->breakId .    "<br/>";
      echo "startTime: " .  $breakInfo->startTime .  "<br/>";
      echo "endTime: " .    $breakInfo->endTime .    "<br/>";
   }
   else
   {
      echo "No break info found.";
   }
}
?>