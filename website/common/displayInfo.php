<?php
require_once 'database.php';
require_once 'presentationInfo.php';
require_once 'time.php';

abstract class DisplayStatus
{
   const UNKNOWN = 0;
   const FIRST = 1;
   const UNCONFIGURED = DisplayStatus::FIRST;
   const DISABLED = 2;
   const READY = 3;
   const LAST = 4;
   const COUNT = DisplayStatus::LAST - DisplayStatus::FIRST;
   
   public static $values = array(DisplayStatus::UNCONFIGURED, DisplayStatus::DISABLED, DisplayStatus::READY);
   
   public static function getLabel($displayStatus)
   {
      $labels = array("---", "Unconfigured", "Disabled", "Ready");
      
      return ($labels[$displayStatus]);
   }
   
   public static function getClass($displayStatus)
   {
      $labels = array("", "display-unconfigured", "display-disabled", "display-ready");
      
      return ($labels[$displayStatus]);
   }
}

class DisplayInfo
{
   const UNKNOWN_DISPLAY_ID = 0;
   
   const ONLINE_THRESHOLD = 45;  // seconds
   
   const RESET_THRESHOLD = 45;   // seconds
   
   const UPGRADE_THRESHOLD = 45;   // seconds
   
   public $displayId;
   public $uid;
   public $name;
   public $ipAddress;
   public $version;
   public $presentationId;
   public $lastContact;
   public $resetTime;
   public $upgradeTime;
   public $firmwareImage;
   public $enabled;
   
   public function __construct()
   {
      $this->displayId = DisplayInfo::UNKNOWN_DISPLAY_ID;
      $this->uid = "";
      $this->name = "";
      $this->ipAddress = "";
      $this->version = "";
      $this->presentationId = PresentationInfo::UNKNOWN_PRESENTATION_ID;
      $this->lastContact = null;
      $this->resetTime = null;
      $this->upgradeTime = null;
      $this->firmwareImage = null;
      $this->enabled = false;
   }

   public static function load($displayId)
   {
      $displayInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getDisplay($displayId);
         
         if ($result && ($row = $result[0]))
         {
            $displayInfo = new DisplayInfo();
            
            $displayInfo->displayId = intval($row['displayId']);
            $displayInfo->uid = $row['uid'];
            $displayInfo->name = $row['name'];
            $displayInfo->ipAddress = $row['ipAddress'];
            $displayInfo->version = $row['version'];
            $displayInfo->presentationId = intval($row['presentationId']);
            $displayInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
            $displayInfo->resetTime = $row['resetTime'] ? Time::fromMySqlDate($row['resetTime'], "Y-m-d H:i:s") : null; 
            $displayInfo->upgradeTime = $row['upgradeTime'] ? Time::fromMySqlDate($row['upgradeTime'], "Y-m-d H:i:s") : null; 
            $displayInfo->firmwareImage = $row['firmwareImage'];
            $displayInfo->enabled = filter_var($row["enabled"], FILTER_VALIDATE_BOOLEAN);
         }
      }
      
      return ($displayInfo);
   }
   
   public function isOnline()
   {
      $isOnline = false;
      
      $now = new DateTime("now", new DateTimeZone('America/New_York'));
      $lastContact = new DateTime($this->lastContact);
      
      // Determine the interval between the supplied date and the current time.
      $interval = $lastContact->diff($now);
      
      if (($interval->days == 0) && ($interval->h == 0))  // Note: Adjust if threshold is >= 1 hour
      {
         $seconds = (($interval->i * 60) + ($interval->s));
         $isOnline = ($seconds <= DisplayInfo::ONLINE_THRESHOLD);
      }

      return ($isOnline);
   }
   
   public function isResetPending()
   {
      $resetPending = false;
      
      if ($this->resetTime)
      {
         $now = new DateTime("now", new DateTimeZone('America/New_York'));
         $resetTime = new DateTime($this->resetTime);
         
         // Determine the interval between the supplied date and the current time.
         $interval = $resetTime->diff($now);
         
         if (($interval->days == 0) && ($interval->h == 0))  // Note: Adjust if threshold is >= 1 hour
         {
            $seconds = (($interval->i * 60) + ($interval->s));
            $resetPending = ($seconds <= DisplayInfo::RESET_THRESHOLD);
         }
      }
      
      return ($resetPending);
   }
      
   public function isUpgradePending()
   {
      $upgradePending = false;
      
      if ($this->upgradeTime)
      {
         $now = new DateTime("now", new DateTimeZone('America/New_York'));
         $upgradeTime = new DateTime($this->upgradeTime);
         
         // Determine the interval between the supplied date and the current time.
         $interval = $upgradeTime->diff($now);
         
         if (($interval->days == 0) && ($interval->h == 0))  // Note: Adjust if threshold is >= 1 hour
         {
            $seconds = (($interval->i * 60) + ($interval->s));
            $upgradePending = ($seconds <= DisplayInfo::UPGRADE_THRESHOLD);
         }
      }
   
      return ($upgradePending);
   }
   
   public function getDisplayStatus()
   {
      $displayStatus = DisplayStatus::UNKNOWN;
      
      if ($this->presentationId == PresentationInfo::UNKNOWN_PRESENTATION_ID)
      {
         $displayStatus = DisplayStatus::UNCONFIGURED;
      }
      else if ($this->enabled == false)
      {
         $displayStatus = DisplayStatus::DISABLED;
      }
      else
      {
         $displayStatus = DisplayStatus::READY;
      }
      
      return ($displayStatus);
   }
   
   public static function generateUid()
   {
      $uid = null;
      
      do
      {
         $uid = strtoupper(dechex(rand(0x100000, 0xFFFFFF)));
      } while (FactoryStatsGlobalDatabase::getInstance()->isDisplayRegistered($uid));
      
      return ($uid);
   }
}

/*
 if (isset($_GET["displayId"]))
 {
    $chipId = $_GET["displayId"];
    $displayInfo = DisplayInfo::load($chipId);
    
    if ($displayInfo)
    {
       echo "displayId: " .      $displayInfo->displayId .      "<br/>";
       echo "uid: " .            $displayInfo->uid .            "<br/>";
       echo "ipAddress: " .      $displayInfo->ipAddress .      "<br/>";
       echo "version: " .        $displayInfo->version .        "<br/>";
       echo "name: " .           $displayInfo->roboxName .      "<br/>";
       echo "presentationId: " . $displayInfo->presentationId . "<br/>";
       echo "lastContact: " .    $displayInfo->lastContact .    "<br/>";
       echo "enabled: " .        ($displayInfo->enabled ? "true" : "false") . "<br/>";
    }
    else
    {
       echo "No display entry found.";
    }
 }
 */

?>