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
   
   public function getRegistryEntry($chipId)
   {
      $query = "SELECT * from registry WHERE chipId = \"$chipId\";";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getRegistryEntries($userId)
   {
      $userClause = empty($userId) ? "" : "WHERE userId = \"$userId\"";
      $query = "SELECT * from registry $userClause ORDER BY chipId DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function getRegistryEntriesForStation($stationId)
   {
      $query = "SELECT * from registry WHERE roboxName = \"$stationId\" ORDER BY lastContact DESC;";
      
      $result = $this->query($query);
      
      return ($result);
   }
   
   public function existsInRegistry($chipId)
   {
      $query = "SELECT chipId from registry WHERE chipId = \"$chipId\";";
      
      $result = $this->query($query);
      
      return ($result && ($result->num_rows > 0));
   }
   
   public function register($registryEntry)
   {
      $lastContact = Time::toMySqlDate($registryEntry->lastContact);
      
      $query =
      "INSERT INTO registry (chipId, macAddress, ipAddress, roboxName, userId, lastContact) " .
      "VALUES ('$registryEntry->chipId', '$registryEntry->macAddress', '$registryEntry->ipAddress', '$registryEntry->roboxName', '$registryEntry->userId', '$lastContact');";
      echo $query;
      $this->query($query);
   }
   
   public function updateRegistry($registryEntry)
   {
      $lastContact = Time::toMySqlDate($registryEntry->lastContact);
      
      $query =
      "UPDATE registry " .
      "SET macAddress = \"$registryEntry->macAddress\", ipAddress = \"$registryEntry->ipAddress\", roboxName = \"$registryEntry->roboxName\", userId = \"$registryEntry->userId\", lastContact = \"$lastContact\" " .
      "WHERE chipId = $registryEntry->chipId;";
      echo $query;
      $this->query($query);
   }
   
   public function unregister($chipId)
   {
      $query = "DELETE FROM registry WHERE chipId = $chipId;";
      
      $this->query($query);
   }
   
   public function stationExists($stationId)
   {
      $query = "SELECT stationId from station WHERE stationId = \"$stationId\";";

      $result = $this->query($query);
      
      return ($result && ($result->num_rows > 0));
   }
   
   public function newStation($stationId)
   {
      $now = new DateTime(Time::now("Y-m-d H:i:s"));
      
      $query = "INSERT INTO station (stationId, updateTime) VALUES ('$stationId', '$now');";

      $this->query($query);
   }  

   public function getCount($stationId, $startDateTime, $endDateTime)
   {
      $screenCount = 0;
      
      $stationClause = ($stationId == "ALL") ? "" : "stationId = \"$stationId\" AND";
      $query = "SELECT * FROM screencount WHERE $stationClause dateTime BETWEEN '" . Time::toMySqlDate($startDateTime) . "' AND '" . Time::toMySqlDate($endDateTime) . "' ORDER BY dateTime DESC;";
      //echo $query . "<br/>";
      $result = $this->query($query);
      
      while ($result && ($row = $result->fetch_assoc()))
      {
         $screenCount += intval($row["count"]);
      }
      
      return ($screenCount);
   }
   
   public function updateCount($stationId, $screenCount)
   {
      if (!FlexscreenDatabase::stationExists($stationId))
      {
         FlexscreenDatabase::newStation($stationId);
      }
      
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
      $this->updateStation($stationId);
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
   
   protected function updateStation($stationId)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      // Determine if we have an entry for this station.
      $query = "SELECT * from station WHERE stationId = \"$stationId\";";
      //echo $query . "<br/>";
      $result = $this->query($query);
      
      // New entry.
      if ($result && ($result->num_rows == 0))
      {
         $query =
         "INSERT INTO station " .
         "(stationId, updateTime) " .
         "VALUES " .
         "('$stationId', '$now');";
         //echo $query . "<br/>";
         $this->query($query);
      }
      // Updated entry.
      else
      {
         // Record last update time.
         $query = "UPDATE station SET updateTime = \"$now\" WHERE stationId = \"$stationId\";";
         //echo $query . "<br/>";
         $this->query($query);
      }
   }
   
   protected function calculateCountTime($stationId)
   {
      $countTime = 0;
      
      $now = new DateTime(Time::now("Y-m-d H:i:s"));
      
      $updateTime = new DateTime(FlexscreenDatabase::getUpdateTime($stationId), new DateTimeZone('America/New_York'));
      
      if ($updateTime)
      {
         $interval = $updateTime->diff($now);
         
         // With this day?  // TODO: Refine
         if ($interval->days == 0)
         {
            // Convert to seconds.
            $countTime = (($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s);
         }
      }
      
      return ($countTime);
   }
}

?>