<?php

require_once '../common/databaseKey.php';

function mysqlTest($iterations)
{
   global $SERVER, $USER, $PASSWORD, $DATABASE;
   
   $connection = new mysqli($SERVER, $USER, $PASSWORD, $DATABASE);
   
   $now = Time::now("Y-m-d H:i:s");
   
   for ($i = 0; $i < $iterations; $i++)
   {
      $now = Time::decrementDay($now);
      $startTime = Time::toMySqlDate(Time::startOfDay($now));
      $endTime = Time::toMySqlDate(Time::endOfDay($now));
      
      $query = "SELECT SUM(count) AS countSum FROM count WHERE stationId = 1 AND shiftId=1 AND dateTime BETWEEN \"$startTime\" AND \"$endTime\";";
      
      $dbaseResult = $connection->query($query);
      
      $result = $dbaseResult->fetch_array(MYSQLI_ASSOC);
      
      //var_dump($result);
   }
}

function pdoTest($iterations)
{
   global $SERVER, $USER, $PASSWORD, $DATABASE;
   
   $options = [
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false
   ];
   
   $pdo = new PDO("mysql:host=$SERVER;dbname=$DATABASE", $USER, $PASSWORD, $options);
   
   $query = "SELECT SUM(count) AS countSum FROM count WHERE stationId = 1 AND shiftId=1 AND dateTime BETWEEN ? AND ?;";
   
   $statement = $pdo->prepare($query);
   
   $now = Time::now("Y-m-d H:i:s");
   
   for ($i = 0; $i < $iterations; $i++)
   {
      $now = Time::decrementDay($now);
      $startTime = Time::toMySqlDate(Time::startOfDay($now));
      $endTime = Time::toMySqlDate(Time::endOfDay($now));
      
      $result = $statement->execute([$startTime, $endTime]) ? $statement->fetchAll() : null;
      
      //var_dump($result);
   }
}

$then = microtime(true);
pdoTest(100);
$now = microtime(true);
echo sprintf("pdoTest:  %f", $now-$then);

echo "<br><br>";

$then = microtime(true);
mysqlTest(100);
$now = microtime(true);
echo sprintf("mysqlTest:  %f", $now-$then);

?>