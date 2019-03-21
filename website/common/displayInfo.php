<?php
require_once 'database.php';
require_once 'time.php';

class DisplayInfo
{
   const UNKNOWN_DISPLAY_ID = 0;
   
   const ONLINE_THRESHOLD = 20;  // seconds
   
   public $displayId = DisplayInfo::UNKNOWN_DISPLAY_ID;
   public $macAddress;
   public $ipAddress;
   public $name;
   public $description;
   public $stationId;
   public $lastContact;

   public static function load($displayId)
   {
      $displayInfo = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getDisplay($displayId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $displayInfo = new DisplayInfo();
            
            $displayInfo->displayId = intval($row['displayId']);
            $displayInfo->macAddress = $row['macAddress'];
            $displayInfo->ipAddress = $row['ipAddress'];
            $displayInfo->name = $row['name'];
            $displayInfo->description = $row['description'];
            $displayInfo->stationId = intval($row['stationId']);
            $displayInfo->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
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
}

/*
 if (isset($_GET["displayId"]))
 {
    $chipId = $_GET["displayId"];
    $displayInfo = DisplayInfo::load($chipId);
    
    if ($displayInfo)
    {
       echo "displayId: " .   $displayInfo->displayId .   "<br/>";
       echo "macAddress: " .  $displayInfo->macAddress .  "<br/>";
       echo "ipAddress: " .   $displayInfo->ipAddress .   "<br/>";
       echo "name: " .        $displayInfo->roboxName .   "<br/>";
       echo "description: " . $displayInfo->description . "<br/>";
       echo "stationId: " .   $displayInfo->stationId .   "<br/>";
       echo "lastContact: " . $displayInfo->lastContact . "<br/>";
    }
    else
    {
       echo "No display entry found.";
    }
 }
 */
?>