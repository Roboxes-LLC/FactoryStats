<?php
require_once 'database.php';
require_once 'time.php';

class StationInfo
{
   const UNKNOWN_STATION_ID = 0;
   
   const MIN_STATION_ID = 1;   
   
   const MAX_STATION_ID = 64;   
   
   public $stationId = StationInfo::UNKNOWN_STATION_ID;
   public $name;
   public $label;
   public $description;
   public $cycleTime;
   public $updateTime;

   public static function load($stationId)
   {
      $stationInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStation($stationId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $stationInfo= new StationInfo();
            
            $stationInfo->stationId = intval($row['stationId']);
            $stationInfo->name = $row['name'];
            $stationInfo->label = $row['label'];
            $stationInfo->description = $row['description'];
            $stationInfo->cycleTime = intval($row['cycleTime']);
            $stationInfo->updateTime = Time::fromMySqlDate($row['updateTime'], "Y-m-d H:i:s");
         }
      }
      
      return ($stationInfo);
   }
   
   public function getLabel()
   {
      $label = ($this->label != "") ? $this->label : $this->name;

      return ($label);
   }
   
   public static function getStationOptions($selectedStationId)
   {
      $html = "<option style=\"display:none\">";
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStations();
         
         while ($result && ($row = $result->fetch_assoc()))
         {
            $stationId = intval($row["stationId"]);
            $stationName = $row["name"];
            
            $selected = ($stationId == $selectedStationId) ? "selected" : "";
            
            $html .= "<option value=\"$stationId\" $selected>$stationName</option>";
         }
      }
      
      return ($html);
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
       echo "label: " .       $stationInfo->label .       "<br/>";
       echo "description: " . $stationInfo->description . "<br/>";
       echo "cycleTime: " .   $stationInfo->cycleTime .   "<br/>";
       echo "updateTime: "  . $stationInfo->updateTime .  "<br/>";
    }
    else
    {
       echo "No station info found.";
    }
 }
 */
?>