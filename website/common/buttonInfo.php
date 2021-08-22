<?php
require_once 'breakInfo.php';
require_once 'buttonDefs.php';
require_once 'database.php';
require_once 'stationInfo.php';
require_once 'time.php';

class ButtonInfo
{
   const UNKNOWN_BUTTON_ID = 0;
   
   const ONLINE_THRESHOLD = 20;  // seconds
   
   const RECENTLY_PRESSED_THRESHOLD = 2;  // seconds
   
   public $buttonId;
   public $uid;  // MAC address, serial number, Flic id
   public $ipAddress;
   public $name;
   public $stationId;
   public $buttonActions;
   public $lastContact;
   public $enabled;
   
   public function __construct()
   {
      $this->buttonId = ButtonInfo::UNKNOWN_BUTTON_ID;
      $this->uid = "";
      $this->ipAddress = "";
      $this->name = "";
      $this->stationId = StationInfo::UNKNOWN_STATION_ID;
      
      $this->buttonActions = array();
      for ($i = 0; $i < ButtonPress::COUNT; $i++)
      {
         $this->buttonActions[$i] = ButtonAction::UNKNOWN;
      }
      
      $this->lastContact = null;
      $this->enabled = false;
   }

   public static function load($buttonId)
   {
      $buttonInfo = null;
      
      $database = FactoryStatsDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getButton($buttonId);
         
         if ($result && ($row = $result[0]))
         {
            $buttonInfo= new ButtonInfo();
            
            $buttonInfo->buttonId = intval($row['buttonId']);
            $buttonInfo->uid = $row['uid'];
            $buttonInfo->ipAddress = $row['ipAddress'];
            $buttonInfo->name = $row['name'];
            $buttonInfo->stationId = intval($row['stationId']);
            $buttonInfo->setButtonAction(ButtonPress::SINGLE_CLICK, intval($row['clickAction']));
            $buttonInfo->setButtonAction(ButtonPress::DOUBLE_CLICK, intval($row['doubleClickAction']));
            $buttonInfo->setButtonAction(ButtonPress::HOLD, intval($row['holdAction']));            
            $buttonInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
            $buttonInfo->enabled = filter_var($row["enabled"], FILTER_VALIDATE_BOOLEAN);
         }
      }
      
      return ($buttonInfo);
   }
   
   public function setButtonAction($buttonPress, $buttonAction)
   {
      if (($buttonPress >= ButtonPress::FIRST) &&
            ($buttonPress < ButtonPress::LAST))
      {
         $this->buttonActions[$buttonPress - ButtonPress::FIRST] = $buttonAction;
      }      
   }
   
   public function getButtonAction($buttonPress)
   {
      $buttonAction = ButtonAction::UNKNOWN;
      
      if (($buttonPress >= ButtonPress::FIRST) &&
          ($buttonPress < ButtonPress::LAST))
      {
         $buttonAction = $this->buttonActions[$buttonPress - ButtonPress::FIRST];
      }      
      
      return ($buttonAction);
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
         $isOnline = ($interval->s <= ButtonInfo::ONLINE_THRESHOLD);
      }
      
      return ($isOnline);
   }
   
   public function recentlyPressed()
   {
      $recentlyPressed = false;
      
      $now = new DateTime("now", new DateTimeZone('America/New_York'));
      $lastContact = new DateTime($this->lastContact);
      
      // Determine the interval between the supplied date and the current time.
      $interval = $lastContact->diff($now);
      
      if (($interval->days == 0) && ($interval->i == 0))
      {
         $recentlyPressed = ($interval->s <= ButtonInfo::RECENTLY_PRESSED_THRESHOLD);
      }

      return ($recentlyPressed);
   }
   
   public function handleButtonPress($buttonPress)
   {
      $buttonAction = $this->getButtonAction($buttonPress);

      if ($this->enabled &&
          ($buttonAction != ButtonAction::UNKNOWN))
      {
         $shiftId = ShiftInfo::getShift(Time::now("Y-m-d H:i:s"));
         
         switch ($buttonAction)
         {
            case ButtonAction::INCREMENT_COUNT:
            {
               if ($this->stationId != StationInfo::UNKNOWN_STATION_ID)
               {
                  FactoryStatsDatabase::getInstance()->updateCount($this->stationId, $shiftId, 1);
               }
               break;
            }
            
            case ButtonAction::DECREMENT_COUNT:
            {
               if ($this->stationId != StationInfo::UNKNOWN_STATION_ID)
               {
                  FactoryStatsDatabase::getInstance()->updateCount($this->stationId, $shiftId, -1);
               }               
               break;
            }
            
            case ButtonAction::PAUSE_STATION:
            {
               echo "StationId: " . $this->stationId . ", ShiftId: " . $shiftId . "<br>";
               if ($this->stationId != StationInfo::UNKNOWN_STATION_ID)
               {
                  if (BreakInfo::isOnBreak($this->stationId, $shiftId))
                  {
                     BreakInfo::endBreak($this->stationId, $shiftId);
                  }
                  else 
                  {
                     BreakInfo::startBreak($this->stationId, $shiftId, 1);                     
                  }
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
   
   function getButtonStatus()
   {
      $buttonStatus = ButtonStatus::UNKNOWN;
      
      if (($this->stationId == StationInfo::UNKNOWN_STATION_ID) &&
          ($this->getButtonAction(ButtonPress::SINGLE_CLICK) == ButtonAction::UNKNOWN) &&
          ($this->getButtonAction(ButtonPress::DOUBLE_CLICK) == ButtonAction::UNKNOWN) &&
          ($this->getButtonAction(ButtonPress::HOLD) == ButtonAction::UNKNOWN))
      {
         $buttonStatus = ButtonStatus::UNCONFIGURED;
      }
      else if ($this->enabled == false)
      {
         $buttonStatus = ButtonStatus::DISABLED;
      }
      else
      {
         $buttonStatus = ButtonStatus::READY;
      }
      
      return ($buttonStatus);
   }
}

/*
if (isset($_GET["buttonId"]))
{
   $buttonId = $_GET["buttonId"];
   $buttonInfo = ButtonInfo::load($buttonId);
 
   if ($buttonInfo)
   {
      echo "buttonId: " .          $buttonInfo->buttonId .         "<br/>";
      echo "uid: " .               $buttonInfo->uid .              "<br/>";
      echo "ipAddress: " .         $buttonInfo->ipAddress .        "<br/>";
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