<?php

require_once 'databaseKey.php';
require_once 'time.php';

interface Database
{
   public function connect();

   public function disconnect();

   public function isConnected();

   public function query(
      $query);
}

class MySqlDatabase implements Database
{
   function __construct(
      $server,
      $user,
      $password,
      $database)
   {
      $this->server = $server;
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;
   }

   public function connect()
   {
      // Create connection
      $this->connection = new mysqli($this->server, $this->user, $this->password, $this->database);

      // Check connection
      if ($this->connection->connect_error)
      {
         // TODO?
      }
      else
      {
         $this->isConnected = true;
      }
   }

   public function disconnect()
   {
      if ($this->isConnected())
      {
         $this->connection->close();
      }
   }

   public function isConnected()
   {
      return ($this->isConnected);
   }

   public function query(
      $query)
   {
      $result = NULL;

      if ($this->isConnected())
      {
         $result = $this->connection->query($query);
      }

      return ($result);
   }
   
   public static function countResults($result)
   {
      return (mysqli_num_rows($result));
   }
   
   protected function getConnection()
   {
      return ($this->connection);
   }

   private $server = "";

   private $user = "";

   private $password = "";

   private $database = "";

   private $connection;

   private $isConnected = false;
}

class FlexscreenDatabase extends MySqlDatabase
{
   public function __construct()
   {
      global $SERVER, $USER, $PASSWORD, $DATABASE;
      
      parent::__construct($SERVER, $USER, $PASSWORD, $DATABASE);
   }
   
   // **************************************************************************
   
   public function getDisplay($displayId)
   {
      $query = "SELECT * from display WHERE displayId = \"$displayId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getDisplays()
   {
      $query = "SELECT * from display ORDER BY macAddress DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getDisplayByMacAddress($macAddress)
   {
      $query = "SELECT * from display WHERE macAddress = \"$macAddress\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function displayExists($macAddress)
   {
      $query = "SELECT displayId from display WHERE macAddress = \"$macAddress\";";
      
      $result = $this->query($query);
      
      return ($result && ($result->num_rows > 0));
   }
   
   public function newDisplay($displayInfo)
   {
      $lastContact = Time::toMySqlDate($displayInfo->lastContact);
      
      $query =
      "INSERT INTO display (macAddress, ipAddress, lastContact) " .
      "VALUES ('$displayInfo->macAddress', '$displayInfo->ipAddress', '$lastContact');";

      $this->query($query);
   }
   
   public function updateDisplay($displayInfo)
   {
      $lastContact = Time::toMySqlDate($displayInfo->lastContact);
      
      $query =
      "UPDATE display " .
      "SET macAddress = \"$displayInfo->macAddress\", ipAddress = \"$displayInfo->ipAddress\", stationId = \"$displayInfo->stationId\", lastContact = \"$lastContact\" " .
      "WHERE displayId = $displayInfo->displayId;";
      echo $query;
      $this->query($query);
   }
   
   public function deleteDisplay($displayId)
   {
      $query = "DELETE FROM display WHERE displayId = $displayId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   
   public function getButton($buttonId)
   {
      $query = "SELECT * from button WHERE buttonId = \"$buttonId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getButtons()
   {
      $query = "SELECT * from button ORDER BY macAddress DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getButtonsForStation($stationId)
   {
      $query = "SELECT * from button WHERE stationId = \"$stationId\" ORDER BY lastContact DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getButtonByMacAddress($macAddress)
   {
      $query = "SELECT * from button WHERE macAddress = \"$macAddress\";";

      $result = $this->query($query);
     
      return ($result);
   }
   
   public function buttonExists($macAddress)
   {
      $query = "SELECT buttonId from button WHERE macAddress = \"$macAddress\";";
      
      $result = $this->query($query);
      
      return ($result && ($result->num_rows > 0));
   }
   
   public function newButton($buttonInfo)
   {
      $lastContact = Time::toMySqlDate($buttonInfo->lastContact);
      
      $query =
      "INSERT INTO button (macAddress, ipAddress, lastContact) " .
      "VALUES ('$buttonInfo->macAddress', '$buttonInfo->ipAddress', '$lastContact');";

      $this->query($query);
   }
   
   public function updateButton($buttonInfo)
   {
      $lastContact = Time::toMySqlDate($buttonInfo->lastContact);
      
      $query =
      "UPDATE button " .
      "SET macAddress = \"$buttonInfo->macAddress\", ipAddress = \"$buttonInfo->ipAddress\", stationId = \"$buttonInfo->stationId\", lastContact = \"$lastContact\" " .
      "WHERE buttonId = $buttonInfo->buttonId;";

      $this->query($query);
   }
   
   public function deleteButton($buttonId)
   {
      $query = "DELETE FROM button WHERE buttonId = $buttonId;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   // **************************************************************************
   
   public function getStation($stationId)
   {
      $query = "SELECT * from station WHERE stationId = \"$stationId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getStations()
   {
      $query = "SELECT * from station;";
      
      $result = $this->query($query);
      
      return ($result);
   } 
   
   public function stationExists($stationId)
   {
      $query = "SELECT stationId from station WHERE stationId = \"$stationId\";";

      $result = $this->query($query);
      
      return ($result && ($result->num_rows > 0));
   }
   
   public function newStation($stationInfo)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      $query = "INSERT INTO station (name, label, description, cycleTime, updateTime) VALUES ('$stationInfo->name', '$stationInfo->label', '$stationInfo->description', '$stationInfo->cycleTime', $now');";

      $this->query($query);
   }
   
   public function updateStation($stationInfo)
   {
      $query =
      "UPDATE station " .
      "SET name = \"$stationInfo->name\", label = \"$stationInfo->label\", description = \"$stationInfo->description\", cycleTime = $stationInfo->cycleTime " .
      "WHERE stationId = $stationInfo->stationId;";
      echo $query;
      $this->query($query);
   }
   
   public function deleteStation($stationId)
   {
      $query = "DELETE FROM station WHERE stationId = $stationId;";
      
      $this->query($query);
      
      $query = "DELETE FROM screencount WHERE stationId = $stationId;";
      
      $this->query($query);
      
      $query = "UPDATE button SET stationId = NULL WHERE stationId = $stationId;";
      
      $this->query($query);
      
      $query = "UPDATE display SET stationId = NULL WHERE stationId = $stationId;";
      
      $this->query($query);
      
      return ($result);
   }
   
   public function touchStation($stationId)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));

      // Record last update time.
      $query = "UPDATE station SET updateTime = \"$now\" WHERE stationId = \"$stationId\";";

      $this->query($query);
   }
   
   // **************************************************************************

   public function getCount($stationId, $startDateTime, $endDateTime)
   {
      $screenCount = 0;
      
      $stationClause = ($stationId == "ALL") ? "" : "stationId = \"$stationId\" AND";
      $query = "SELECT * FROM screencount WHERE $stationClause dateTime BETWEEN '" . Time::toMySqlDate($startDateTime) . "' AND '" . Time::toMySqlDate($endDateTime) . "' ORDER BY dateTime DESC;";

      $result = $this->query($query);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $screenCount += intval($row["count"]);
      }
      
      return ($screenCount);
   }
   
   public function getHourlyCounts($stationId, $startDateTime, $endDateTime)
   {
       $stationClause = ($stationId == "ALL") ? "" : "stationId = \"$stationId\" AND";
       $query = "SELECT * FROM screencount WHERE $stationClause dateTime BETWEEN '" . Time::toMySqlDate($startDateTime) . "' AND '" . Time::toMySqlDate($endDateTime) . "' ORDER BY stationId ASC, dateTime ASC;";

       $result = $this->query($query);
             
       return ($result);
   }
   
   public function updateCount($stationId, $screenCount)
   {
      $nowHour = Time::toMySqlDate(Time::now("Y-m-d H:00:00"));
      
      // Calculate the time since the update (in seconds).
      $countTime = FlexscreenDatabase::calculateCountTime($stationId);
      
      // Determine if we have an entry for this station/hour.
      $query = "SELECT * from screencount WHERE stationId = \"$stationId\" AND dateTime = \"$nowHour\";";
      //echo $query . "<br/>";
      $result = $this->query($query);
      
      // New entry.
      if ($result && ($result->num_rows == 0))
      {
         $query =
         "INSERT INTO screencount " .
         "(stationId, dateTime, count, countTime) " .
         "VALUES " .
         "('$stationId', '$nowHour', '$screenCount', '$countTime');";
         //echo $query . "<br/>";
         
         $this->query($query);
      }
      // Updated entry.
      else
      {
         // Update counter count.
         $query = "UPDATE screencount SET count = count + $screenCount, countTime = countTime + $countTime WHERE stationId = \"$stationId\" AND dateTime = \"$nowHour\";";
         //echo $query . "<br/>";
         $this->query($query);
      }
      
      // Store a new updateTime for this station.
      $this->touchStation($stationId);
   }
   
   public function getUpdateTime($stationId)
   {
      $updateTime = "";
      
      $query = "SELECT updateTime from station WHERE stationId = \"$stationId\";";
      //echo $query . "<br>";
      $result = $this->query($query);
      
      if ($result && ($row = $result->fetch_assoc()))
      {
         $updateTime = Time::fromMySqlDate($row["updateTime"], "Y-m-d H:i:s");
      }
      
      return ($updateTime);
   }
   
   public function getCountTime($stationId, $startDateTime, $endDateTime)
   {
      $countTime = 0;
      
      $query = "SELECT * FROM screencount WHERE stationId = \"$stationId\" AND dateTime BETWEEN '" . Time::toMySqlDate($startDateTime) . "' AND '" . Time::toMySqlDate($endDateTime) . "' ORDER BY dateTime DESC;";
      //echo $query . "<br/>";
      $result = $this->query($query);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $countTime += intval($row["countTime"]);
      }
      
      return ($countTime);
   }
   
   protected function calculateCountTime($stationId)
   {
      $countTime = 0;
      
      $now = new DateTime("now", new DateTimeZone('America/New_York'));
      
      $updateTime = new DateTime(FlexscreenDatabase::getUpdateTime($stationId), new DateTimeZone('America/New_York'));
      
      if ($updateTime)
      {
         $interval = $updateTime->diff($now);
         
         $sameDay = (($interval->days == 0) &&
                     (intval($now->format('d')) == intval($updateTime->format('d'))));
         
         if ($sameDay)
         {
            // Convert to seconds.
            $countTime = (($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s);
         }
      }
      
      return ($countTime);
   }
}

?>