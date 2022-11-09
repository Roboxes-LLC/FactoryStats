<?php
require_once 'database.php';

abstract class SensorType
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const COUNTER = SensorType::FIRST;
   const LAST = 2;
   const COUNT = SensorType::LAST - SensorType::FIRST;
   
   public static $values = array(SensorType::COUNTER);
   
   public static function getLabel($sensorType)
   {
      $labels = array("---", "Counter");
      
      return ($labels[$sensorType]);
   }
}

abstract class SensorStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const UNCONFIGURED = SensorStatus::FIRST;
   const DISABLED = 2;
   const READY = 3;
   const LAST = 4;
   const COUNT = SensorStatus::LAST - SensorStatus::FIRST;
   
   public static $values = array(SensorStatus::UNCONFIGURED, SensorStatus::DISABLED, SensorStatus::READY);
   
   public static function getLabel($sensorStatus)
   {
      $labels = array("---", "Unconfigured", "Disabled", "Ready");
      
      return ($labels[$sensorStatus]);
   }
   
   public static function getClass($sensorStatus)
   {
      $labels = array("", "button-unconfigured", "button-disabled", "button-ready");
      
      return ($labels[$sensorStatus]);
   }
}

class SensorInfo
{
   const UNKNOWN_SENSOR_ID = 0;
   
   const ONLINE_THRESHOLD = 300;  // seconds (i.e. 5 minutes)
   
   public $sensorId;
   public $uid;  // last 4 digbits of MAC address
   public $ipAddress;
   public $version;
   public $name;
   public $sensorType;
   public $stationId;
   public $lastContact;
   public $enabled;
   
   public function __construct()
   {
      $this->sensorId = SensorInfo::UNKNOWN_SENSOR_ID;
      $this->uid = "";
      $this->ipAddress = "";
      $this->version = "";
      $this->name = "";
      $this->sensorType = SensorType::UNKNOWN;
      $this->stationId = StationInfo::UNKNOWN_STATION_ID;
      $this->lastContact = null;
      $this->enabled = false;
   }

   public static function load($sensorId)
   {
      $sensorInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getSensor($sensorId);
         
         if ($result && ($row = $result[0]))
         {
            $sensorInfo= new SensorInfo();
            
            $sensorInfo->sensorId = intval($row['sensorId']);
            $sensorInfo->uid = $row['uid'];
            $sensorInfo->ipAddress = $row['ipAddress'];
            $sensorInfo->version = $row['version'];
            $sensorInfo->name = $row['name'];
            $sensorInfo->sensorType = intval($row['sensorType']);
            $sensorInfo->stationId = intval($row['stationId']);
            $sensorInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
            $sensorInfo->enabled = filter_var($row["enabled"], FILTER_VALIDATE_BOOLEAN);
         }
      }
      
      return ($sensorInfo);
   }
   
   public function isOnline()
   {
      $isOnline = false;
      
      $now = Time::getDateTime(Time::now());
      $lastContact = new DateTime($this->lastContact);
      
      // Determine the interval between the supplied date and the current time.
      $interval = $lastContact->diff($now);
      
      if (($interval->days == 0) && ($interval->h == 0))  // Note: Adjust if threshold is >= 1 hour
      {
         $seconds = (($interval->i * 60) + ($interval->s)); 
         $isOnline = ($seconds <= SensorInfo::ONLINE_THRESHOLD);
      }
      
      return ($isOnline);
   }
   
   public function handleSensorUpdate($sensorPayload, &$result)
   {
      if ($this->enabled)
      {
         switch ($this->sensorType)
         {
            case SensorType::COUNTER:
            {
               if (($this->stationId != StationInfo::UNKNOWN_STATION_ID) &&
                   (isset($sensorPayload["count"])))
               {
                  $count = intval($sensorPayload["count"]);
                  
                  if ($count != 0)
                  {
                     $shiftId = ShiftInfo::getShift(Time::now("Y-m-d H:i:s"));
                     
                     FactoryStatsDatabase::getInstance()->updateCount($this->stationId, $shiftId, $count);
                  }
                  
                  $result->ackedCount = $count;
                  $result->totalCount = $this->getCountForSensor();
               }
               break;
            }
            
            default:
            {
               break;
            }
         }
      }
   }
   
   public function getCountForSensor()
   {
      $count = 0;
      
      $now = Time::now("Y-m-d H:i:s");
      
      $shiftId = ShiftInfo::getShift($now);
      $shiftInfo = ShiftInfo::load($shiftId);
      
      if ($shiftInfo)
      {
         $evaluationTimes = $shiftInfo->getEvaluationTimes($now, $now);
      
         $count = FactoryStatsDatabase::getInstance()->getCount(
            $this->stationId,
            $shiftId,
            $evaluationTimes->startDateTime,
            $evaluationTimes->endDateTime);
      }
      
      return ($count);
   }
   
   function getSensorStatus()
   {
      $sensorStatus = SensorStatus::UNKNOWN;
      
      if (($this->sensorType == SensorType::UNKNOWN) ||
          ($this->stationId == StationInfo::UNKNOWN_STATION_ID))
      {
         $sensorStatus = SensorStatus::UNCONFIGURED;
      }
      else if ($this->enabled == false)
      {
         $sensorStatus = SensorStatus::DISABLED;
      }
      else
      {
         $sensorStatus = SensorStatus::READY;
      }
      
      return ($sensorStatus);
   }
}

/*
if (isset($_GET["buttonId"]))
{
   $buttonId = $_GET["buttonId"];
   $buttonInfo = SensorInfo::load($buttonId);
 
   if ($buttonInfo)
   {
      echo "buttonId: " .          $buttonInfo->buttonId .         "<br/>";
      echo "uid: " .               $buttonInfo->uid .              "<br/>";
      echo "ipAddress: " .         $buttonInfo->ipAddress .        "<br/>";
      echo "version: " .           $buttonInfo->version .          "<br/>";
      echo "name: " .              $buttonInfo->name .             "<br/>";
      echo "stationId: " .         $buttonInfo->stationId .        "<br/>";
      echo "clickAction: " .       $buttonInfo->buttonActions[0] . "<br/>";
      echo "doubleClickAction: " . $buttonInfo->buttonActions[1] . "<br/>";
      echo "holdAction: " .        $buttonInfo->buttonActions[2] . "<br/>";
      echo "lastContact: " .       $buttonInfo->lastContact .      "<br/>";
      echo "enabled: " .           ($buttonInfo->enabled ? "true" : "false") . "<br/>";
   }
   else
   {
      echo "No button info found.";
   }
}
*/
?>