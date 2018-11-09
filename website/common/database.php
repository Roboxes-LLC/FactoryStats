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

   public function getScreenCount($stationId, $startDateTime, $endDateTime)
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
   
   public function updateScreenCount($stationId, $screenCount)
   {
      $this->updateStation($stationId);
      
      $nowHour = Time::toMySqlDate(Time::now("Y-m-d H:00:00"));
      
      // Determine if we have an entry for this station/hour.
      $query = "SELECT * from screencount WHERE stationId = \"$stationId\" AND dateTime = \"$nowHour\";";
      //echo $query . "<br/>";
      $result = $this->query($query);
      
      // New entry.
      if ($result && ($result->num_rows == 0))
      {
         $query =
         "INSERT INTO screencount " .
         "(stationId, dateTime, count) " .
         "VALUES " .
         "('$stationId', '$nowHour', '$screenCount');";
         //echo $query . "<br/>";
         
         $this->query($query);
      }
      // Updated entry.
      else
      {
         // Update counter count.
         $query = "UPDATE screencount SET count = count + $screenCount WHERE stationId = \"$stationId\" AND dateTime = \"$nowHour\";";
         //echo $query . "<br/>";
         $this->query($query);
      }
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
         "(stationId, lastUpdate) " .
         "VALUES " .
         "('$stationId', '$now');";
         //echo $query . "<br/>";
         $this->query($query);
      }
      // Updated entry.
      else
      {
         // Record last update time.
         $query = "UPDATE station SET lastUpdate = \"$now\" WHERE stationId = \"$stationId\";";
         //echo $query . "<br/>";
         $this->query($query);
      }
   }
}

?>
