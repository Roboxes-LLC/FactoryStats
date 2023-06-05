<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/stationInfo.php';
require_once ROOT.'/plugin/plugin.php';
require_once ROOT.'/plugin/pluginEvent.php';
require_once ROOT.'/plugin/pluginManager.php';

// *****************************************************************************
//                               PrecisionCounterConfig

class PrecisionCounterConfig
{
   public $enabled;
   
   public function __construct()
   {
      $this->enabled = false;
      $this->stationId = StationInfo::UNKNOWN_STATION_ID;
   }
   
   public function initialize($jsonString)
   {
      $parsedConfig = json_decode($jsonString);
      
      if ($parsedConfig)
      {
         if (property_exists($parsedConfig, "enabled"))
         {
            $this->enabled = filter_var($parsedConfig->enabled, FILTER_VALIDATE_BOOLEAN);
         }

         if (property_exists($parsedConfig, "enabled"))
         {            
            $this->stationId = intval($parsedConfig->stationId);
         }
      }
   }
}

// *****************************************************************************
//                               PrecisionCounterStatus

class PrecisionCounterStatus
{
   public function __construct()
   {
   }
   
   public function initialize($jsonString)
   {
      $parsedStatus = json_decode($jsonString);
      if ($parsedStatus)
      {
      }
   }
}

// *****************************************************************************
//                           PrecisionCounter (plugin)

class PrecisionCounter extends Plugin
{
   public function __construct()
   {
      parent::__construct();
      
      $this->class = "PrecisionCounter";
      $this->config = new PrecisionCounterConfig();
      $this->status = new PrecisionCounterStatus();
   }
   
   public function initialize($row)
   {
      parent::initialize($row); 

      if (isset($row["config"]))
      {
         $this->config->initialize($row["config"]);
      }
      
      if (isset($row["status"]))
      {
         $this->config->initialize($row["status"]);
      }
   }
   
   // **************************************************************************
   
   public function register()
   {
      PluginManager::register($this, PluginEvent::STATION_COUNT_CHANGED);
   } 
   
   public function handleEvent($event, $payload)
   {
      $handled = false;

      if ($this->config->enabled)
      {   
         switch ($event)
         {
            case PluginEvent::STATION_COUNT_CHANGED:
            {
               $this->handleStationCountChanged($payload);
               $handled = true;
               break;
            }
            
            default:
            {
            }
         }
      }
      
      return ($handled);
   }
   
   // **************************************************************************
   
   private function handleStationCountChanged($payload)
   {
      if ($payload instanceof StationCountChangedPayload)
      {   
         if ($payload->stationId == $this->config->stationId)
         {
            FactoryStatsDatabase::getInstance()->updateExactCount(
               $payload->stationId,
               ShiftInfo::getShift(Time::now()),
               $payload->deltaCount); 
         }
      }
   }
}