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
   
   const ONLINE_THRESHOLD = 20;  // seconds
   
   public $displayId;
   public $uid;
   public $name;
   public $ipAddress;
   public $presentationId;
   public $lastContact;
   public $enabled;
   
   public function __construct()
   {
      $this->displayId = DisplayInfo::UNKNOWN_DISPLAY_ID;
      $this->uid = "";
      $this->name = "";
      $this->ipAddress = "";
      $this->presentationId = PresentationInfo::UNKNOWN_PRESENTATION_ID;
      $this->lastContact = null;
      $this->enabled = false;
   }

   public static function load($displayId)
   {
      $displayInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getDisplay($displayId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $displayInfo = new DisplayInfo();
            
            $displayInfo->displayId = intval($row['displayId']);
            $displayInfo->uid = $row['uid'];
            $displayInfo->name = $row['name'];
            $displayInfo->ipAddress = $row['ipAddress'];
            $displayInfo->presentationId = intval($row['presentationId']);
            $displayInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
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
      
      if (($interval->days == 0) && ($interval->i == 0))
      {
         $isOnline = ($interval->s <= DisplayInfo::ONLINE_THRESHOLD);
      }

      return ($isOnline);
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