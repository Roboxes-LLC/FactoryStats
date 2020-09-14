<?php

require_once 'database.php';
require_once 'permissions.php';
require_once 'roles.php';

class UserInfo
{
   const UNKNOWN_USER_ID = 0;
   
   const UNKNOWN_EMPLOYEE_NUMBER = 0;
   
   const ADMIN_EMPLOYEE_NUMBER = 1;
   
   const NO_ASSIGNED_STATIONS = 0;
   
   public $userId;
   public $employeeNumber;
   public $username;
   public $password;
   public $firstName;
   public $lastName;
   public $roles;
   public $permissions;       // bitfield
   public $email;
   public $authToken;
   public $assignedStations;  // bitfield
   
   public function __construct()
   {
      $this->userId = UserInfo::UNKNOWN_USER_ID;
      $this->employeeNumber = UserInfo::UNKNOWN_EMPLOYEE_NUMBER;
      $this->username = null;
      $this->password = null;
      $this->firstName = null;
      $this->roles = Role::UNKNOWN;
      $this->permissions = Permission::NO_PERMISSIONS;
      $this->email = null;
      $this->authToken = null;
      $this->assignedStations = UserInfo::NO_ASSIGNED_STATIONS;
   }
   
   public static function load($userId)
   {
      $userInfo = null;
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUser($userId);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   static public function loadByName($username)
   {
      $userInfo = null;
      
      $database = new FlexscreenDatabase();
      
      $database->connect();
      
      if ($database->isConnected())
      {
         $result = $database->getUserByName($username);
         
         if ($result && ($row = $result->fetch_assoc()))
         {
            $userInfo = new UserInfo();
            
            $userInfo->initialize($row);
         }
      }
      
      return ($userInfo);
   }
   
   static public function getUsersByRole($role)
   {
      $users = array();
      
      $database = FlexscreenDatabase::getInstance();
           
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRole($role);

         if ($result)
         {
            while ($row = $result->fetch_assoc())
            {
               $userInfo = new UserInfo();
               
               $userInfo->initialize($row);
               
               $users[] = $userInfo;
            }
         }
      }
      
      return ($users);
   }
   
   static public function getUsersByRoles($roles)
   {
      $users = array();
      
      $database = FlexscreenDatabase::getInstance();
      
      if ($database && $database->isConnected())
      {
         $result = $database->getUsersByRoles($roles);
         
         if ($result)
         {
            while ($row = $result->fetch_assoc())
            {
               $userInfo = new UserInfo();
               
               $userInfo->initialize($row);
               
               $users[] = $userInfo;
            }
         }
      }
      
      return ($users);
   }
   
   public function getFullName()
   {
      return ($this->firstName . " " . $this->lastName);
   }
   
   public function setAssignedStation($stationId)
   {
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $this->assignedStations |= (1 << ($stationId - StationInfo::MIN_STATION_ID));
      }  
   }
   
   public function clearAssignedStation($stationId)
   {
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $this->assignedStations |= ~(1 << ($stationId - StationInfo::MIN_STATION_ID));
      }      
   }   
   
   public function isAssignedStation($stationId)
   {
      $isAssigned = false;
      
      if ($stationId != StationInfo::UNKNOWN_STATION_ID)
      {
         $isAssigned = (($this->assignedStations & (1 << ($stationId - StationInfo::MIN_STATION_ID))) > 0);
      }
      
      return ($isAssigned);
   }
   
   public function getAssignedStations()
   {
      $stationIds = array();
      
      for ($stationId = StationInfo::MIN_STATION_ID; stationId <= StationInfo::MAX_STATION_ID; $stationId++)
      {
         if ($this->isAssignedStation($stationId))
         {
            $stationIds[] = $stationId;
         }
      }
      
      return ($stationIds);
   }
   
   private function initialize($row)
   {
      $this->userId = intval($row['userId']);
      $this->employeeNumber = intval($row['employeeNumber']);
      $this->username = $row['username'];
      $this->password = $row['password'];
      $this->roles = intval($row['roles']);
      $this->permissions = intval($row['permissions']);
      $this->firstName = $row['firstName'];
      $this->lastName = $row['lastName'];
      $this->email = $row['email'];
      $this->authToken = $row['authToken'];
      $this->assignedStations = $row["assignedStations"];
   }
}

/*
$userInfo = null;

if (isset($_GET["userId"]))
{
   $userId = $_GET["userId"];
   $userInfo = UserInfo::load($userId);
}
else if (isset($_GET["username"]))
{
   $username = $_GET["username"];
   $userInfo = UserInfo::loadByName($username);
}
    
if ($userInfo)
{
   echo "userId: " .           $userInfo->userId .           "<br/>";
   echo "employeeNumber: " .   $userInfo->employeeNumber .   "<br/>";
   echo "username: " .         $userInfo->username .         "<br/>";
   echo "password: " .         $userInfo->password .         "<br/>";
   echo "roles: " .            $userInfo->roles .            "<br/>";
   echo "permissions: " .      $userInfo->permissions .      "<br/>";
   echo "firstName: " .        $userInfo->firstName .        "<br/>";
   echo "lastName: " .         $userInfo->lastName .         "<br/>";
   echo "email: " .            $userInfo->email .            "<br/>";
   echo "authToken: " .        $userInfo->authToken .        "<br/>";
   echo "assignedStations: " . $userInfo->assignedStations . "<br/>";
   
   echo "fullName: " . $userInfo->getFullName() . "<br/>";
}
else
{
   echo "No user found.";
}
*/

?>