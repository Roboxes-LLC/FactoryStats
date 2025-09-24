<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/database.php';

class StationGroup
{
   const UNKNOWN_GROUP_ID = 0;
   
   public $groupId;
   public $name;
   public $stationIds;
   public $virtualStationId;
   
   public function __construct()
   {
      $this->groupId = StationGroup::UNKNOWN_GROUP_ID;
      $this->name = null;
      $this->stationIds = array();
      $this->virtualStationId = StationInfo::UNKNOWN_STATION_ID;
   }
   
   public static function load($groupId)
   {
      $stationGroup = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStationGroup($groupId);
         
         if ($result && ($row = $result[0]))
         {
            $stationGroup = new StationGroup();            
            
            $stationGroup->initialize($row);
            
            $stationGroup->loadWorkstations();
         }
      }
      
      return ($stationGroup);
   }
   
   public function initialize($row)
   {
      $this->groupId = intval($row['groupId']);
      $this->name = $row['name'];
      $this->virtualStationId = intval($row['virtualStationId']);
   }
   
   public static function getOptions($selectedGroupId)
   {
      $allWorkstations = StationGroup::UNKNOWN_GROUP_ID;      
      $selected = ($allWorkstations == $selectedGroupId) ? "selected" : "";
      
      $html = "<option value=\"$allWorkstations\" $selected>All workstations</option>";
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStationGroups();
         
         foreach ($result as $row)
         {
            $stationGroup = new StationGroup();
            $stationGroup->initialize($row);
            
            $selected = ($stationGroup->groupId == $selectedGroupId) ? "selected" : "";
            
            $html .= "<option value=\"$stationGroup->groupId\" $selected>$stationGroup->name</option>";
         }
      }
      
      return ($html);
   }
   
   public function hasVirtualStation()
   {
      return ($this->virtualStationId != StationInfo::UNKNOWN_STATION_ID);
   }
   
   private function loadWorkstations()
   {
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getStationsForGroup($this->groupId);
         
         foreach ($result as $row)
         {
            $this->stationIds[] = intval($row["stationId"]);
         }
      }
   }
}