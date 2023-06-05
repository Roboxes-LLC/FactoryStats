<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/stationInfo.php';

abstract class PluginEvent
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const STATION_COUNT_CHANGED = PluginEvent::FIRST;
   const LAST = 2;
   const COUNT = PluginEvent::LAST - PluginEvent::FIRST;
}

class PluginEventPayload
{
}

class StationCountChangedPayload extends PluginEventPayload
{
   public $stationId;
   public $shiftId;
   public $deltaCount;
   
   public function __construct($stationId, $shiftId, $deltaCount)
   {
      $this->stationId = $stationId;
      $this->shiftId = $shiftId;
      $this->deltaCount = $deltaCount;
   }
}

?>