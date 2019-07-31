<?php
require_once 'database.php';
require_once 'time.php';

class ButtonInfo
{
   const UNKNOWN_BUTTON_ID = 0;
   
   const ONLINE_THRESHOLD = 20;  // seconds
   
   public $buttonId = ButtonInfo::UNKNOWN_BUTTON_ID;
   public $macAddress;
   public $ipAddress;
   public $name;
   public $description;
   public $stationId;
   public $lastContact;

   public static function load($buttonId)
   {
      $buttonInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getButton($buttonId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $buttonInfo= new ButtonInfo();
            
            $buttonInfo->buttonId = intval($row['buttonId']);
            $buttonInfo->macAddress = $row['macAddress'];
            $buttonInfo->ipAddress = $row['ipAddress'];
            $buttonInfo->name = $row['name'];
            $buttonInfo->description = $row['description'];
            $buttonInfo->stationId = intval($row['stationId']);
            $buttonInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
         }
      }
      
      return ($buttonInfo);
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
}

/*
 if (isset($_GET["buttonId"]))
 {
    $buttonId = $_GET["buttonId"];
    $buttonInfo = ButtonInfo::load($buttonId);
    
    if ($buttonInfo)
    {
       echo "buttonId: " .    $buttonInfo->buttonId .    "<br/>";
       echo "macAddress: " .  $buttonInfo->macAddress .  "<br/>";
       echo "ipAddress: " .   $buttonInfo->ipAddress .   "<br/>";
       echo "name: " .        $buttonInfo->name .        "<br/>";
       echo "description: " . $buttonInfo->description . "<br/>";
       echo "stationId: " .   $buttonInfo->stationId .   "<br/>";
       echo "lastContact: " . $buttonInfo->lastContact . "<br/>";
    }
    else
    {
       echo "No button info found.";
    }
 }
 */
?>