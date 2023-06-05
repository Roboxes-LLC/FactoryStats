<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';
require_once ROOT.'/common/time.php';

abstract class StationFilter
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const ALL_STATIONS = StationFilter::FIRST;
   const ACTIVE_STATIONS = 2;
   const IDLE_STATIONS = 3;
   const LAST = 4;
   const COUNT = StationFilter::LAST - StationFilter::FIRST;
   
   public static $values = array(StationFilter::ALL_STATIONS, StationFilter::ACTIVE_STATIONS, StationFilter::IDLE_STATIONS);
   
   public static function getLabel($stationFilter)
   {
      $labels = array("---", "All Stations", "Active Stations", "Idle Stations");
      
      return ($labels[$stationFilter]);
   }
   
   public static function getOptions($selectedStationFilter)
   {
      $html = "";
      
      foreach (StationFilter::$values as $stationFilter)
      {
         $selected = ($selectedStationFilter == $stationFilter) ? "selected" : "";
         $label = StationFilter::getLabel($stationFilter);
         
         $html .= "<option value=\"$stationFilter\" $selected>$label</option>";
      }
      
      return ($html);
   }
}

class StationInfo
{
   const UNKNOWN_STATION_ID = 0;
   
   const MIN_STATION_ID = 1;   
   
   const MAX_STATION_ID = 64;   
   
   public $stationId;
   public $name;
   public $label;
   public $objectName;
   public $cycleTime;
   public $hideOnSummary;
   
   public $updateTime;
   
   public function __construct()
   {
      $this->stationId = StationInfo::UNKNOWN_STATION_ID;
      $this->name = "";
      $this->label = "";
      $this->objectName = "";
      $this->cycleTime = 0;
      $this->hideOnSummary = false;
      $this->updateTime = null;
   }

   public static function load($stationId)
   {
      $stationInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStation($stationId);
         
         if ($result && ($row = $result[0]))
         {
            $stationInfo= new StationInfo();
            
            $stationInfo->stationId = intval($row['stationId']);
            $stationInfo->name = $row['name'];
            $stationInfo->label = $row['label'];
            $stationInfo->objectName = $row['objectName'];
            $stationInfo->cycleTime = intval($row['cycleTime']);
            $stationInfo->hideOnSummary = filter_var($row["hideOnSummary"], FILTER_VALIDATE_BOOLEAN);
            if ($row['updateTime'])
            {
               $stationInfo->updateTime = Time::fromMySqlDate($row['updateTime'], "Y-m-d H:i:s");
            }
         }
      }
      
      return ($stationInfo);
   }
   
   public function getLabel()
   {
      $label = ($this->label != "") ? $this->label : $this->name;

      return ($label);
   }
   
   public function getObjectNamePlural()
   {
      $pluralName = "";
      
      if ($this->objectName != "")
      {
         if (StationInfo::str_ends_with($this->objectName, "s") ||
             StationInfo::str_ends_with($this->objectName, "sh") ||
             StationInfo::str_ends_with($this->objectName, "ch") ||
             StationInfo::str_ends_with($this->objectName, "x") ||
             StationInfo::str_ends_with($this->objectName, "z"))
         {
            $pluralName = $this->objectName . "es";
         }
         else
         {
            $pluralName = $this->objectName . "s";
         }
      }
      
      return ($pluralName);
   }
   
   public static function getStationOptions($selectedStationId, $includeNoStationOption)
   {
      $html = "";
      
      if ($includeNoStationOption)
      {
         $selected = ($selectedStationId == StationInfo::UNKNOWN_STATION_ID) ? "selected" : "";
         $html = "<option value=\"" . StationInfo::UNKNOWN_STATION_ID . "\" $selected>None</option>";         
      }
      else
      {
         $html = "<option style=\"display:none\">";         
      }
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStations();
         
         foreach ($result as $row)
         {
            $stationId = intval($row["stationId"]);
            $stationName = $row["name"];
            
            $selected = ($stationId == $selectedStationId) ? "selected" : "";
            
            $html .= "<option value=\"$stationId\" $selected>$stationName</option>";
         }
      }
      
      return ($html);
   }
   
   // Remove if upgrading to PHP 8.
   private function str_ends_with($haystack, $needle)
   {
      $length = strlen($needle);
      return $length > 0 ? substr($haystack, -$length) === $needle : true;
   }
}

/*
if (isset($_GET["stationId"]))
{
   $stationId = $_GET["stationId"];
   $stationInfo = StationInfo::load($stationId);
    
   if ($stationInfo)
   {
      echo "stationId: " .        $stationInfo->stationId .     "<br/>";
      echo "name: " .             $stationInfo->name .          "<br/>";
      echo "label: " .            $stationInfo->label .         "<br/>";
      echo "objectName: " .       $stationInfo->objectName .    "<br/>";
      echo "objectNamePlural: " . $stationInfo->getObjectNamePlural() . "<br/>";
      echo "cycleTime: " .        $stationInfo->cycleTime .     "<br/>";
      echo "hideOnSummary: " .    $stationInfo->hideOnSummary . "<br/>";
      echo "updateTime: "  .      $stationInfo->updateTime .    "<br/>";
   }
   else
   {
      echo "No station info found.";
   }
}
*/
?>