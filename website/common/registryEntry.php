<?php
require_once 'database.php';
require_once 'time.php';

class RegistryEntry
{
   const UNKNOWN_CHIP_ID = 0;
   
   public $chipId = RegistryEntry::UNKNOWN_CHIP_ID;
   public $macAddress;
   public $ipAddress;
   public $roboxName;
   public $userId;
   public $lastContact;

   public static function load($chipId)
   {
      $registryEntry = null;
      
      $database = new RoboxesDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getRegistryEntry($chipId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $registryEntry= new RegistryEntry();
            
            $registryEntry->chipId= intval($row['chipId']);
            $registryEntry->macAddress = $row['macAddress'];
            $registryEntry->ipAddress= $row['ipAddress'];
            $registryEntry->roboxName= $row['roboxName'];
            $registryEntry->userId= $row['userId'];
            $registryEntry->lastContact = Time::fromMySqlDate($row['lastContact'], "Y-m-d H:i:s");
         }
      }
      
      return ($registryEntry);
   }
}

/*
 if (isset($_GET["chipId"]))
 {
    $chipId = $_GET["chipId"];
    $registryEntry = RegistryEntry::load($chipId);
    
    if ($registryEntry)
    {
       echo "chipId: " .      $registryEntry->chipId .      "<br/>";
       echo "macAddress: " .  $registryEntry->macAddress .  "<br/>";
       echo "ipAddress: " .   $registryEntry->ipAddress .   "<br/>";
       echo "roboxName: " .   $registryEntry->roboxName .   "<br/>";
       echo "userId: " .      $registryEntry->userId .      "<br/>";
       echo "lastContact: " . $registryEntry->lastContact . "<br/>";
    }
    else
    {
       echo "No registry entry found.";
    }
 }
 */
?>