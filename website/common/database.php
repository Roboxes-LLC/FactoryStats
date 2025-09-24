<?php

if (!defined('ROOT')) require_once '../root.php';
require_once ROOT.'/common/breakInfo.php';
require_once ROOT.'/common/buttonInfo.php';
require_once ROOT.'/common/databaseDefs.php';
require_once ROOT.'/common/databaseKey.php';
require_once ROOT.'/common/displayInfo.php';
require_once ROOT.'/common/displayRegistry.php';
require_once ROOT.'/common/roles.php';
require_once ROOT.'/common/sensorInfo.php';
require_once ROOT.'/common/shiftInfo.php';
require_once ROOT.'/common/slideInfo.php';
require_once ROOT.'/common/time.php';
require_once ROOT.'/common/userInfo.php';

interface Database
{
   public function connect();

   public function disconnect();

   public function isConnected();

   public function query($query);
   
   public function countResults($result);
   
   public function rowsAffected();
   
   public function lastInsertId();
}

class PDODatabase implements Database
{
   public function __construct(
      $databaseType,
      $server,
      $user,
      $password,
      $database)
   {
      $this->databaseType = $databaseType;
      $this->server = $server;
      $this->user = $user;
      $this->password = $password;
      $this->database = $database;
      
      $this->isConnected = false;
      $this->pdo = null;
   }
   
   public function connect()
   {
      try
      {
         $this->pdo = new PDO($this->getDSN(), $this->user, $this->password, $this->getOptions());
         
         $this->isConnected = true;
      }
      catch (PDOException $exception)
      {
         throw new PDOException($exception->getMessage(), (int)$exception->getCode());
         
         // TODO: Database error handling.
         echo "Database error: " . $exception->getMessage() . ", code(" . (int)$exception->getCode() . ")";
      }
   }
   
   public function disconnect()
   {
      $this->pdo = null;
      $this->isConnected = false;
   }
   
   public function isConnected()
   {
      return ($this->isConnected);
   }
   
   public function query($query)
   {
      $result = null;
      
      if ($this->isConnected())
      {
         $result = $this->pdo->query($query);
      }
      
      return ($result);
   }
   
   public function countResults($result)
   {
      return (count($result));
   }
   
   public function rowsAffected()
   {
      return($this->pdo->rowCount());
   }
   
   public function lastInsertId()
   {
      return ($this->pdo->lastInsertId());
   }
   
   private function getDSN()
   {
      $dsn = DatabaseType::getConnectString($this->databaseType, $this->server, $this->database);     

      return ($dsn);
   }
   
   private function getOptions()
   {
      $dsn = DatabaseType::getOptions($this->databaseType);
      
      return ($dsn);
   }
   
   protected $databaseType;
   
   protected $server;
   
   protected $user;
   
   protected $password;
   
   protected $database;
   
   protected $isConnected;
   
   protected $pdo;
}

class FactoryStatsGlobalDatabase extends PDODatabase
{
   public static function getInstance()
   {
      if (!FactoryStatsGlobalDatabase::$databaseInstance)
      {
         self::$databaseInstance = new FactoryStatsGlobalDatabase();
         
         self::$databaseInstance->connect();
      }
      
      return (self::$databaseInstance);
   }
   
   public function __construct()
   {
      global $DATABASE_TYPE, $GLOBAL_SERVER, $GLOBAL_USER, $GLOBAL_PASSWORD, $GLOBAL_DATABASE;
      
      parent::__construct($DATABASE_TYPE, $GLOBAL_SERVER, $GLOBAL_USER, $GLOBAL_PASSWORD, $GLOBAL_DATABASE);
   }
   
   // **************************************************************************
   
   public function isDisplayRegistered($uid)
   {
      $statement = $this->pdo->prepare("SELECT * FROM displayregistry WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      return ($result && (count($result) == 1));
   }
   
   public function registerDisplay($uid)
   {
      $statement = $this->pdo->prepare("INSERT INTO displayregistry (uid, subdomain) VALUES (?, ?);");
      
      $result = $statement->execute([$uid, DisplayRegistry::UNKNOWN_SUBDOMAIN]);
      
      return ($result);
   }
   
   public function uregisterDisplay($uid)
   {
      $statement = $this->pdo->prepare("DELETE FROM displayregistry WHERE uid = ?;");
      
      $result = $statement->execute([$uid]);
      
      return ($result);
   }
   
   public function associateDisplayWithSubdomain($uid, $subdomain)
   {
      $statement = $this->pdo->prepare("UPDATE displayregistry SET subdomain = ? WHERE uid = ?;");
      
      $result = $statement->execute([$subdomain, $uid]);
      
      return ($result);
   }
   
   public function getAssociatedSubdomainForDisplay($uid)
   {
      $domain = "";
      
      $statement = $this->pdo->prepare("SELECT * FROM displayregistry WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]))
      {
         $domain = $row["subdomain"];
      }
      
      return ($domain);
   }
   
   // **************************************************************************
   //                                Customer
   
   public function getCustomer($customerId)
   {
      $statement = $this->pdo->prepare("SELECT * from customer WHERE customerId = ?;");
      
      $result = $statement->execute([$customerId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCustomers()
   {
      $statement = $this->pdo->prepare("SELECT * FROM customer ORDER BY name ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCustomerFromSubdomain($subdomain)
   {
      $statement = $this->pdo->prepare("SELECT * from customer WHERE subdomain = ?;");
      
      $result = $statement->execute([$subdomain]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getCustomersForUser($userId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM customer INNER JOIN user_customer ON customer.customerId = user_customer.customerId WHERE user_customer.userId = ? ORDER BY customer.name ASC;");
      
      $result = $statement->execute([$userId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   // **************************************************************************
   //                                   User
   
   public function getUser($userId)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT * FROM $userTable WHERE userId = ?;");
      
      $result = $statement->execute([$userId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUserByName($username)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT * FROM $userTable WHERE username = ?");
      
      $result = $statement->execute([$username]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUsers()
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT * FROM $userTable ORDER BY firstName ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUsersForCustomer($customerId)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT * FROM $userTable INNER JOIN user_customer ON $userTable.userId = user_customer.userId WHERE user_customer.customerId = ? ORDER BY $userTable.firstName ASC;");
      
      $result = $statement->execute([$customerId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUsersForCustomers($customerIds)
   {
      $result = null;
      
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      if (count($customerIds) > 0)
      {
         $customerClause = "";
         $symbols = array();
         for ($i = 0; $i < count($customerIds); $i++)
         {
            $symbols[] = "?";
         }
         $customerClause = implode(",", $symbols);
         
         $statement = $this->pdo->prepare("SELECT DISTINCT $userTable.userId FROM $userTable INNER JOIN user_customer ON $userTable.userId = user_customer.userId WHERE user_customer.customerId IN ($customerClause) ORDER BY $userTable.firstName ASC;");
         
         $result = $statement->execute($customerIds) ? $statement->fetchAll() : null;
      }
      
      return ($result);
   }
   
   public function getUsersByRole($role)
   {
      $params = array();
      
      $roleClause = "";
      if ($role != Role::UNKNOWN)
      {
         $roleClause = "WHERE roles = ?";
         $params[] = $role;
      }
      
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $query = "SELECT * FROM $userTable $roleClause ORDER BY firstName ASC;";
      
      $statement = $this->pdo->prepare($query);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getUsersByRoles($roles)
   {
      $result = null;
      
      $params = array();
      
      if (sizeof($roles) > 0)
      {
         $rolesClause = "roles in (";
         
         $count = 0;
         foreach ($roles as $role)
         {
            $rolesClause .= "?";
            $params[] = $role;
            
            $count++;
            
            if ($count < sizeof($roles))
            {
               $rolesClause .= ", ";
            }
         }
         
         $rolesClause .= ")";
         
         $userTable = DatabaseType::reservedName("user", $this->databaseType);
         
         $query = "SELECT * FROM $userTable WHERE $rolesClause ORDER BY firstName ASC;";
         
         $statement = $this->pdo->prepare($query);
         
         $result = $statement->execute($params) ? $statement->fetchAll() : false;
      }
      
      return ($result);
   }
   
   public function newUser($userInfo, $customerId)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare(
         "INSERT INTO $userTable " .
         "(username, passwordHash, roles, permissions, firstName, lastName, email, authToken) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      
      $result = $statement->execute(
         [
            $userInfo->username,
            $userInfo->passwordHash,
            $userInfo->roles,
            $userInfo->permissions,
            $userInfo->firstName,
            $userInfo->lastName,
            $userInfo->email,
            $userInfo->authToken
         ]);
      
      if ($result)
      {
         $this->addUserToCustomer($this->lastInsertId(), $customerId);
      }
      
      return ($result);
   }
   
   public function updateUser($userInfo)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare(
         "UPDATE $userTable " .
         "SET username = ?, passwordHash = ?, roles = ?, permissions = ?, firstName = ?, lastName = ?, email = ?, authToken = ? " .
         "WHERE userId = ?");
      
      $result = $statement->execute(
         [
            $userInfo->username,
            $userInfo->passwordHash,
            $userInfo->roles,
            $userInfo->permissions,
            $userInfo->firstName,
            $userInfo->lastName,
            $userInfo->email,
            $userInfo->authToken,
            $userInfo->userId
         ]);
      
      return ($result);
   }
   
   public function updatePassword($userId, $passwordHash)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("UPDATE $userTable SET passwordHash = ? WHERE userId = ?");
      
      $result = $statement->execute([$passwordHash, $userId]);
      
      return ($result);
   }
   
   public function deleteUser($userId)
   {
      $userTable = DatabaseType::reservedName("user", $this->databaseType);
      
      $statement = $this->pdo->prepare("DELETE FROM $userTable WHERE userId = ?");
      
      $result = $statement->execute([$userId]);
      
      $statement = $this->pdo->prepare("DELETE FROM user_customer WHERE userId = ?");
      
      $result &= $statement->execute([$userId]);
      
      return ($result);
   }
   
   public function customerHasUser($userId, $customerId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM user_customer WHERE userId = ? AND customerId = ?;");
      
      $result = $statement->execute([$userId, $customerId]);
      
      return ($result && (count($result) > 0));
   }
   
   public function addUserToCustomer($userId, $customerId)
   {
      $statement = $this->pdo->prepare("INSERT INTO user_customer (userId, customerId) VALUE (?, ?);");
      
      $result = $statement->execute([$userId, $customerId]);
      
      return ($result);
   }
   
   public function removeUserFromCustomer($userId, $customerId)
   {
      $statement = $this->pdo->prepare("DELETE FROM user_customer WHERE userId = ? AND customerId = ?;");
      
      $result = $statement->execute([$userId, $customerId]);
      
      return ($result);
   }
   
   public function userExists($username)
   {
      $results = $this->getUserByName($username);
      return ($results && ($this->countResults($this->getUserByName($username)) > 0));
   }
   
   // **************************************************************************
   
   private static $databaseInstance = null;
}

class FactoryStatsDatabase extends PDODatabase
{
   public static function getInstance()
   {
      if (!FactoryStatsDatabase::$databaseInstance)
      {
         self::$databaseInstance = new FactoryStatsDatabase();
         
         self::$databaseInstance->connect();
      }
      
      return (self::$databaseInstance);
   }
   
   public function __construct()
   {
      global $DATABASE_TYPE, $SERVER, $USER, $PASSWORD;
      
      $database = $_SESSION["database"];
      
      parent::__construct($DATABASE_TYPE, $SERVER, $USER, $PASSWORD, $database);
   }
   
   // **************************************************************************
   //                                   Break
   
   public function getCurrentBreakId($stationId, $shiftId)
   {
      $breakId = 0;
      
      $breakTable = DatabaseType::reservedName("break", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT breakId FROM $breakTable WHERE stationId = ? AND shiftId = ? AND endTime IS NULL;");
      
      $result = $statement->execute([$stationId, $shiftId]) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]))
      {
         $breakId = $row["breakId"];
      }
      
      return ($breakId);
   }
   
   public function isOnBreak($stationId, $shiftId)
   {
      return ($this->getCurrentBreakId($stationId, $shiftId) != 0);
   }
   
   public function startBreak($stationId, $shiftId, $breakDescriptionId, $startDateTime)
   {
      $result = false;
      
      if (!$this->isOnBreak($stationId, $shiftId))
      {
         $breakTable = DatabaseType::reservedName("break", $this->databaseType);
         
         $statement = $this->pdo->prepare(
            "INSERT INTO $breakTable " .
            "(stationId, shiftId, breakDescriptionId, startTime) " .
            "VALUES " .
            "(?, ?, ?, ?);");

         $result = $statement->execute(
            [
               $stationId,
               $shiftId, 
               $breakDescriptionId,
               Time::toMySqlDate($startDateTime)
            ]);
      }
      
      return ($result);
   }
   
   public function endBreak($stationId, $shiftId, $endDateTime)
   {
      $result = false;
      
      $breakId = $this->getCurrentBreakId($stationId, $shiftId);
      
      if ($breakId != BreakInfo::UNKNOWN_BREAK_ID)
      {
         $breakTable = DatabaseType::reservedName("break", $this->databaseType);
         
         $statement = $this->pdo->prepare("UPDATE $breakTable SET endTime = ? WHERE breakId = ?;");
         
         $result = $statement->execute(
            [
               Time::toMySqlDate($endDateTime),
               $breakId
            ]);
      }
      
      return ($result);
   }
   
   public function getBreak($breakId)
   {
      $breakTable = DatabaseType::reservedName("break", $this->databaseType);
      
      $statement = $this->pdo->prepare("SELECT * from $breakTable WHERE breakId = ?;");
      
      $result = $statement->execute([$breakId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getBreaks($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $params = array();
      
      $stationClause = "";
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationClause = "stationId = ? AND";
         $params[] = $stationId;
      }
      
      $shiftClause = "";
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftClause = "shiftId = ? AND";
         $params[] = $shiftId;
      }
      
      $breakTable = DatabaseType::reservedName("break", $this->databaseType);
      
      $statement = $this->pdo->prepare(
         "SELECT * FROM $breakTable WHERE $stationClause $shiftClause " .
         "startTime BETWEEN ? AND ? " .
         "ORDER BY stationId ASC, startTime ASC;");
      
      $params[] = Time::toMySqlDate($startDateTime);
      $params[] = Time::toMySqlDate($endDateTime);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getBreakTime($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $breakTime = 0;
      
      $breakTable = DatabaseType::reservedName("break", $this->databaseType);

      $statement = $this->pdo->prepare(
         "SELECT * FROM $breakTable WHERE " .
         "stationId = ? AND shiftId = ? AND " .
         "startTime BETWEEN ? AND ? " .
         "ORDER BY startTime DESC;");
      
      $result = $statement->execute(
         [
            $stationId,
            $shiftId,
            Time::toMySqlDate($startDateTime),
            Time::toMySqlDate($endDateTime)
         ]);
      
      $updateTime = getUpdateTime($stationId);
      
      foreach ($result as $row)
      {
         // Only count complete breaks.
         $isCompleteBreak = ($row["endTime"] != null);
         
         // Don't count breaks that start *after* the last screen count.
         $startTime = Time::fromMySqlDate($row["startTime"], "Y-m-d H:i:s");
         $isValidBreak = (new DateTime($startTime) < new DateTime($updateTime));
         
         if ($isCompleteBreak && $isValidBreak)
         {
            $breakTime += Time::differenceSeconds($row["startTime"], $row["endTime"]);
         }
      }
      
      return ($breakTime);
   }
   
   // **************************************************************************
   //                                   Count
   
   public function getCount($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $screenCount = 0;
      
      $params = array();
      
      $stationClause = "";
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationClause = "stationId = ? AND";
         $params[] = $stationId;
      }
            
      $shiftClause = "";
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftClause = "shiftId = ? AND";
         $params[] = $shiftId;
      }
      
      $params[] = Time::toMySqlDate($startDateTime);
      $params[] = Time::toMySqlDate($endDateTime);
      
      $query = "SELECT SUM(count) AS countSum FROM count WHERE $stationClause $shiftClause dateTime BETWEEN ? AND ?;";
      
      $statement = $this->pdo->prepare($query);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]))
      {
         $screenCount = intval($row['countSum']);
      }
      
      return ($screenCount);
   }
   
   public function getHourlyCounts($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $params = array();
      
      $stationClause = "";
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationClause = "stationId = ? AND";
         $params[] = $stationId;
      }
      
      $shiftClause = "";
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftClause = "shiftId = ? AND";
         $params[] = $shiftId;
      }
      
      $params[] = Time::toMySqlDate($startDateTime);
      $params[] = Time::toMySqlDate($endDateTime);
            
      $statement = $this->pdo->prepare(
         "SELECT * FROM count " .
         "WHERE $stationClause $shiftClause dateTime BETWEEN ? AND ? " .
         "ORDER BY stationId ASC, dateTime ASC;");
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function updateCount($stationId, $shiftId, $count)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      $nowHour = Time::toMySqlDate(Time::now("Y-m-d H:00:00"));
      
      // Incremented count
      if ($count > 0)
      {
         // Calculate the time since the update (in seconds).
         $countTime = $this->calculateCountTime($stationId);
         
         // Determine if we have an entry for this station/hour.
         $statement = $this->pdo->prepare(
            "SELECT * from count " .
            "WHERE stationId = ? AND shiftId = ? AND dateTime = ?;");
   
         $result = $statement->execute([$stationId, $shiftId, $nowHour]) ? $statement->fetchAll() : null;
   
         if (!$result || (count($result) == 0))
         {
            $statement = $this->pdo->prepare(
               "INSERT INTO count " .
               "(stationId, shiftId, dateTime, count, countTime, firstEntry, lastEntry) " .
               "VALUES (?, ?, ?, ?, ?, ?, ?);");
            
            $result = $statement->execute(
               [
                  $stationId,
                  $shiftId, 
                  $nowHour,
                  $count,
                  $countTime,
                  $now,
                  $now
               ]);
         }
         // Updated entry.
         else
         {
            $statement = $this->pdo->prepare(
               "UPDATE count SET " .
               "count = count + ?, countTime = countTime + ?, lastEntry = ? " .
               "WHERE stationId = ? AND shiftId = ? AND dateTime = ?;");
            
            $result = $statement->execute(
               [
                  $count,
                  $countTime,
                  $now,
                  $stationId,
                  $shiftId,
                  $nowHour
               ]);
         }
      }
      // Decremented count
      // Note: Rather than add a negative entry, we'll deduct from the last entry from this shift.
      else if ($count < 0)
      {
         $shiftInfo = ShiftInfo::load($shiftId);
         
         if ($shiftInfo)
         {
            $shiftTimes = $shiftInfo->getShiftTimes(Time::now("Y-m-d"));
            
            $hourlyCounts = FactoryStatsDatabase::getHourlyCounts($stationId, $shiftId, $shiftTimes->startDateTime, $shiftTimes->endDateTime);
            
            // Retrieve the last entry.
            $lastEntry = end($hourlyCounts);
            
            if ($lastEntry)
            {
               $entryId = intval($lastEntry["entryId"]);
               
               // Compute the decremented count for this horly period.
               $updatedCount = max((intval($lastEntry["count"]) + $count), 0);
               
               if ($updatedCount > 0)
               {
                  // Decrement the count for this entry.
                  $statement = $this->pdo->prepare("UPDATE count SET count = ? WHERE entryId = ?;");
                  $result = $statement->execute([$updatedCount, $entryId]);
               }
               else
               {
                  // Delete the count for this entry.
                  $statement = $this->pdo->prepare("DELETE FROM count WHERE entryId = ?;");
                  $result = $statement->execute([$entryId]);
               }
            }
         }
      }
      
      // Store a new updateTime for this station.
      $this->touchStation($stationId);
   }
   
   public function getFirstEntry($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $firstEntry = null;
      
      $statement = $this->pdo->prepare(
         "SELECT firstEntry FROM count " .
         "WHERE stationId = ? AND shiftId = ? AND dateTime BETWEEN ? AND ? " .
         "ORDER BY dateTime ASC;");
      
      $startDateTime = Time::toMySqlDate($startDateTime);
      $endDateTime = Time::toMySqlDate($endDateTime);
      
      $result = $statement->execute(
         [
            $stationId,
            $shiftId, 
            $startDateTime,
            $endDateTime,
         ]) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]) && $row["firstEntry"])
      {
         $firstEntry = Time::fromMySqlDate($row["firstEntry"], "Y-m-d H:i:s");
      }
      
      return ($firstEntry);
   }
   
   public function getLastEntry($stationId,  $shiftId, $startDateTime, $endDateTime)
   {
      $lastEntry = null;
      
      $statement = $this->pdo->prepare(
            "SELECT lastEntry FROM count " .
            "WHERE stationId = ? AND shiftId = ? AND dateTime BETWEEN ? AND ? " .
            "ORDER BY dateTime DESC;");
      
      $startDateTime = Time::toMySqlDate($startDateTime);
      $endDateTime = Time::toMySqlDate($endDateTime);
      
      $result = $statement->execute(
         [
            $stationId, 
            $shiftId,
            $startDateTime,
            $endDateTime,
         ]) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]) && $row["lastEntry"])
      {
         $lastEntry = Time::fromMySqlDate($row["lastEntry"], "Y-m-d H:i:s");
      }
      
      return ($lastEntry);
   }
   
   public function setHourlyCount($stationId, $shiftId, $dateTime, $count, $countTime)
   {
      $dateTime = Time::toMySqlDate($dateTime);
      
      $firstEntry = Time::toMySqlDate(Time::startOfHour($dateTime));
      $lastEntry = Time::toMySqlDate(Time::endOfHour($dateTime));
      
      $statement = $this->pdo->prepare(
         "INSERT INTO count " .
         "(stationId, shiftId, dateTime, count, countTime, firstEntry, lastEntry) " .
         "VALUES " .
         "(?, ?, ?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $stationId,
            $shiftId,
            $dateTime,
            $count,
            $countTime,
            $firstEntry,
            $lastEntry
         ]);
      
      return ($result);
   }
   
   public function hasCountEntry($stationId, $shiftId, $dateTime)
   {
      $dateTime = Time::toMySqlDate($dateTime);
      
      $statement = $this->pdo->prepare(
            "SELECT * FROM count WHERE stationId = ? AND shiftId = ? AND dateTime = ?;");
      
      $result = $statement->execute(
         [
            $stationId,
            $shiftId,
            $dateTime
         ]) ? $statement->fetchAll() : null;
      
      return ($result && (count($result) > 0));
   }
   
   // **************************************************************************
   //                              Break Description
   
   public function getBreakDescription($breakDescriptionId)
   {
      $statement = $this->pdo->prepare("SELECT * from breakdescription WHERE breakDescriptionId = ?;");
      
      $result = $statement->execute([$breakDescriptionId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getBreakDescriptionFromCode($breakCode)
   {
      $statement = $this->pdo->prepare("SELECT * from breakdescription WHERE code = ?;");
      
      $result = $statement->execute([$breakCode]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getBreakDescriptions()
   {
      $statement = $this->pdo->prepare("SELECT * from breakdescription;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
      
   }
   
   public function newBreakDescription($breakDescription)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO breakdescription (code, description) VALUES (?, ?)");
      
      $result = $statement->execute([$breakDescription->code, $breakDescription->description]);
      
      return ($result);
   }
   
   public function updateBreakDescription($breakDescription)
   {
      $statement = $this->pdo->prepare(
         "UPDATE breakdescription SET code = ?, description = ? WHERE breakDescriptionId = ?");
      
      $result = $statement->execute(
         [
            $breakDescription->code, 
            $breakDescription->description, 
            $breakDescription->breakDescriptionId
         ]);
      
      return ($result);
   }
   
   public function deleteBreakDescription($breakDescriptionId)
   {
      $statement = $this->pdo->prepare("DELETE FROM breakdescription WHERE breakDescriptionId = ?");
      
      $result = $statement->execute([$breakDescriptionId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                   Button
   
   public function getButton($buttonId)
   {
      $statement = $this->pdo->prepare("SELECT * from button WHERE buttonId = ?;");
      
      $result = $statement->execute([$buttonId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getButtons()
   {
      $statement = $this->pdo->prepare("SELECT * from button ORDER BY uid ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getButtonsForStation($stationId)
   {
      $statement = $this->pdo->prepare("SELECT * from button WHERE stationId = ? ORDER BY lastContact DESC;");
      
      $result = $statement->execute([$stationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getButtonByUid($uid)
   {
      $statement = $this->pdo->prepare("SELECT * from button WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function buttonExists($uid)
   {
      $statement = $this->pdo->prepare("SELECT buttonId from button WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      return ($result && (count($result) > 0));
   }
   
   public function newButton($buttonInfo)
   {
      $lastContact = Time::toMySqlDate($buttonInfo->lastContact);
      
      $clickAction = $buttonInfo->getButtonAction(ButtonPress::SINGLE_CLICK);
      $doubleClickAction = $buttonInfo->getButtonAction(ButtonPress::DOUBLE_CLICK);
      $holdAction = $buttonInfo->getButtonAction(ButtonPress::HOLD);
      
      $statement = $this->pdo->prepare(
         "INSERT INTO button (uid, ipAddress, name, stationId, clickAction, doubleClickAction, holdAction, lastContact, enabled) " . 
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $buttonInfo->uid,
            $buttonInfo->ipAddress,
            $buttonInfo->name,
            $buttonInfo->stationId,
            $clickAction,
            $doubleClickAction,
            $holdAction,
            $lastContact,
            $buttonInfo->enabled ? 1 : 0
         ]);
      
      return ($result);
   }
   
   public function updateButton($buttonInfo)
   {
      $lastContact = Time::toMySqlDate($buttonInfo->lastContact);
      
      $clickAction = $buttonInfo->getButtonAction(ButtonPress::SINGLE_CLICK);
      $doubleClickAction = $buttonInfo->getButtonAction(ButtonPress::DOUBLE_CLICK);
      $holdAction = $buttonInfo->getButtonAction(ButtonPress::HOLD);
      
      $statement = $this->pdo->prepare(
         "UPDATE button " .
         "SET uid = ?, ipAddress = ?, name = ?, stationId = ?, clickAction = ?, doubleClickAction = ?, " .
         "holdAction = ?, lastContact = ?, enabled = ? " .
         "WHERE buttonId = ?;");
      
      $result = $statement->execute(
         [
            $buttonInfo->uid,
            $buttonInfo->ipAddress,
            $buttonInfo->name,
            $buttonInfo->stationId,
            $clickAction,
            $doubleClickAction,
            $holdAction,
            $lastContact,
            $buttonInfo->enabled,
            $buttonInfo->buttonId
         ]);

      return ($result);
   }
   
   public function deleteButton($buttonId)
   {
      $statement = $this->pdo->prepare("DELETE FROM button WHERE buttonId = ?;");
      
      $result = $statement->execute([$buttonId]);
      
      return ($result);
   }
     
   // **************************************************************************
   //                                  Display
   
   public function getDisplay($displayId)
   {
      $statement = $this->pdo->prepare("SELECT * from display WHERE displayId = ?;");
      
      $result = $statement->execute([$displayId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getDisplays()
   {
      $statement = $this->pdo->prepare("SELECT * from display ORDER BY uid DESC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getDisplayByUid($uid)
   {
      $statement = $this->pdo->prepare("SELECT * from display WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function newDisplay($displayInfo)
   {
      $lastContact = Time::toMySqlDate($displayInfo->lastContact);
      
      $statement = $this->pdo->prepare(
         "INSERT INTO display (uid, ipAddress, version, name, scaling, presentationId, lastContact, enabled) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $displayInfo->uid,
            $displayInfo->ipAddress,
            $displayInfo->version,
            $displayInfo->name,
            $displayInfo->scaling,
            $displayInfo->presentationId,
            $lastContact,
            $displayInfo->enabled ? 1 : 0
         ]);
      
      return ($result);
   }
   
   public function updateDisplay($displayInfo)
   {
      $lastContact = Time::toMySqlDate($displayInfo->lastContact);
      
      $statement = $this->pdo->prepare(
         "UPDATE display " .
         "SET uid = ?, ipAddress = ?, version = ?, name = ?, scaling = ?, presentationId = ?, lastContact = ?, enabled = ? " .
         "WHERE displayId = ?;");

      $result = $statement->execute(
         [
            $displayInfo->uid,
            $displayInfo->ipAddress,
            $displayInfo->version,
            $displayInfo->name,
            $displayInfo->scaling,
            $displayInfo->presentationId,
            $lastContact,
            $displayInfo->enabled ? 1 : 0,
            $displayInfo->displayId
         ]);

      return ($result);
   }
   
   public function setDisplayResetTime($displayId, $resetTime)
   {
      $resetTime = $resetTime ? Time::toMySqlDate($resetTime) : null;
      
      $statement = $this->pdo->prepare("UPDATE display SET resetTime = ? WHERE displayId = ?;");
      
      $result = $statement->execute([$resetTime, $displayId]);
      
      return ($result);
   }
   
   public function setDisplayUpgradeTime($displayId, $upgradeTime, $firmwareImage = null)
   {
      $resetTime = $upgradeTime ? Time::toMySqlDate($upgradeTime) : null;
      
      $statement = $this->pdo->prepare("UPDATE display SET upgradeTime = ?, firmwareImage = ? WHERE displayId = ?;");
      
      $result = $statement->execute([$resetTime, $firmwareImage, $displayId]);
      
      return ($result);
   }
   
   public function deleteDisplay($displayId)
   {
      $statement = $this->pdo->prepare("DELETE FROM display WHERE displayId = ?;");
      
      $result = $statement->execute([$displayId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                               Presentation
   
   public function getPresentations()
   {
      $statement = $this->pdo->prepare("SELECT * from presentation ORDER BY name ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getPresentation($presentationId)
   {
      $statement = $this->pdo->prepare("SELECT * from presentation WHERE presentationId = ?;");
      
      $result = $statement->execute([$presentationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function newPresentation($presentationInfo)
   {
      $statement = $this->pdo->prepare("INSERT INTO presentation (name) VALUES (?);");
      
      $result = $statement->execute([$presentationInfo->name]);
      
      return ($result);
   }
   
   public function updatePresentation($presentationInfo)
   {
      $statement = $this->pdo->prepare("UPDATE presentation SET name = ? WHERE presentationId = ?;");
      
      $result = $statement->execute([$presentationInfo->name, $presentationInfo->presentationId]);
      
      return ($result);
   }
   
   public function deletePresentation($presentationId)
   {
      $statement = $this->pdo->prepare("DELETE FROM presentation WHERE presentationId = ?;");
      
      $result = $statement->execute([$presentationId]);
      
      $statement = $this->pdo->prepare("DELETE FROM slide WHERE presentationId = ?;");
      
      $result &= $statement->execute([$presentationId]);
      
      return ($result);
   }
   

   
   // **************************************************************************
   //                                  Sensor
   
   public function getSensor($sensorId)
   {
      $statement = $this->pdo->prepare("SELECT * from sensor WHERE sensorId = ?;");
      
      $result = $statement->execute([$sensorId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getSensors()
   {
      $statement = $this->pdo->prepare("SELECT * from sensor ORDER BY uid ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getSensorByUid($uid)
   {
      $statement = $this->pdo->prepare("SELECT * from sensor WHERE uid = ?;");
      
      $result = $statement->execute([$uid]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function sensorExists($uid)
   {
      $result = $this->getSensor($uid);
      
      return ($result && (count($result) > 0));
   }
   
   public function newSensor($sensorInfo)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO sensor (uid, ipAddress, version, name, sensorType, stationId, lastContact, enabled) " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $sensorInfo->uid, 
            $sensorInfo->ipAddress,
            $sensorInfo->version,
            $sensorInfo->name,
            $sensorInfo->sensorType,
            $sensorInfo->stationId, 
            Time::toMySqlDate($sensorInfo->lastContact),
            $sensorInfo->enabled ? 1 : 0
         ]);
      
      return ($result);
   }
   
   public function updateSensor($sensorInfo)
   {
      $statement = $this->pdo->prepare(
         "UPDATE sensor " .
         "SET uid = ?, ipAddress = ?, version = ?, name = ?, sensorType = ?, stationId = ?, lastContact = ?, enabled = ? " .
         "WHERE sensorId = ?;");
      
      $result = $statement->execute(
         [
            $sensorInfo->uid,
            $sensorInfo->ipAddress,
            $sensorInfo->version,
            $sensorInfo->name,
            $sensorInfo->sensorType,
            $sensorInfo->stationId,
            Time::toMySqlDate($sensorInfo->lastContact),
            $sensorInfo->enabled ? 1 : 0,
            $sensorInfo->sensorId,
         ]);
      
      return ($result);
   }
   
   public function deleteSensor($sensorId)
   {
      $statement = $this->pdo->prepare("DELETE FROM sensor WHERE sensorId = ?;");
      
      $result = $statement->execute([$sensorId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                  Shift
   
   public function getShift($shiftId)
   {
      $statement = $this->pdo->prepare("SELECT * from shift WHERE shiftId = ?;");
      
      $result = $statement->execute([$shiftId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getShifts()
   {
      $statement = $this->pdo->prepare("SELECT * from shift ORDER BY startTime ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function newShift($shiftInfo)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO shift (shiftName, startTime, endTime) VALUES (?, ?, ?)");
      
      $result = $statement->execute([$shiftInfo->shiftName, $shiftInfo->startTime, $shiftInfo->endTime]);
      
      return ($result);
   }
   
   public function updateShift($shiftInfo)
   {
      $statement = $this->pdo->prepare(
         "UPDATE shift SET shiftName = ?, startTime = ?, endTime = ? WHERE shiftId = ?;");
      
      $result = $statement->execute([$shiftInfo->shiftName, $shiftInfo->startTime, $shiftInfo->endTime, $shiftInfo->shiftId]);
      
      return ($result);
   }
   
   public function deleteShift($shiftId)
   {
      $statement = $this->pdo->prepare("DELETE FROM shift WHERE shiftId = ?;");
      
      $result = $statement->execute([$shiftId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                  Slides
   
   public function getSlide($slideId)
   {
      $statement = $this->pdo->prepare("SELECT * from slide WHERE slideId = ?;");
      
      $result = $statement->execute([$slideId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getSlidesForPresentation($presentationId)
   {
      $statement = $this->pdo->prepare("SELECT * from slide WHERE presentationId = ? ORDER BY slideIndex ASC;");
      
      $result = $statement->execute([$presentationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function newSlide($slideInfo)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO slide (presentationId, slideType, slideIndex, duration, enabled, " . 
         "reloadInterval, url, image, shiftId, groupId, stationFilter, stationId1, stationId2, stationId3, stationId4)  " .
         "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $slideInfo->presentationId,
            $slideInfo->slideType,
            $slideInfo->slideIndex,
            $slideInfo->duration,
            $slideInfo->enabled ? 1 : 0,
            $slideInfo->reloadInterval,
            $slideInfo->url,
            $slideInfo->image,
            $slideInfo->shiftId,
            $slideInfo->groupId,
            $slideInfo->stationFilter,
            $slideInfo->stationIds[0],
            $slideInfo->stationIds[1],
            $slideInfo->stationIds[2],
            $slideInfo->stationIds[3]
         ]);

      return ($result);
   }
   
   public function updateSlide($slideInfo)
   {
      $statement = $this->pdo->prepare(
         "UPDATE slide " .
         "SET presentationId = ?, slideType = ?, slideIndex = ?, duration = ?, enabled = ?, " .
         "reloadInterval = ?, url = ?, image = ?, shiftId = ?, groupId = ?, stationFilter = ?, " .
         "stationId1 = ?, stationId2 = ?, stationId3 = ?, stationId4 = ? " .
         "WHERE slideId = ?;");
      
      $result = $statement->execute(
         [
            $slideInfo->presentationId,
            $slideInfo->slideType,
            $slideInfo->slideIndex,
            $slideInfo->duration,
            $slideInfo->enabled ? 1 : 0,
            $slideInfo->reloadInterval,
            $slideInfo->url,
            $slideInfo->image,
            $slideInfo->shiftId,
            $slideInfo->groupId,
            $slideInfo->stationFilter,
            $slideInfo->stationIds[0],
            $slideInfo->stationIds[1],
            $slideInfo->stationIds[2],
            $slideInfo->stationIds[3],
            $slideInfo->slideId
         ]);
      
      return ($result);
   }
   
   public function updateSlideOrder($slideId, $slideIndex)
   {
      $statement = $this->pdo->prepare("UPDATE slide SET slideIndex = ? WHERE slideId = ?;");
      
      $result = $statement->execute([$slideId, $slideIndex]);
      
      return ($result);
   }
   
   public function deleteSlide($slideId)
   {
      $statement = $this->pdo->prepare("DELETE FROM slide WHERE slideId = ?;");
      
      $result = $statement->execute([$slideId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                 Station
   
   public function getStation($stationId)
   {
      $statement = $this->pdo->prepare("SELECT * from station WHERE stationId = ?;");
      
      $result = $statement->execute([$stationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getStations($includeVirtualStations = true)
   {
      $virtualStationClause = $includeVirtualStations ? "TRUE" : "isVirtualStation = 0";
      
      $statement = $this->pdo->prepare("SELECT * from station WHERE $virtualStationClause ORDER BY name ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function stationExists($stationId)
   {
      $result = $this->getStation($stationId);
      
      return ($result && (count($result) > 0));
   }
   
   public function newStation($stationInfo)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));
      
      $statement = $this->pdo->prepare(
         "INSERT INTO station (name, label, objectName, cycleTime, hideOnSummary, updateTime) " .
         "VALUES (?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $stationInfo->name, 
            $stationInfo->label, 
            $stationInfo->objectName, 
            $stationInfo->cycleTime, 
            $stationInfo->hideOnSummary ? 1 : 0, 
            $now
         ]);
      
      return ($result);
   }
   
   public function addStation($stationInfo)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO station (name, label, objectName, cycleTime, hideOnSummary, isVirtualStation) " .
         "VALUES (?, ?, ?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $stationInfo->name,
            $stationInfo->label,
            $stationInfo->objectName,
            $stationInfo->cycleTime,
            $stationInfo->hideOnSummary ? 1 : 0,
            $stationInfo->isVirtualStation ? 1 : 0
         ]);
      
      return ($result);
   }
   
   public function updateStation($stationInfo)
   {
      $statement = $this->pdo->prepare(
         "UPDATE station " .
         "SET name = ?, label = ?, objectName = ?, cycleTime = ?, hideOnSummary = ?, isVirtualStation = ? " .
         "WHERE stationId = ?;");
      
      $result = $statement->execute(
         [
            $stationInfo->name,
            $stationInfo->label,
            $stationInfo->objectName,
            $stationInfo->cycleTime,
            $stationInfo->hideOnSummary ? 1 : 0,
            $stationInfo->isVirtualStation ? 1 : 0,
            $stationInfo->stationId
         ]);
      
      return ($result);
   }
   
   public function deleteStation($stationId)
   {
      $statement = $this->pdo->prepare("DELETE FROM station WHERE stationId = ?;");
      
      $result = $statement->execute([$stationId]);
      
      $statement = $this->pdo->prepare("DELETE FROM count WHERE stationId = ?;");
      
      $result &= $statement->execute([$stationId]);
      
      $statement = $this->pdo->prepare("UPDATE button SET stationId = NULL WHERE stationId = ?;");
      
      $result &= $statement->execute([$stationId]);

      return ($result);
   }
   
   public function touchStation($stationId)
   {
      $now = Time::toMySqlDate(Time::now("Y-m-d H:i:s"));

      $statement = $this->pdo->prepare("UPDATE station SET updateTime = ? WHERE stationId = ?;");

      // Record last update time.
      $result = $statement->execute([$now, $stationId]);
      
      return ($result);
   }
   
   public function getUpdateTime($stationId)
   {
      $updateTime = "";
      
      $statement = $this->pdo->prepare("SELECT updateTime from station WHERE stationId = ?;");
      
      $result = $statement->execute([$stationId]) ? $statement->fetchAll() : null;

      if ($result && ($row = $result[0]))
      {
         $updateTime = Time::fromMySqlDate($row["updateTime"], "Y-m-d H:i:s");
      }
      
      return ($updateTime);
   }
   
   // **************************************************************************
   //                               Station Groups
   
   public function getStationGroup($groupId)
   {
      $statement = $this->pdo->prepare("SELECT * from stationgroup WHERE groupId = ?;");
      
      $result = $statement->execute([$groupId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getStationGroups()
   {
      $statement = $this->pdo->prepare("SELECT * from stationgroup ORDER BY name ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function newStationGroup($stationGroup)
   {
      $statement = $this->pdo->prepare("INSERT INTO stationgroup (name, virtualStationId) VALUES (?, ?);");
      
      $result = 
         $statement->execute([
            $stationGroup->name,
            $stationGroup->virtualStationId
         ]);
      
      return ($result);
   }
   
   public function updateStationGroup($stationGroup)
   {
      $statement = $this->pdo->prepare("UPDATE stationgroup SET name = ?, virtualStationId = ? WHERE groupId = ?;");
      
      $result =
      $statement->execute([
               $stationGroup->name,
               $stationGroup->virtualStationId,
               $stationGroup->groupId
      ]);
      
      return ($result);
   }
   
   public function deleteStationGroup($groupId)
   {
      $statement = $this->pdo->prepare("DELETE FROM stationgroup WHERE groupId = ?;");
      
      $result = $statement->execute([$groupId]);
      
      $statement = $this->pdo->prepare("UPDATE slide SET groupId = 0 WHERE groupId = ?;");
      
      $result = $statement->execute([$groupId]);
      
      return ($result);
   }
   
   public function getVirtualStationIds($stationId)
   {
      $statement = 
         $this->pdo->prepare(
            "SELECT stationgroup.virtualStationId AS virtualStationId FROM stationgroup " .
            "INNER JOIN group_station ON stationgroup.groupId = group_station.groupId " .
            "INNER JOIN station ON station.stationId = group_station.stationId " .
            "WHERE (station.stationId = ?) AND (stationgroup.virtualStationId > 0);");
      
      $result = $statement->execute([$stationId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Group_Station
   
   public function getStationsForGroup($groupId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM station INNER JOIN group_station ON station.stationId = group_station.stationId WHERE group_station.groupId = ? ORDER BY station.name ASC;");
      
      $result = $statement->execute([$groupId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function stationInGroup($stationId, $groupId)
   {
      $statement = $this->pdo->prepare("SELECT * FROM group_station WHERE groupId = ? AND stationId = ?;");
      
      $result = $statement->execute([$groupId, $stationId]) ? $statement->fetchAll() : null;
      
      return ($result && (count($result) > 0));
   }
   
   public function addStationToGroup($stationId, $groupId)
   {
      $statement = $this->pdo->prepare("INSERT INTO group_station (groupId, stationId) VALUE (?, ?);");
      
      $result = $statement->execute([$groupId, $stationId]);
      
      return ($result);
   }
   
   public function removeStationFromGroup($stationId, $groupId)
   {
      $statement = $this->pdo->prepare("DELETE FROM group_station WHERE groupId = ? AND stationId = ?;");
      
      $result = $statement->execute([$groupId, $stationId]);
      
      return ($result);
   }
   
   // **************************************************************************
   //                                Plugin
   
   public function pluginTableExists()
   {
      $tableExists = false;
      
      $statement = $this->pdo->prepare("DESCRIBE plugin;");
    
      try
      {
         $tableExists = ($statement->execute() !== false);
      }
      catch (Exception $e)
      {
         // Table doesn't exist.
      }

      return ($tableExists);
   }
   
   public function getPlugin($pluginId)
   {
      $statement = $this->pdo->prepare("SELECT * from plugin WHERE pluginId = ?;");
      
      $result = $statement->execute([$pluginId]) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function getPlugins()
   {
      $statement = $this->pdo->prepare("SELECT * from plugin ORDER BY pluginId ASC;");
      
      $result = $statement->execute() ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function addPlugin($plugin)
   {
      $statement = $this->pdo->prepare(
         "INSERT INTO plugin (class, config, status) " .
         "VALUES (?, ?, ?);");
      
      $result = $statement->execute(
         [
            $plugin->class,
            json_encode($plugin->config),
            json_encode($plugin->status)
         ]);
      
      return ($result);
   }
   
   public function updatePlugin($plugin)
   {
      $statement = $this->pdo->prepare(
         "UPDATE plugin " .
         "SET class = ?, config = ?, status = ? " .
         "WHERE pluginId = ?;");
      
      $result = $statement->execute(
         [
            $plugin->class,
            json_encode($plugin->config),
            json_encode($plugin->status),
            $plugin->pluginId
         ]);
   
      return ($result);
   }
   
   public function deletePlugin($pluginId)
   {
      $statement = $this->pdo->prepare("DELETE FROM plugin WHERE pluginId = ?;");
      
      $result = $statement->execute([$pluginId]);
      
      return ($result);
   }   
   
   // **************************************************************************
   //                        Exact Count (experimental)
   
   public function getExactCount($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $count = 0;
      
      $params = array();
      
      $stationClause = "";
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationClause = "stationId = ? AND";
         $params[] = $stationId;
      }
      
      $shiftClause = "";
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftClause = "shiftId = ? AND";
         $params[] = $shiftId;
      }
      
      $params[] = Time::toMySqlDate($startDateTime);
      $params[] = Time::toMySqlDate($endDateTime);
      
      $query = "SELECT SUM(count) AS countSum FROM exactcount WHERE $stationClause $shiftClause dateTime BETWEEN ? AND ?;";
      
      $statement = $this->pdo->prepare($query);
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      if ($result && ($row = $result[0]))
      {
         $count = intval($row['countSum']);
      }
      
      return ($count);
   }
   
   public function getExactCounts($stationId, $shiftId, $startDateTime, $endDateTime)
   {
      $params = array();
      
      $stationClause = "";
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $stationClause = "stationId = ? AND";
         $params[] = $stationId;
      }
      
      $shiftClause = "";
      if ($shiftId != ShiftInfo::UNKNOWN_SHIFT_ID)
      {
         $shiftClause = "shiftId = ? AND";
         $params[] = $shiftId;
      }
      
      $params[] = Time::toMySqlDate($startDateTime);
      $params[] = Time::toMySqlDate($endDateTime);
      
      $statement = $this->pdo->prepare(
         "SELECT *, DATE_FORMAT(dateTime, '%Y-%m-%d %H:%i:%s.%f') as dateTime FROM exactcount " .
         "WHERE $stationClause $shiftClause dateTime BETWEEN ? AND ? " .
         "ORDER BY stationId ASC, dateTime ASC;");
      
      $result = $statement->execute($params) ? $statement->fetchAll() : null;
      
      return ($result);
   }
   
   public function updateExactCount($stationId, $shiftId, $count)
   {
      $dateTime = Time::toMySqlDate(Time::now("Y-m-d H:i:s.u"), "Y-m-d H:i:s.u");
      
      $statement = $this->pdo->prepare(
         "INSERT INTO exactcount " .
         "(dateTime, stationId, shiftId, count) " .
         "VALUES (?, ?, ?, ?);");
      
      $result = $statement->execute(
         [
            $dateTime,
            $stationId,
            $shiftId,
            $count
         ]);
      
      return ($result);
   }
   
   // **************************************************************************
   
   protected function calculateCountTime($stationId)
   {
      $countTime = 0;
      
      $now = Time::getDateTime(Time::now());
      
      $updateTime = Time::getDateTime($this->getUpdateTime($stationId));
      
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
   
   // **************************************************************************
   
   private static $databaseInstance;
}

?>