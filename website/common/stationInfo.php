<?php
require_once 'database.php';
require_once 'time.php';

class StationInfo
{
   const UNKNOWN_STATION_ID = 0;
   
   public $stationId = StationInfo::UNKNOWN_STATION_ID;
   public $name;
   public $description;
   public $updateTime;

   public static function load($stationId)
   {
      $stationInfo = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getStation($stationId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $stationInfo= new StationInfo();
            
            $stationInfo->stationId = intval($row['stationId']);
            $stationInfo->name = $row['name'];
            $stationInfo->description = $row['description'];
            $stationInfo->updateTime = Time::fromMySqlDate($row['updateTime'], "Y-m-d H:i:s");
         }
      }
      
      return ($stationInfo);
   }
}

/*
 if (isset($_GET["stationId"]))
 {
    $stationId = $_GET["stationId"];
    $stationInfo = StationInfo::load($stationId);
    
    if ($stationInfo)
    {
       echo "stationId: " .   $stationInfo->stationId .   "<br/>";
       echo "name: " .        $stationInfo->name .        "<br/>";
       echo "description: " . $stationInfo->description . "<br/>";
       echo "updateTime: "  . $stationInfo->updateTime .  "<br/>";
    }
    else
    {
       echo "No station info found.";
    }
 }
 */
?>