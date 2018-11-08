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

   public function getScreenCounts($stationId, $startDate, $endDate)
   {
      
   }
   
   public function getScreenCountsByHour($stationId, $date)
   {
      
   }
   
   public function updateScreenCount($stationId, $screenCount)
   {
      updateStation($stationId);
      
      $nowHour = Time::toMySqlDate(Time::now("Y-m-d H"));
      
      // Determine if we have an entry for this station/hour.
      $query = "SELECT * from screencount WHERE stationId = \"$stationId\";";
      $result = $this->query($query);
      
      // New entry.
      if (!$result)
      {
         $query =
         "INSERT INTO screencount " .
         "(stationId, dateTime, count) " .
         "VALUES " .
         "('$stationId', '$nowHour', '$screenCount');";
         
         $this->query($query);
      }
      // Updated entry.
      else
      {
         // Update counter count.
         $query = "UPDATE sensor SET count = count + $screenCount WHERE stationId = \"$stationId\" AND dateTime = \"$nowHour\";";
         $this->query($query);
      }
   }
   
   protected function updateStation($stationId)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      // Determine if we have an entry for this station.
      $query = "SELECT * from station WHERE stationId = \"$stationId\";";
      $result = $this->query($query);
      
      // New entry.
      if (!$result)
      {
         $query =
         "INSERT INTO station " .
         "(stationId, lastUpdate) " .
         "VALUES " .
         "('$stationId', '$now');";
      }
      // Updated entry.
      else
      {
         // Record last update time.
         $query = "UPDATE station SET lastUpdate = \"$now\" WHERE stationId = \"$stationId\";";
         $this->query($query);
      }
   }
}

?>
